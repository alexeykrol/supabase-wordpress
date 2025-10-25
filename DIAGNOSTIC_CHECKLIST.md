# Diagnostic Checklist

> **Quick reference for debugging issues**
>
> **Use this**: When user reports a bug or unexpected behavior

---

## Step-by-Step Diagnostic Workflow

### ☑️ Step 1: Check Documentation First

**Before doing ANYTHING:**

- [ ] Read `TROUBLESHOOTING.md` - is this issue already documented?
- [ ] Check `BACKLOG.md` - is this a known open task?
- [ ] Review `CLAUDE.md` - any project-specific debugging notes?

**If issue is documented:**
→ Follow the documented solution
→ Skip to Step 7 (Verify Solution)

**If issue is NEW:**
→ Continue to Step 2

---

### ☑️ Step 2: Gather Information

**Ask user these questions:**

- [ ] What were you trying to do? (expected behavior)
- [ ] What actually happened? (actual behavior)
- [ ] Can you reproduce it? (steps to reproduce)
- [ ] First time or recurring? (frequency)
- [ ] Any error messages? (screenshots, console logs)

**Check environment:**

```bash
# Current directory
pwd

# Git status (any uncommitted changes?)
git status

# Docker status
docker compose ps

# Recent changes
git log --oneline -n 5
```

---

### ☑️ Step 3: Check Logs

**CRITICAL: Always check logs BEFORE proposing solution!**

```bash
# WordPress logs (last 100 lines)
docker compose logs wordpress --tail=100

# Filter by component
docker compose logs wordpress | grep "Supabase Bridge"
docker compose logs wordpress | grep "error"
docker compose logs wordpress | grep "fatal"

# Real-time monitoring (in separate terminal)
docker compose logs wordpress --follow
```

**What to look for:**

- [ ] Error messages (stack traces, exceptions)
- [ ] Warning messages
- [ ] Unusual patterns (repeated requests, timeouts)
- [ ] **PIDs and timestamps** (race condition indicators)

**Race Condition Indicators:**
```
[Fri Oct 24 00:27:55.336390] [pid 57:tid 57] alexeykrol2@gmail.com
[Fri Oct 24 00:27:55.336673] [pid 54:tid 54] alexeykrol2@gmail.com
                            ^^^^^ Different PID
Timestamp difference: < 1 second = RACE CONDITION
```

---

### ☑️ Step 4: Identify Problem Layer

**Check each layer systematically:**

#### Frontend (JavaScript)

```bash
# Ask user to check browser console
# Look for:
# - JavaScript errors
# - Failed network requests
# - Console.log messages
```

- [ ] JavaScript errors in console?
- [ ] Failed fetch/XHR requests?
- [ ] Correct event handlers firing?

#### Network

```bash
# Ask user to check Browser DevTools → Network tab
# Look for:
# - Failed requests (4xx, 5xx status codes)
# - Request payload (is data correct?)
# - Response body (error messages?)
```

- [ ] HTTP status codes (200 = success, 4xx = client error, 5xx = server error)
- [ ] Request headers (CORS issues?)
- [ ] Request/response timing (slow requests?)

#### Backend (PHP)

```bash
# Check Docker logs (see Step 3)
# Look for:
# - PHP errors/warnings
# - WordPress errors
# - Plugin errors
```

- [ ] PHP errors/warnings in logs?
- [ ] WordPress hooks firing correctly?
- [ ] Database queries succeeding?

#### Database

```bash
# Check WordPress database
docker compose exec wordpress wp db query "SELECT * FROM wp_users ORDER BY ID DESC LIMIT 10"

# Check for duplicates
docker compose exec wordpress wp db query "SELECT email, COUNT(*) FROM wp_users GROUP BY email HAVING COUNT(*) > 1"
```

- [ ] Data integrity issues?
- [ ] Duplicate records?
- [ ] Missing records?

---

### ☑️ Step 5: Root Cause Analysis

**Ask these questions:**

1. **When does it fail?**
   - [ ] First time only?
   - [ ] Every time?
   - [ ] Intermittently?
   - [ ] Under specific conditions?

2. **Where does it fail?**
   - [ ] Client-side (before network request)?
   - [ ] Network layer (request/response)?
   - [ ] Server-side (after request arrives)?
   - [ ] Database layer?

3. **Why does it fail?**
   - [ ] Race condition? (check PIDs/timestamps)
   - [ ] Missing data? (validation issue)
   - [ ] Wrong data? (logic error)
   - [ ] External service? (Supabase, API)

4. **What is the FIRST point of failure?**
   - [ ] Don't fix symptoms!
   - [ ] Find the root cause

**Common Root Causes:**

- **Race Condition**: Different PIDs, similar timestamps
- **Validation Error**: Missing/invalid data in logs
- **Configuration Error**: Wrong settings, missing env vars
- **External Service**: Supabase/API errors
- **Logic Error**: Wrong flow, incorrect assumptions

---

### ☑️ Step 6: Design Solution

**Match solution to problem layer:**

| Problem Layer | Solution Layer | Example |
|---------------|----------------|---------|
| Frontend | JavaScript | Add validation, fix event handlers |
| Network | Frontend + Backend | Add retries, improve error handling |
| Backend | PHP | Add locks, fix logic, validation |
| Database | PHP + DB | Add constraints, fix queries |

**Solution Checklist:**

- [ ] Does solution address ROOT CAUSE (not symptoms)?
- [ ] Will it work across all layers?
- [ ] Are there edge cases?
- [ ] Can it cause new problems?
- [ ] Is it testable?

**Anti-Patterns (AVOID):**

- ❌ Fixing client-side for server-side race condition
- ❌ Adding retry logic for data integrity issues
- ❌ Hiding errors instead of fixing them
- ❌ Solving without understanding root cause

---

### ☑️ Step 7: Verify Solution

**Test systematically:**

```bash
# 1. Clear test data
# (e.g., delete test users, clear cache)

# 2. Start log monitoring
docker compose logs wordpress --follow

# 3. Reproduce issue
# (follow exact steps from Step 2)

# 4. Check logs for success/failure
# Look for expected log messages

# 5. Verify result
# (e.g., check WordPress admin, database)
```

**Verification Checklist:**

- [ ] Issue resolved in clean environment?
- [ ] Logs show expected behavior?
- [ ] No new errors introduced?
- [ ] Edge cases handled?
- [ ] User confirms fix?

---

### ☑️ Step 8: Document Solution

**Update TROUBLESHOOTING.md:**

```markdown
## PROBLEM-XXX: [Brief Description]

**Status**: ✅ RESOLVED
**Date Identified**: YYYY-MM-DD
**Date Resolved**: YYYY-MM-DD
**Severity**: [LOW/MEDIUM/HIGH/CRITICAL]

### Symptoms
- [What user experienced]

### How to Diagnose
```bash
# [Exact commands to run]
```

### Root Cause
**Level**: [Frontend/Backend/Database]
**Component**: [File/module]

[Technical explanation]

### Solution (VERIFIED)
[Detailed solution with code snippets]

### What DOESN'T Work
[Document failed attempts and WHY]

### Verification Steps
[How to verify fix]

### Code References
- [File paths and line numbers]
```

**Documentation Checklist:**

- [ ] Added to TROUBLESHOOTING.md?
- [ ] Included diagnostic commands?
- [ ] Documented what DOESN'T work?
- [ ] Added code references?
- [ ] Updated BACKLOG.md if needed?

---

## Quick Decision Tree

```
Issue Reported
    ↓
Check TROUBLESHOOTING.md
    ↓
┌─────────────────────┐
│ Issue documented?   │
└─────────────────────┘
    ↓           ↓
  YES          NO
    ↓           ↓
Follow       Check Logs
solution      ↓
              Identify Layer
              ↓
        ┌─────────────────┐
        │ What layer?     │
        └─────────────────┘
           ↓   ↓   ↓   ↓
        Front Net Back DB
           ↓   ↓   ↓   ↓
        Design Solution
           ↓
        Test & Verify
           ↓
        Document
```

---

## Common Issues Quick Reference

### Issue: Duplicate Users Created

**Check:**
```bash
docker compose logs wordpress | grep "Supabase Bridge"
# Look for different PIDs with same email
```

**Root Cause**: Server-side race condition
**Solution**: See TROUBLESHOOTING.md → PROBLEM-001
**Don't Try**: Client-side locks (won't work!)

---

### Issue: JavaScript Not Loading

**Check:**
```bash
# Browser console
# Look for: 404 errors, CORS errors, CSP violations
```

**Root Cause**: Usually CSP or file path issue
**Solution**: Check TROUBLESHOOTING.md for CSP fixes
**Don't Try**: Changing file paths without checking actual error

---

### Issue: Authentication Fails

**Check:**
```bash
docker compose logs wordpress | grep "Authentication failed"
# Check error message for specific reason
```

**Root Cause**: JWT validation, email verification, or Supabase config
**Solution**: Check specific error in logs
**Don't Try**: Disabling security checks

---

### Issue: Redirect to Wrong Page

**Check:**
```bash
# Browser DevTools → Network → Headers
# Check Location header in 302 redirect
```

**Root Cause**: Usually hardcoded URL or wrong Settings value
**Solution**: Check dynamic URL injection
**Don't Try**: Hardcoding different URL (fixes symptom, not cause)

---

## Time-Saving Tips

### ⚡ Tip #1: Logs First, Code Second

- Spend 2 minutes checking logs
- Save 20 minutes fixing wrong thing
- **Always check logs BEFORE proposing solution**

### ⚡ Tip #2: Reproduce First, Debug Second

- Can't fix what you can't reproduce
- Reproduce in clean environment
- Document exact steps

### ⚡ Tip #3: One Layer at a Time

- Don't jump between layers
- Fix frontend OR backend, not both
- Verify each fix separately

### ⚡ Tip #4: Document Failures Too

- "Solution X doesn't work" is valuable info
- Saves time in next session
- Prevents repeating mistakes

### ⚡ Tip #5: Trust the Data, Not Assumptions

- Logs don't lie
- PIDs show separate processes
- Timestamps show timing issues

---

**Last Updated**: 2025-10-24
**Maintainer**: AI Agent following CLAUDE.md protocol
