# AI Agent Instructions

**Project:** Supabase Bridge for WordPress
**Purpose:** Meta-instructions for effective AI-assisted development
**Created:** 2025-10-01
**Last Updated:** 2025-10-01

> **Note:** This file is optimized for AI assistants (Claude, Cursor, Copilot, etc.) working with this codebase.

---

## 🎯 Quick Start for AI Agents

### Project Overview
**Supabase Bridge** is a minimal WordPress plugin that enables Supabase Auth as the primary authentication system for WordPress. It creates a seamless bridge between Supabase's modern authentication (OAuth, email/password, magic links) and WordPress's user system.

### Required Reading (in order):
1. **README.md** - Full project documentation and usage
2. **FLOW.md** - Authentication flow visualization (Mermaid diagram)
3. **ARCHITECTURE.md** - Technical architecture and design decisions
4. **BACKLOG.md** - Implementation status and roadmap

### Key Files Quick Reference:
```bash
# Core Plugin
supabase-bridge.php          # Main plugin file (all logic)
composer.json                # PHP dependencies (firebase/php-jwt)
vendor/                      # Auto-loaded dependencies

# Documentation
README.md                    # User-facing documentation
FLOW.md                      # Authentication flow diagram
ARCHITECTURE.md              # Technical architecture
BACKLOG.md                   # Roadmap and status
AGENTS.md (this file)        # AI agent instructions

# UI Examples (reference)
htmlblock.html               # Callback page HTML example
button.html                  # Login button example
```

---

## 📚 Technology Stack

### Backend
- **Platform:** WordPress 5.0+
- **Language:** PHP 7.4+
- **Dependencies:**
  - `firebase/php-jwt` v6.10+ (JWT verification)
- **WordPress APIs Used:**
  - REST API (`rest_api_init`)
  - User API (`wp_create_user`, `get_user_by`)
  - Auth API (`wp_set_auth_cookie`)
  - Script enqueue (`wp_enqueue_scripts`)

### Frontend
- **Library:** Supabase JS SDK v2 (CDN)
- **Integration:** Vanilla JavaScript (no framework required)
- **Authentication Flow:** OAuth 2.0 / OIDC

### External Services
- **Supabase Auth:** Primary identity provider
- **JWKS Endpoint:** `.well-known/jwks.json` for JWT verification
- **OAuth Providers:** Google, Apple, Facebook, GitHub, etc. (configured in Supabase)

---

## 🚫 NEVER DO

### Code & Architecture
- ❌ **Store service_role key** in frontend or plugin code (security risk)
- ❌ **Trust JWT without verification** (always verify via JWKS)
- ❌ **Hardcode Supabase credentials** in PHP files (use environment variables)
- ❌ **Skip email verification check** (unless intentionally allowing unverified users)
- ❌ **Use plain text passwords** in WordPress (use `wp_generate_password()`)
- ❌ **Create duplicate WordPress users** (check by email first)
- ❌ **Expose JWKS URL** if it can be user-controlled (XSS risk)

### WordPress Best Practices
- ❌ **Skip sanitization** of user inputs (`sanitize_email`, `sanitize_text_field`)
- ❌ **Use SQL directly** (use WordPress database abstraction)
- ❌ **Forget nonce verification** for state-changing operations
- ❌ **Skip permission checks** in REST endpoints
- ❌ **Ignore WordPress coding standards**

### Security
- ❌ **Log sensitive data** (JWT tokens, passwords) in plain text
- ❌ **Allow expired JWTs** (always check `exp` claim)
- ❌ **Trust `iss` claim** without validation
- ❌ **Skip SSL verification** in production environments

---

## ✅ ALWAYS DO

### Before Making Changes
- ✅ **Read README.md** for plugin functionality
- ✅ **Check ARCHITECTURE.md** for design decisions
- ✅ **Review BACKLOG.md** for planned features
- ✅ **Test in local WordPress** environment first

### During Development
- ✅ **Use environment variables** for all configuration (via `getenv()`)
- ✅ **Validate JWT claims** (iss, exp, email, sub, email_verified)
- ✅ **Sanitize all inputs** from REST API and user data
- ✅ **Follow WordPress coding standards** (naming, hooks, etc.)
- ✅ **Use WordPress APIs** instead of direct database access
- ✅ **Handle errors gracefully** with `WP_Error` objects
- ✅ **Test with multiple OAuth providers** (not just one)

### After Completion
- ✅ **Update BACKLOG.md** with implementation status
- ✅ **Update ARCHITECTURE.md** if design changed
- ✅ **Update README.md** if user-facing changes
- ✅ **Test callback flow** end-to-end
- ✅ **Verify security** (JWT validation, sanitization)
- ✅ **Create meaningful git commit** with clear message

---

## 🔧 Standard Workflows

### Adding New OAuth Provider
```
1. Configuration → Add provider in Supabase Dashboard
2. Documentation → Update README.md with provider name
3. Testing → Test signInWithOAuth() for new provider
4. No code changes needed (plugin is provider-agnostic)
```

### Modifying JWT Validation
```
1. Review → Check JWT claims structure in Supabase docs
2. Update → Modify sb_handle_callback() function
3. Test → Verify with real JWT from Supabase
4. Security → Ensure no validation is skipped
5. Document → Update ARCHITECTURE.md with reasoning
```

### Adding New REST Endpoint
```
1. Register → Use register_rest_route() in rest_api_init hook
2. Permissions → Set appropriate permission_callback
3. Validation → Sanitize all inputs
4. Error Handling → Return WP_Error for failures
5. Testing → Test with various inputs (valid, invalid, edge cases)
6. Documentation → Add to README.md API section
```

### Environment Configuration Change
```
1. Update → Add new getenv() call in sb_cfg()
2. Document → Add to README.md setup instructions
3. Example → Provide example in wp-config.php format
4. Fallback → Provide sensible default if applicable
5. Validation → Check for missing config and return errors
```

---

## 🏗️ Architectural Patterns

### JWT Verification Pattern
**Decision:** Server-side JWT verification via JWKS
**Reason:**
- Client cannot forge JWTs (server validates signature)
- No secret key stored in WordPress (fetched from public JWKS endpoint)
- Standard OAuth/OIDC flow
- Automatic key rotation support

**Implementation:**
```php
// 1. Fetch JWKS from Supabase
$jwks = "{$issuer}/.well-known/jwks.json";
$keys = json_decode(wp_remote_retrieve_body(wp_remote_get($jwks)), true);

// 2. Parse keys and decode JWT
$publicKeys = \Firebase\JWT\JWK::parseKeySet($keys);
$decoded = \Firebase\JWT\JWT::decode($jwt, $publicKeys);

// 3. Validate claims
if ($decoded->iss !== $expectedIssuer) throw new Exception('Bad iss');
if (time() >= $decoded->exp) throw new Exception('Expired');
```

### User Synchronization Pattern
**Decision:** Create WordPress users on first login, update metadata on subsequent logins
**Reason:**
- WordPress plugins/themes expect wp_users records
- RLS policies in Supabase use auth.uid() for user identification
- Metadata sync keeps systems consistent

**Data Flow:**
```
Supabase User → JWT Claims → WordPress User
   ├─ sub (UUID) → usermeta: supabase_user_id
   ├─ email → wp_users: user_email
   └─ email_verified → verification check
```

### Configuration Management Pattern
**Decision:** Environment variables via `getenv()`, not database options
**Reason:**
- Security: No credentials in database dumps
- Portability: Same code, different configs per environment
- WordPress best practice: Use wp-config.php for sensitive data
- Version control: No secrets committed to git

---

## 🐛 Common Issues & Solutions

### Issue: "SUPABASE_PROJECT_REF not set" Error
**Symptom:** 500 error on callback endpoint
**Root Cause:** Environment variables not configured in wp-config.php

**Solution:** Add to `wp-config.php` **BEFORE** the line `/* That's all, stop editing! */`:
```php
// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=your-project-ref');  // e.g., 'abcdefghijk'
putenv('SUPABASE_URL=https://your-project-ref.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...');  // anon public key
```

**Where to find values:**
1. Go to https://app.supabase.com → Your Project
2. **Settings** → **API**
3. Copy **Project URL** and **anon public** key

**Reference:** See `wp-config-supabase-example.php` for complete example

**File:** `wp-config.php` (WordPress root directory)

### Issue: "JWT verify failed: Email not verified"
**Symptom:** Login fails after OAuth callback
**Root Cause:** Supabase user hasn't confirmed email
**Solution:** Either:
1. Enable email confirmation in Supabase Dashboard
2. Remove email_verified check in `sb_handle_callback()` (line 72-74)
**File:** `supabase-bridge.php:72-74`

### Issue: Infinite Redirect Loop
**Symptom:** Callback page keeps reloading
**Root Cause:** JavaScript trying to re-login on already logged-in state
**Solution:** Check session state before calling `signInWithOAuth()`:
```javascript
const { data: { session } } = await sb.auth.getSession();
if (session) {
  window.location.href = '/account/'; // Already logged in
}
```

### Issue: "No session" After OAuth
**Symptom:** `getSession()` returns null on callback page
**Root Cause:** Supabase JS not initialized before OAuth redirect
**Solution:** Ensure Supabase JS CDN loaded and client created:
```html
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
  const { createClient } = window.supabase;
  const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);
</script>
```

### Issue: CORS Errors on JWKS Fetch
**Symptom:** `wp_remote_get()` fails to fetch JWKS
**Root Cause:** Server firewall blocking external requests
**Solution:**
1. Check server outbound connection settings
2. Whitelist `*.supabase.co` domain
3. Use `wp_remote_get()` with increased timeout
**File:** `supabase-bridge.php:57`

---

## 📋 Task Checklists

### Adding New Claim to JWT Validation
- [ ] Identify claim name from Supabase JWT structure
- [ ] Add validation in `sb_handle_callback()` after line 69
- [ ] Handle missing claim gracefully (throw exception or default)
- [ ] Test with real JWT containing claim
- [ ] Test with JWT missing claim
- [ ] Update `ARCHITECTURE.md` with new validation rule
- [ ] Update `README.md` security section

### Creating New REST Endpoint
- [ ] Plan endpoint purpose and data flow
- [ ] Register route with `register_rest_route()` in `rest_api_init` hook
- [ ] Set `permission_callback` (authenticated, public, custom check)
- [ ] Implement callback function with input validation
- [ ] Use `sanitize_*` functions for all inputs
- [ ] Return data with `wp_send_json()` or `WP_Error`
- [ ] Test endpoint with various HTTP methods
- [ ] Add to `README.md` API documentation section

### Implementing Role Mapping (Supabase → WordPress)
- [ ] Design role mapping logic (claims to WP roles)
- [ ] Fetch role from JWT claims (custom claim or metadata)
- [ ] Map Supabase role to WordPress role (subscriber, editor, etc.)
- [ ] Update user role with `$user->set_role()`
- [ ] Handle role changes on subsequent logins
- [ ] Test with multiple role scenarios
- [ ] Document mapping logic in `ARCHITECTURE.md`
- [ ] Update `README.md` with role mapping feature

### Adding Logout Functionality
- [ ] Understand current logout endpoint (`/supabase-auth/logout`)
- [ ] Implement Supabase logout in JavaScript (`sb.auth.signOut()`)
- [ ] Call WordPress logout endpoint after Supabase logout
- [ ] Clear both Supabase session and WordPress cookies
- [ ] Redirect to appropriate page (homepage, login page)
- [ ] Test logout from both systems
- [ ] Add logout button example to `README.md`

---

## 🔍 Debugging Quick Reference

### WordPress Issues
```php
// Enable WordPress debug mode (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check debug.log
tail -f wp-content/debug.log

// Check user metadata
$user_id = 123;
get_user_meta($user_id, 'supabase_user_id', true);

// Check if user exists
$user = get_user_by('email', 'test@example.com');
var_dump($user);
```

### Supabase Auth Issues
```javascript
// Check session in browser console
const { data: { session } } = await sb.auth.getSession();
console.log(session);

// Check user metadata
const { data: { user } } = await sb.auth.getUser();
console.log(user);

// Test OAuth configuration
await sb.auth.signInWithOAuth({
  provider: 'google',
  options: {
    redirectTo: window.location.origin + '/callback/'
  }
});
```

### JWT Debugging
```php
// Decode JWT without verification (debugging only!)
$parts = explode('.', $jwt);
$payload = json_decode(base64_decode($parts[1]), true);
error_log(print_r($payload, true));

// Check JWKS endpoint
$jwks_url = "https://your-project.supabase.co/auth/v1/.well-known/jwks.json";
$response = wp_remote_get($jwks_url);
error_log(wp_remote_retrieve_body($response));
```

### Network Issues
```bash
# Test outbound connection from server
curl https://your-project.supabase.co/auth/v1/.well-known/jwks.json

# Check WordPress REST API
curl https://your-site.com/wp-json/supabase-auth/callback \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"access_token": "your-jwt-token"}'
```

---

## 📊 Performance Guidelines

### WordPress Plugin Performance
- **Script Loading:** Only load Supabase JS on frontend (not admin)
- **JWKS Caching:** Consider caching JWKS response (15 min TTL)
- **Database Queries:** Minimize user lookups (use email index)
- **Error Logging:** Log errors, but avoid verbose logging in production

### Security Hardening
- **JWT Expiration:** Always validate `exp` claim (default 1 hour)
- **SSL Enforcement:** Require HTTPS for callback endpoints
- **Rate Limiting:** Consider rate limiting on callback endpoint
- **CORS:** Restrict callback endpoint to specific origins if possible

### Scalability Considerations
- **User Creation:** Batch user creation if migrating existing Supabase users
- **Metadata Updates:** Use `update_user_meta()` sparingly (triggers hooks)
- **Session Management:** WordPress session cookies scale to thousands of users
- **JWKS Fetching:** Cache JWKS to avoid rate limits on Supabase

---

## 🚀 Code Templates

### New REST Endpoint Template
```php
add_action('rest_api_init', function () {
  register_rest_route('supabase-auth', '/new-endpoint', [
    'methods'  => 'POST',
    'permission_callback' => function() {
      // Return true for public, or check is_user_logged_in()
      return true;
    },
    'callback' => 'sb_handle_new_endpoint',
  ]);
});

function sb_handle_new_endpoint(\WP_REST_Request $req) {
  // Get and validate parameters
  $param = $req->get_param('param_name');
  if (!$param) {
    return new \WP_Error('missing_param', 'Parameter required', ['status' => 400]);
  }

  // Sanitize input
  $param = sanitize_text_field($param);

  try {
    // Your logic here
    $result = do_something($param);
    return ['success' => true, 'data' => $result];
  } catch (\Exception $e) {
    return new \WP_Error('error', $e->getMessage(), ['status' => 500]);
  }
}
```

### Custom JWT Claim Validation
```php
// Add to sb_handle_callback() after line 69
if (isset($claims['custom_claim'])) {
  $custom_value = sanitize_text_field($claims['custom_claim']);
  update_user_meta($user->ID, 'custom_meta_key', $custom_value);
}

// Validate specific claim value
if (($claims['app_metadata']['role'] ?? '') !== 'allowed_role') {
  throw new Exception('User role not authorized');
}
```

### Role Mapping from JWT
```php
// Add to sb_handle_callback() after user creation/fetch (line 87)
$supabase_role = $claims['app_metadata']['role'] ?? 'subscriber';
$wp_role_map = [
  'admin' => 'administrator',
  'editor' => 'editor',
  'user' => 'subscriber',
];
$wp_role = $wp_role_map[$supabase_role] ?? 'subscriber';
$user->set_role($wp_role);
```

### Frontend OAuth Button with Error Handling
```javascript
document.getElementById('login-google').onclick = async () => {
  try {
    const { createClient } = window.supabase;
    const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);

    const { data, error } = await sb.auth.signInWithOAuth({
      provider: 'google',
      options: {
        redirectTo: window.location.origin + '/supabase-callback/',
        scopes: 'email profile' // Optional: request specific scopes
      }
    });

    if (error) {
      console.error('OAuth error:', error);
      alert('Login failed: ' + error.message);
    }
  } catch (err) {
    console.error('Unexpected error:', err);
    alert('Login failed. Please try again.');
  }
};
```

### Callback Page with Custom Redirect
```javascript
// In callback page (supabase-callback)
(async () => {
  const { createClient } = window.supabase;
  const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);

  const { data: { session }, error } = await sb.auth.getSession();
  if (error || !session) {
    document.getElementById("sb-status").textContent = "No session";
    return;
  }

  // Send JWT to WordPress
  const response = await fetch('/wp-json/supabase-auth/callback', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      access_token: session.access_token,
      user: session.user
    })
  });

  if (response.ok) {
    // ✅ Redirect to custom page (thank-you, dashboard, etc.)
    // Common patterns:
    // - '/account/' - User dashboard
    // - '/thank-you/' or '/registr/' - Welcome page with instructions
    // - '/dashboard/' - Member area
    // - External: 'https://example.com/welcome/'
    window.location.href = '/thank-you/'; // Customize as needed
  } else {
    document.getElementById("sb-status").textContent = "Server verify failed";
  }
})();
```

---

## 📝 Sprint Workflow

### Sprint Structure
```
🎯 SPRINT START
├── User request: "Add feature X"
├── Planning (TodoWrite)
├── Implementation
├── Testing (local WordPress + Supabase)
├── Documentation updates
└── Git commit

📋 SPRINT COMPLETION CHECKLIST
├── Update BACKLOG.md (feature status)
├── Update ARCHITECTURE.md (if architectural change)
├── Update README.md (if user-facing change)
├── Update AGENTS.md (if new patterns/issues discovered)
├── Test end-to-end authentication flow
└── 🎉 Commit with meaningful message
```

### Commit Message Template
```
feat: [Brief description of feature]

- Implemented: [main functionality]
- Updated: [documentation files]
- Fixed: [any bugs encountered]
- Tested: [testing performed]

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

---

## 🔄 Version History

- **2025-10-01:** Created AGENTS.md for Supabase Bridge WordPress plugin
- Future updates tracked here

---

*This file should be updated after every sprint completion*
*Goal: Maintain living documentation for effective AI-assisted development*
*Compatible with: Claude Code, Cursor, GitHub Copilot, and other AI coding assistants*
