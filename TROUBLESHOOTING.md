# Troubleshooting Guide

> **Purpose**: Document known issues, root causes, and verified solutions
>
> **Critical**: When debugging, ALWAYS check this file FIRST before proposing solutions

---

## Table of Contents

- [PROBLEM-001: User Duplication on Authentication](#problem-001-user-duplication-on-authentication)
- [Diagnostic Workflow](#diagnostic-workflow)
- [Common Pitfalls](#common-pitfalls)

---

## PROBLEM-001: User Duplication on Authentication

**Status**: ✅ RESOLVED
**Date Identified**: 2025-10-24
**Date Resolved**: 2025-10-24
**Severity**: HIGH (creates duplicate users, data integrity issue)

### Symptoms

- Multiple WordPress users created with identical email address
- Happens during Magic Link authentication
- Happens during OAuth authentication (Google, Facebook)
- Users appear with sequential IDs (e.g., User ID 21, 22)

### How to Diagnose

**CRITICAL: Run these commands BEFORE proposing any solution!**

```bash
# 1. Check Docker logs for Supabase Bridge events
docker compose logs wordpress --tail=100 | grep "Supabase Bridge"

# 2. Look for duplicate email entries with DIFFERENT PIDs
# Example of race condition:
# [pid 57:tid 57] alexeykrol2@gmail.com
# [pid 54:tid 54] alexeykrol2@gmail.com  ← Different PID!

# 3. Check timestamp difference
# If timestamps differ by < 1 second = race condition
```

**Signs of Race Condition:**
- ✅ Same email appears 2+ times in logs
- ✅ Different `[pid X]` values for each occurrence
- ✅ Timestamps within 0-500 milliseconds of each other
- ✅ Multiple "User created successfully" messages for same email

**Example from logs:**
```
[Fri Oct 24 00:27:55.336390] [pid 57:tid 57] alexeykrol2@gmail.com
[Fri Oct 24 00:27:55.336673] [pid 54:tid 54] alexeykrol2@gmail.com
                            ^^^^^ Different PID = different PHP process
Difference: 0.283ms = RACE CONDITION
```

### Root Cause

**Level**: Backend (PHP)
**Component**: `supabase-bridge.php` - REST API callback handler

**Technical Explanation:**

1. User clicks Magic Link → Supabase redirects with `#access_token=...`
2. JavaScript `onAuthStateChange` fires callback
3. **PROBLEM**: Callback can fire MULTIPLE times simultaneously:
   - Browser hash change event
   - Supabase SDK internal state change
   - Page reload/navigation timing
4. Multiple POST requests sent to `/wp-json/supabase-auth/callback` **at the same time**
5. Apache/PHP-FPM handles each request in **separate PHP process** (different PIDs)
6. Both processes run `get_user_by('email', $email)` simultaneously
7. Both processes see "user doesn't exist"
8. Both processes call `wp_create_user()` → **DUPLICATE USERS**

**Why JavaScript solutions DON'T work:**
- ❌ Once HTTP POST request leaves browser, JavaScript can't cancel it
- ❌ `localStorage` is async - too slow to prevent race condition
- ❌ In-memory flags (`isRedirecting`) don't work across separate PHP processes
- ❌ Browser can send requests from different tabs/windows

### Solution (VERIFIED)

**Level**: Backend (PHP) - Server-side distributed lock
**File**: `supabase-bridge.php`
**Lines**: 367-467

**Three-layer protection:**

#### Layer 1: Check by Supabase UUID (lines 369-378)
```php
// BEFORE checking email, check by Supabase unique ID
$existing_users = get_users([
  'meta_key' => 'supabase_user_id',
  'meta_value' => $supabase_user_id,  // UUID from JWT
  'number' => 1
]);
```

**Why this works:**
- Supabase UUID is globally unique
- Prevents duplicate even if email check has race condition

#### Layer 2: Distributed Lock via WordPress Transient API (lines 386-414)
```php
$lock_key = 'sb_create_lock_' . md5($supabase_user_id);

if (get_transient($lock_key)) {
  // Another process is creating user - WAIT
  sleep(2);
  // Check again for user
} else {
  // Acquire lock for 5 seconds
  set_transient($lock_key, 1, 5);
  // Create user
  wp_create_user(...);
  // Release lock
  delete_transient($lock_key);
}
```

**Why this works:**
- WordPress Transient API is database-backed (shared across all PHP processes)
- Second process waits instead of creating duplicate
- Lock auto-expires (5 seconds) to prevent deadlock

#### Layer 3: Retry with UUID check on error (lines 426-444)
```php
if (is_wp_error($uid)) {
  // wp_create_user failed - maybe user was created by other process?
  $existing_users = get_users([
    'meta_key' => 'supabase_user_id',
    'meta_value' => $supabase_user_id
  ]);

  if (!empty($existing_users)) {
    // Found user created by other process - use it
    $user = $existing_users[0];
  }
}
```

**Why this works:**
- Even if lock somehow fails, we gracefully recover
- No error shown to user
- Data integrity maintained

### What DOESN'T Work (Lessons Learned)

**Attempted Solution #1: JavaScript `localStorage` lock**
```javascript
// ❌ DOESN'T WORK
if (localStorage.getItem('processing')) return;
localStorage.setItem('processing', 'true');
fetch('/wp-json/supabase-auth/callback', ...);
```

**Why it failed:**
- `localStorage.setItem()` is async
- Both callbacks execute setItem at same microsecond
- Both pass the check before either writes
- Both send POST request

---

**Attempted Solution #2: JavaScript in-memory Set**
```javascript
// ❌ DOESN'T WORK for server-side race condition
const processingTokens = new Set();
if (processingTokens.has(token)) return;
processingTokens.add(token);
```

**Why it failed:**
- Works ONLY within single JavaScript execution context
- Once POST request sent to server, JavaScript can't stop it
- Two PHP processes = two separate execution contexts
- In-memory Set doesn't exist on server

---

**Attempted Solution #3: `isRedirecting` flag**
```javascript
// ❌ DOESN'T WORK for concurrent requests
let isRedirecting = false;
if (isRedirecting) return;
isRedirecting = true;
```

**Why it failed:**
- Same problem as Solution #2
- Flag exists only in browser memory
- Doesn't prevent concurrent PHP processes

---

**Attempted Solution #4: Early return checks**
```javascript
// ❌ DOESN'T WORK - wrong level
async function handleAuthChange(event, session) {
  if (isRedirecting) return;  // Too late!
  // ... fetch already sent ...
}
```

**Why it failed:**
- By the time we check, HTTP request already in flight
- Can't prevent server from processing request

### Verification Steps

After implementing server-side lock, verify:

```bash
# 1. Clear all test users
# WordPress Admin → Users → Delete all ybot* users

# 2. Check Docker logs in real-time
docker compose logs wordpress --tail=50 --follow

# 3. Test with NEW email (never used before)
# Use: ybot[random-number]@gmail.com

# 4. Send Magic Link and click it

# 5. Check logs for these SUCCESS messages:
"Supabase Bridge: User created successfully - User ID: XX"
# Should appear ONCE, not twice

# 6. Check WordPress Admin → Users
# Should see ONLY ONE user with that email
```

### Code References

- **Main handler**: `supabase-bridge.php:234-427` (`sb_handle_callback()`)
- **UUID check**: `supabase-bridge.php:369-378`
- **Distributed lock**: `supabase-bridge.php:386-414`
- **Retry logic**: `supabase-bridge.php:420-444`
- **Meta update**: `supabase-bridge.php:464-467`

### Related Issues

- Race condition can also affect user metadata updates
- WordPress `update_user_meta()` is atomic but doesn't prevent duplicate users
- Consider using database transactions for critical operations in future

---

## Diagnostic Workflow

**ALWAYS follow this workflow when debugging ANY issue:**

### Step 1: Reproduce the Issue
- [ ] Can you reproduce the issue consistently?
- [ ] What are the exact steps to reproduce?
- [ ] Does it happen in incognito mode?

### Step 2: Check Logs FIRST
```bash
# Docker logs
docker compose logs wordpress --tail=100

# Filter by component
docker compose logs wordpress | grep "Supabase Bridge"

# Real-time monitoring
docker compose logs wordpress --follow
```

### Step 3: Identify the Layer
- [ ] Frontend (JavaScript console errors)?
- [ ] Network (check Network tab in DevTools)?
- [ ] Backend (PHP errors in Docker logs)?
- [ ] Database (check WordPress database)?

### Step 4: Root Cause Analysis
- [ ] What is the FIRST point of failure?
- [ ] Is this a race condition? (check PIDs and timestamps)
- [ ] Is this a configuration issue? (check Settings)
- [ ] Is this a third-party issue? (Supabase, Elementor, etc.)

### Step 5: Solution Design
- [ ] Does solution address ROOT CAUSE or just symptoms?
- [ ] Will solution work across all layers (frontend, backend, database)?
- [ ] Are there edge cases?

### Step 6: Document BEFORE Implementing
- [ ] Update this TROUBLESHOOTING.md
- [ ] Add diagnostic commands
- [ ] Document what DOESN'T work (save future time!)

### Step 7: Verify Solution
- [ ] Test in clean environment
- [ ] Check logs confirm fix
- [ ] Test edge cases

---

## Common Pitfalls

### ❌ Pitfall #1: Solving Symptoms Instead of Root Cause

**Example:**
- **Symptom**: Duplicate users created
- **Wrong approach**: Add JavaScript flags to prevent multiple callbacks
- **Right approach**: Check logs → see different PIDs → add server-side lock

**Lesson**: Always identify the LAYER where problem occurs before proposing solution.

---

### ❌ Pitfall #2: Not Checking Logs First

**Example:**
- User reports "duplicate users"
- AI suggests JavaScript fix without checking logs
- Wastes time on wrong layer

**Lesson**: **LOGS FIRST, SOLUTION SECOND**

---

### ❌ Pitfall #3: Assuming Client-Side Solutions Work for Server-Side Problems

**Example:**
- Race condition visible in logs (`[pid 57]` and `[pid 54]`)
- Trying to fix with JavaScript (client-side)
- JavaScript can't control separate PHP processes

**Lesson**: Match solution to problem layer (client vs server vs database)

---

### ❌ Pitfall #4: Not Documenting Failed Solutions

**Example:**
- Try Solution A → doesn't work
- Try Solution B → doesn't work
- Try Solution C → works
- Only document Solution C

**Problem**: Next session, AI might try Solution A or B again!

**Lesson**: Document what DOESN'T work is as valuable as what DOES work.

---

## Template for New Issues

When adding new issue to this file, use this template:

```markdown
## PROBLEM-XXX: [Brief Description]

**Status**: 🔄 INVESTIGATING / ✅ RESOLVED / ❌ WONTFIX
**Date Identified**: YYYY-MM-DD
**Date Resolved**: YYYY-MM-DD (if resolved)
**Severity**: LOW / MEDIUM / HIGH / CRITICAL

### Symptoms
- Bullet list of observable symptoms
- How user experiences the problem

### How to Diagnose
```bash
# Exact commands to run
# What to look for in output
```

### Root Cause
**Level**: Frontend / Backend / Database / Infrastructure
**Component**: Specific file/module

Technical explanation of WHY problem occurs

### Solution (VERIFIED)
Detailed solution with code snippets and line numbers

### What DOESN'T Work (Lessons Learned)
Document attempted solutions that failed and WHY

### Verification Steps
How to verify the fix works

### Code References
- File paths and line numbers

### Related Issues
Links to related problems
```

---

**Last Updated**: 2025-10-24
**Maintainer**: AI Agent / Developer reviewing CLAUDE.md
