# Project Backlog

**Project:** Supabase Bridge (Auth) for WordPress
**Version:** 0.4.0
**Last Updated:** 2025-10-24

---

## 🎯 Recent Updates

### 2025-10-24 - Release v0.4.0 - Shortcode & Settings Page
**Status:** ✅ Complete
**Description:** Major UX improvements - replaced manual code copy/paste with shortcode and Settings UI
**Details:**
- Implemented `[supabase_auth_form]` shortcode
- Added Settings page with Thank You Page selector
- Encrypted credentials storage in database (AES-256-CBC)
- Real-time credentials verification via API
- Auto-extraction of Project Ref from Supabase URL
- Simplified setup from 7 steps to 4 steps
- Resolved Issues #3, #4, #5, #6, #7 (see Known Issues section)

### 2025-10-23 - Migrated to Claude Code Starter v1.2.4
**Status:** ✅ Complete
**Description:** Successfully migrated legacy documentation to structured framework
**Details:**
- All legacy files archived to `archive/`
- Documentation restructured to Init/ framework
- Single source of truth established
- See `archive/MIGRATION_REPORT.md` for full report
- Content from docs/BACKLOG.md migrated to this file

---

> **📋 Authoritative Source:** This is the SINGLE SOURCE OF TRUTH for:
> - ✅ **Detailed implementation plan** with checklists
> - ✅ **Current status** of all features (TODO/IN PROGRESS/DONE)
> - ✅ **Sprint roadmap** and task breakdown
>
> **⚠️ NOT in ARCHITECTURE.md:**
> ARCHITECTURE.md explains WHY (technology choices, design principles).
> THIS file contains WHAT to do (tasks, checklists, status).
>
> **For AI Agents:**
> When user asks for checklist or "what's next?" → Read THIS file, not ARCHITECTURE.md
>
> **📋 После завершения каждой фазы:**
> - Обнови этот файл согласно [`PROCESS.md`](./PROCESS.md)
> - Обнови [`PROJECT_SNAPSHOT.md`](./PROJECT_SNAPSHOT.md) с текущим прогрессом
> - См. [`DEVELOPMENT_PLAN_TEMPLATE.md`](./DEVELOPMENT_PLAN_TEMPLATE.md) для методологии планирования
>
> All AI agents and developers MUST check this file before starting work.

---

## 📊 Project Status Overview

**Current Phase:** Production
**Active Sprint:** Maintenance & Enhancements
**Completion:** 100% of MVP features + 5 critical issues resolved

### Quick Stats
- ✅ **Completed:** 16 core features (v0.1.0 - v0.4.0)
- 🚧 **In Progress:** 0 features
- 📋 **Planned:** 12 features (v0.2.0-v1.3.0)
- 🔴 **Blocked:** 0 features
- ✅ **Issues Resolved:** 5 critical (v0.4.0)

---

## 📋 Current Implementation Status

### ✅ Version 0.4.0 - Shortcode & Settings Page (Current)
**Released:** 2025-10-24
**Status:** Production Ready ✨

#### Implemented Features
- [x] **[supabase_auth_form] Shortcode** - WordPress shortcode for auth form embedding
  - Implemented: 2025-10-24
  - Files: `supabase-bridge.php` (lines 122-130)
  - Notes: Replaces manual 1068-line code copy/paste with simple shortcode
  - Usage: Just insert `[supabase_auth_form]` in any page/post
  - Benefits: Works with all page builders (Gutenberg, Elementor, Divi, etc.)
  - Impact: Setup reduced from 7 steps to 4 steps

- [x] **Settings Page with Page Selector** - WordPress Admin UI for plugin configuration
  - Implemented: 2025-10-24
  - Files: `supabase-bridge.php` (lines 414-584)
  - Notes: Modern WordPress Settings page with dropdown selectors
  - Features: Thank You Page selector, encrypted credentials storage
  - Benefits: No manual file editing, no FTP access required
  - Impact: Follows modern WordPress plugin standards (like WooCommerce, Jetpack)

- [x] **Encrypted Credentials Storage** - AES-256-CBC encryption for Supabase credentials
  - Implemented: 2025-10-24
  - Files: `supabase-bridge.php` (lines 77-90)
  - Notes: Credentials stored encrypted in wp_options table, not plaintext in wp-config.php
  - Encryption: AES-256-CBC using WordPress salts as encryption key
  - Functions: sb_encrypt(), sb_decrypt()
  - Benefits: Secure credential storage, no Git leaks, easy key rotation
  - Impact: Resolves critical security issue (Issue #5)

- [x] **Real-time Credentials Verification** - API call to verify credentials when saving Settings
  - Implemented: 2025-10-24
  - Files: `supabase-bridge.php` (lines 44-75)
  - Notes: Makes HTTP request to Supabase auth/v1/settings endpoint
  - Endpoint: {SUPABASE_URL}/auth/v1/settings
  - Validation: URL format check, API connectivity test, HTTP 200 response
  - UX: Success/error message displayed inline below credentials form
  - Benefits: Immediate feedback, prevents misconfiguration
  - Impact: Reduces support burden from invalid credentials

- [x] **Auto-extract Project Ref from URL** - Automatic parsing of project ref from Supabase URL
  - Implemented: 2025-10-24
  - Files: `supabase-bridge.php` (lines 94-106)
  - Notes: Regex extraction from URL pattern (https://PROJECT_REF.supabase.co)
  - Regex: `/https?:\/\/([^.]+)\.supabase\.co/`
  - Benefits: Removes redundant manual input field, reduces user errors
  - Impact: One less field to configure in Settings

- [x] **Simplified Setup Instructions** - Flat 4-step instructions instead of nested 7-step process
  - Implemented: 2025-10-24
  - Files: `supabase-bridge.php` (lines 495-584)
  - Notes: Removed nested numbering (2.1, 2.2, 3.1), converted to flat Step 1-4
  - Structure: Step 1 (Configure), Step 2 (Shortcode), Step 3 (Supabase), Step 4 (Test)
  - Benefits: Clearer instructions, less cognitive load
  - User feedback: "Людям надо проще" - mission accomplished!

- [x] **Inline Verification Messages** - Success/error messages displayed below credentials form
  - Implemented: 2025-10-24
  - Files: `supabase-bridge.php` (lines 498-502)
  - Notes: Green for success, red for error, shown immediately after credentials section
  - Benefits: Better UX - no scrolling to top of page to see message
  - Impact: Professional WordPress admin UX

#### UX Improvements
- ✅ **Setup complexity:** 7 steps → 4 steps (43% reduction)
- ✅ **Code copy/paste:** 1068 lines → 0 lines (replaced with shortcode)
- ✅ **Manual file editing:** Required (wp-config.php) → Optional (Settings UI)
- ✅ **FTP access requirement:** Required → Not required
- ✅ **Security:** Plaintext credentials → AES-256-CBC encrypted

#### Issues Resolved
- ✅ **Issue #3** - Poor UX (auth-form.html code not embedded) → Resolved via shortcode
- ✅ **Issue #4** - Settings page with page selector → Resolved
- ✅ **Issue #5** - Credentials in plaintext → Resolved via encrypted storage
- ✅ **Issue #6** - Confusing auth-form.html structure → Resolved (no longer copying code)
- ✅ **Issue #7** - Thank You page URL configuration → Resolved via Settings page dropdown

#### Testing Results
- ✅ Shortcode: Rendering correctly in Gutenberg
- ✅ Settings: Page selector working, credentials encrypted
- ✅ Verification: API validation working with success/error messages
- ✅ Backward compatibility: wp-config.php credentials still supported as fallback

---

### ✅ Version 0.3.5 - OAuth & Magic Link Fixes
**Released:** 2025-10-23
**Status:** Production Ready ✨

#### Implemented Features
- [x] **OAuth Email Verification Fix** - Allow NULL email_verified for OAuth providers
  - Implemented: 2025-10-23
  - Files: `supabase-bridge.php` (lines 181-197)
  - Notes: Google/Facebook OAuth don't always send email_verified field. Now only blocks if explicitly false.
  - Logic: email_verified=true ✅, email_verified=NULL ✅, email_verified=false ❌
  - Impact: Google OAuth now works correctly without "Email verification required" error

- [x] **Magic Link localStorage Fix** - Move token deduplication after WordPress response
  - Implemented: 2025-10-23
  - Files: `auth-form.html` (lines 938-970)
  - Notes: Previously marked token as processed BEFORE WordPress request completed
  - Impact: If WordPress request failed, token couldn't be retried. Now allows legitimate retries.
  - Result: 100% success rate on Magic Link registration tested with 3 different emails

#### Bug Fixes
- 🐛 **Fixed:** Google OAuth failing with "Email verification required" despite successful authentication
  - Root cause: `email_verified` field missing (NULL) in JWT from Google
  - Solution: Only block if `email_verified === false`, allow NULL/missing values

- 🐛 **Fixed:** Second Magic Link registration failing to create WordPress user
  - Root cause: localStorage marked token as processed before WordPress response
  - Solution: Move localStorage.setItem() to AFTER successful WordPress callback
  - Testing: Verified with 3 different email addresses - all working

#### Testing Results
- ✅ Magic Link: Registration working perfectly (tested 3 emails)
- ✅ Magic Link: Login working perfectly
- ✅ Google OAuth: Registration and login working
- ✅ No duplicate users created (race condition fixed)
- ✅ Proper redirects: New users → `/thankyoureg/`, Existing users → `/`

---

### ✅ Version 0.3.3 - Enhanced Security & Hardening
**Released:** 2025-10-07
**Status:** Production Ready 🛡️

#### Implemented Features
- [x] **HTTP Security Headers** - CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php` (lines 15-42)
  - Notes: Strict CSP allows only Supabase CDN and same-origin

- [x] **Enhanced Error Handling** - Generic user messages, detailed server logs
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php`
  - Notes: No information leakage to frontend, full audit trail in error_log

- [x] **Audit Logging** - Complete audit trail for all auth events
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php`
  - Notes: Success/failure/logout with IP tracking

- [x] **Improved JWT Validation** - Better error messages, SSL verification for JWKS
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php`
  - Notes: Status code checks, enhanced error handling

- [x] **Stronger Passwords** - 32 characters with high complexity
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php`
  - Notes: wp_generate_password(32, true, true)

- [x] **Enhanced Email Validation** - is_email() check + sanitize_email()
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php`
  - Notes: RFC 5322 compliance

- [x] **Default User Roles** - Automatically assign 'subscriber' role
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php`
  - Notes: All new users get subscriber role

- [x] **Rate Limit Clearing** - Clear rate limit on successful auth
  - Implemented: 2025-10-07
  - Files: `supabase-bridge.php`
  - Notes: delete_transient() after successful login

- [x] **Composer Metadata** - MIT license and author information
  - Implemented: 2025-10-07
  - Files: `composer.json`
  - Notes: Proper package metadata

- [x] **SECURITY.md** - Comprehensive security documentation
  - Implemented: 2025-10-07
  - Files: `SECURITY.md` (legacy), `Init/SECURITY.md` (current)
  - Notes: Full security policy documented

- [x] **Dependencies Updated** - Composer audit passed
  - Implemented: 2025-10-07
  - Files: `composer.lock`
  - Notes: 0 vulnerabilities

---

### ✅ Version 0.3.2 - Security Hotfix
**Released:** 2025-10-05
**Status:** Complete

#### Implemented Features
- [x] **CRITICAL: Origin/Referer Bypass Fix** - Strict host comparison
  - Implemented: 2025-10-05
  - Files: `supabase-bridge.php`
  - Notes: Replaced strpos() with exact host matching

- [x] **CSRF Protection for Logout** - Origin validation on logout endpoint
  - Implemented: 2025-10-05
  - Files: `supabase-bridge.php`
  - Notes: Prevents CSRF on logout

---

### ✅ Version 0.3.1 - Security Update
**Released:** 2025-10-05
**Status:** Complete

#### Implemented Features
- [x] **CSRF Protection** - Origin/Referer header validation
- [x] **JWT aud Validation** - Mandatory audience claim check
- [x] **Email Verification** - Strict email_verified=true check
- [x] **Open Redirect Protection** - Same-origin validation
- [x] **JWKS Caching** - 1-hour cache for public keys
- [x] **Rate Limiting** - 10 attempts/60s per IP
- [x] **PHP >=8.0 Requirement** - Updated minimum version
- [x] **.gitignore** - Protect wp-config files

---

### ✅ Version 0.3.0 - Multi-Provider Authentication
**Released:** 2025-10-05
**Status:** Complete

#### Implemented Features
- [x] **Google OAuth** - Tested and working
- [x] **Facebook OAuth** - Advanced access for email scope
- [x] **Magic Link (Passwordless)** - Email + 6-digit code
- [x] **Smart Redirects** - New vs existing user detection
- [x] **3 Redirect Modes** - Standard, paired, flexible
- [x] **Ready-to-use Form** - auth-form.html with all 3 methods
- [x] **Documentation** - AUTH-FORM-REDIRECT-GUIDE.md

---

### ✅ Version 0.1.0 - Core Authentication
**Released:** 2025-10-01
**Status:** Complete

#### Implemented Features
- [x] **JWT Verification via JWKS** - Server-side JWT validation with Supabase public keys
  - Implemented: 2025-10-01
  - Files: `supabase-bridge.php`
  - Notes: RS256 signature verification

- [x] **WordPress User Synchronization** - Automatic user creation on first login
  - Implemented: 2025-10-01
  - Files: `supabase-bridge.php`
  - Notes: Creates mirror user in wp_users

- [x] **OAuth Provider Support** - Works with any Supabase-configured provider
  - Implemented: 2025-10-01
  - Files: `supabase-bridge.php`, `auth-form.html`
  - Notes: Google, Apple, GitHub, etc.

- [x] **REST API Endpoints** - `/callback` and `/logout` endpoints
  - Implemented: 2025-10-01
  - Files: `supabase-bridge.php`
  - Notes: /wp-json/supabase-auth/callback and /logout

- [x] **Configuration via Environment Variables** - Secure credential management
  - Implemented: 2025-10-01
  - Files: `wp-config.php` (user configuration)
  - Notes: putenv() in wp-config.php

- [x] **Supabase JS Integration** - CDN-loaded client library
  - Implemented: 2025-10-01
  - Files: `supabase-bridge.php`
  - Notes: jsdelivr.net CDN, config injected via wp_add_inline_script

- [x] **Session Management** - WordPress authentication cookies
  - Implemented: 2025-10-01
  - Files: `supabase-bridge.php`
  - Notes: wp_set_auth_cookie()

- [x] **User Metadata Storage** - supabase_user_id in wp_usermeta
  - Implemented: 2025-10-01
  - Files: `supabase-bridge.php`
  - Notes: Links WP user to Supabase user

#### Technical Details
- **Plugin Size:** 388 lines (single PHP file)
- **Dependencies:** `firebase/php-jwt` ^6.11.1
- **WordPress Hooks:** `wp_enqueue_scripts`, `rest_api_init`, `send_headers`
- **Database Tables:** `wp_users`, `wp_usermeta` (existing WordPress tables)

---

## 🚧 Planned Features

### High Priority

#### v0.2.0 - Role Mapping
**Priority:** High
**Estimated Effort:** Small
**Status:** Planned

**Description:** Map Supabase user roles to WordPress roles automatically

**Requirements:**
- Read role from JWT `app_metadata` or custom claim
- Map Supabase roles to WordPress roles (admin, editor, subscriber, etc.)
- Update user role on each login (handle role changes)
- Configurable role mapping (via filter hook or config)

**Technical Approach:**
```php
// In sb_handle_callback() after user creation
$supabase_role = $claims['app_metadata']['role'] ?? 'user';
$role_map = apply_filters('sb_role_map', [
  'admin' => 'administrator',
  'editor' => 'editor',
  'user' => 'subscriber'
]);
$user->set_role($role_map[$supabase_role] ?? 'subscriber');
```

**Benefits:**
- WordPress plugins/themes respect user capabilities
- Supabase as single source of truth for permissions
- Automatic permission sync across systems

---

#### v0.4.0 - User Metadata Sync
**Priority:** High
**Estimated Effort:** Medium
**Status:** Planned

**Description:** Sync additional user metadata from Supabase to WordPress

**Requirements:**
- Extract metadata from JWT `user_metadata` claim
- Map fields: `first_name`, `last_name`, `avatar_url`, etc.
- Store in WordPress usermeta table
- Handle missing/optional fields gracefully
- Support custom metadata mappings via filter

**Technical Approach:**
```php
// In sb_handle_callback()
if (isset($claims['user_metadata'])) {
  $metadata = $claims['user_metadata'];
  $field_map = apply_filters('sb_metadata_map', [
    'first_name' => 'first_name',
    'last_name' => 'last_name',
    'avatar_url' => 'sb_avatar_url'
  ]);

  foreach ($field_map as $sb_field => $wp_field) {
    if (isset($metadata[$sb_field])) {
      update_user_meta($user->ID, $wp_field, sanitize_text_field($metadata[$sb_field]));
    }
  }
}
```

**Benefits:**
- Rich user profiles in WordPress
- Avatar synced from OAuth providers
- Custom user data accessible to WordPress themes/plugins

---

### Medium Priority

#### v0.5.0 - Email/Password Authentication
**Priority:** Medium
**Estimated Effort:** Medium
**Status:** Planned

**Description:** Add support for native Supabase email/password login (not just OAuth)

**Requirements:**
- Frontend login form with email/password fields
- Use `supabase.auth.signInWithPassword()`
- Same callback flow as OAuth (JWT → WordPress)
- Registration form support (`signUp()`)
- Password reset flow (`resetPasswordForEmail()`)

**Technical Approach:**
```javascript
// Login form handler
const { data, error } = await sb.auth.signInWithPassword({
  email: email_input.value,
  password: password_input.value
});

if (data.session) {
  // Send JWT to WordPress callback (same as OAuth flow)
  await fetch('/wp-json/supabase-auth/callback', {
    method: 'POST',
    body: JSON.stringify({ access_token: data.session.access_token })
  });
}
```

**Benefits:**
- No dependency on external OAuth providers
- Traditional login UX for users who prefer it
- Password management handled by Supabase

---

#### v0.6.0 - Magic Link Authentication
**Priority:** Medium (Already implemented in v0.3.0!)
**Estimated Effort:** N/A
**Status:** ✅ Complete (v0.3.0)

**Note:** This feature was originally planned but has been completed in v0.3.0 with 6-digit code support.

---

#### v0.7.0 - Admin Settings Page
**Priority:** Medium
**Estimated Effort:** Medium
**Status:** Planned

**Description:** WordPress admin UI for non-sensitive plugin settings

**Requirements:**
- Settings page in WordPress admin (`admin_menu` hook)
- Configure: redirect URLs, default user role, button text
- **Keep credentials in wp-config.php** (not in database)
- Use WordPress Settings API for form handling
- Preview OAuth button styles

**Settings:**
- Callback page URL (default: `/supabase-callback/`)
- Post-login redirect URL (default: `/account/`)
- Default user role for new users
- Enable/disable email verification requirement
- Custom CSS for login buttons

**Benefits:**
- Easier configuration for non-technical users
- No PHP file editing required
- Preview settings before applying

---

#### v0.8.0 - Shortcodes for Login Buttons
**Priority:** Medium
**Estimated Effort:** Small
**Status:** Planned

**Description:** WordPress shortcodes for easy login button insertion

**Requirements:**
- `[supabase_login provider="google"]` shortcode
- Customizable button text and CSS classes
- Support multiple providers in one shortcode
- Automatic Supabase JS initialization
- Works in posts, pages, widgets

**Example Usage:**
```
[supabase_login provider="google" text="Sign in with Google" class="btn-primary"]
[supabase_login provider="apple" text="Sign in with Apple"]
[supabase_logout text="Sign Out" redirect="/"]
```

**Benefits:**
- No HTML/JavaScript knowledge required
- Easy for content editors to add login buttons
- Consistent button styling across site

---

### Low Priority

#### v1.0.0 - Multi-Provider Buttons
**Priority:** Low
**Estimated Effort:** Medium
**Status:** Planned

**Description:** Pre-built UI component with multiple OAuth provider buttons

**Requirements:**
- Single shortcode displays all configured providers
- Auto-detect available providers from Supabase project
- Responsive grid layout
- Provider icons (Google, Apple, Facebook, etc.)
- Customizable styling via CSS

**Example:**
```
[supabase_login_buttons layout="grid" columns="2"]
```

**Benefits:**
- Professional login UI out of the box
- No custom HTML needed
- Supports any provider configured in Supabase

---

#### v1.1.0 - Supabase Database Integration
**Priority:** Low
**Estimated Effort:** Large
**Status:** Planned

**Description:** Link WordPress users with Supabase database records via RLS

**Requirements:**
- Utility functions to query Supabase database
- Use `supabase_user_id` for RLS policy matching
- Helper functions for CRUD operations
- Error handling for database failures
- Documentation for RLS policy setup

**Example Usage:**
```php
// Get user's Supabase data
$user_id = get_current_user_id();
$supabase_data = sb_get_user_data($user_id, 'profiles');

// Supabase RLS policy:
// CREATE POLICY "Users can read own profile"
// ON profiles FOR SELECT
// USING (auth.uid()::text = (SELECT meta_value FROM wp_usermeta WHERE user_id = id AND meta_key = 'supabase_user_id'));
```

**Benefits:**
- Unified user data across WordPress and Supabase
- Secure data access via RLS policies
- Custom app data stored in Supabase, accessed from WordPress

---

#### v1.2.0 - SSO for Multiple WordPress Sites
**Priority:** Low
**Estimated Effort:** Large
**Status:** Planned

**Description:** Single Sign-On across multiple WordPress sites using same Supabase project

**Requirements:**
- Shared Supabase project across multiple WP installs
- Automatic session sync between sites
- User logged in on Site A is automatically logged in on Site B
- Logout propagates to all sites
- Domain/subdomain handling

**Technical Challenges:**
- Cookie domain configuration (`.example.com` for subdomains)
- Session state synchronization
- Logout event broadcasting

**Benefits:**
- True SSO experience for users
- Single user database across network
- Centralized authentication management

---

#### v1.3.0 - WP-CLI Commands
**Priority:** Low
**Estimated Effort:** Small
**Status:** Planned

**Description:** WP-CLI commands for plugin management

**Commands:**
```bash
# Sync existing WP users to Supabase
wp supabase sync-users

# Verify Supabase configuration
wp supabase test-connection

# Clear JWKS cache
wp supabase clear-cache

# Import users from CSV with Supabase creation
wp supabase import-users users.csv
```

**Benefits:**
- Easier migration of existing WordPress sites
- Automated testing and diagnostics
- Bulk user operations

---

## 🔄 Technical Debt

### High Priority

#### Code Quality
- [ ] **PHPStan analysis** - Static analysis for type safety
- [ ] **WordPress Coding Standards** - Full WPCS compliance check
- [ ] **Unit tests** - PHPUnit tests for JWT verification logic
- [ ] **Integration tests** - Test full OAuth flow with mock Supabase

---

### Medium Priority

#### Documentation
- [ ] **Video tutorial** - YouTube walkthrough of setup
- [ ] **Supabase RLS examples** - Common RLS policy patterns
- [ ] **Troubleshooting guide** - Common issues and solutions
- [ ] **Migration guide** - Moving from other auth plugins

---

### Low Priority

#### Performance
- [ ] **Lazy script loading** - Only load Supabase JS on login pages
- [ ] **Minified inline JS** - Reduce config injection size
- [ ] **Database query optimization** - Reduce `get_user_by()` calls

---

## 🐛 Known Issues

### Documentation Issues

#### Issue #1: Misleading `/registr/` slug documentation
**Severity:** Low (UX/Documentation)
**Status:** 🔴 Open
**Reported:** 2025-10-23
**Description:**
Setup page (`supabase-bridge.php:342`) states: "Создайте страницу с URL slug: `/registr/`" which creates impression that this slug is reserved/hardcoded.

**Reality:**
- Slug can be any URL (customizable)
- Hardcoded default exists in `auth-form.html:722`: `'default': '/registr/'`
- Custom redirect map allows full customization (see `AUTH-FORM-REDIRECT-GUIDE.md`)

**Impact:**
Users may incorrectly believe `/registr/` is a special reserved slug.

**Suggested Fix:**
Update setup instructions to clarify:
```
Создайте страницу благодарности (например: /welcome/, /thank-you/, или любой другой slug)
По умолчанию используется /registr/, но вы можете изменить это в auth-form.html
```

**Affected Files:**
- `supabase-bridge.php:342` (setup page)
- `supabase-bridge.php:369` (setup checklist)
- `auth-form.html:722` (hardcoded default)
- `docs/QUICKSTART.md`, `docs/INSTALL.md`, `docs/DEPLOYMENT.md`

---

#### Issue #2: Permalink structure mismatch between environments
**Severity:** Medium (Testing/Development)
**Status:** 🔴 Open
**Reported:** 2025-10-23
**Description:**
Production site uses "Day and name" permalink structure (`/%year%/%monthnum%/%day%/%postname%/`), but local Docker development environment defaults to "Plain" structure (`/?p=123`).

**Impact:**
- OAuth callback URLs have different structures
- Testing environment doesn't match production behavior
- Potential redirect issues during local testing
- URLs in Supabase redirect whitelist may not match

**Suggested Fix:**
Add to documentation (DEPLOYMENT.md, README.md):
```
⚠️ IMPORTANT: Ensure permalink structure matches production!
WordPress Admin → Settings → Permalinks → Select same structure as production
For OAuth testing, permalink structure MUST be identical across environments.
```

**Affected Areas:**
- OAuth redirect URLs
- Supabase redirect whitelist configuration
- Local development setup guide
- Testing procedures

---

#### Issue #3: Poor UX - auth-form.html code not embedded in setup instructions
**Severity:** High (UX/Usability)
**Priority:** 🔥 High
**Status:** ✅ Resolved in v0.4.0
**Reported:** 2025-10-23
**Resolved:** 2025-10-24
**Description:**
Setup page (`supabase-bridge.php:329`) instructs users to "вставьте код из файла `auth-form.html` в HTML виджет Elementor" but doesn't show the actual code.

**Current UX (terrible):**
1. Read setup page in WP Admin
2. See "insert code from auth-form.html"
3. Open FTP/file manager
4. Navigate to `/wp-content/plugins/supabase-bridge/auth-form.html`
5. Open file, copy code
6. Return to WP Admin
7. Create page, paste code

**Expected UX:**
1. Read setup page in WP Admin
2. Code is ALREADY THERE with "Copy to clipboard" button
3. Create page, paste code
4. Done!

**Impact:**
- Major friction in plugin setup
- Requires FTP access (not always available)
- Users may not find the file
- Poor first-time user experience

**Suggested Fix:**
Embed full HTML code in setup page with:
```php
<h3>📋 Код формы (скопируйте полностью)</h3>
<button onclick="copyAuthFormCode()">📋 Copy to Clipboard</button>
<pre><code id="auth-form-code"><?php echo esc_html(file_get_contents(__DIR__ . '/auth-form.html')); ?></code></pre>
<script>
function copyAuthFormCode() {
  navigator.clipboard.writeText(document.getElementById('auth-form-code').textContent);
  alert('✅ Код скопирован в буфер обмена!');
}
</script>
```

**Alternative:**
- Create WordPress shortcode `[supabase_auth_form]` that outputs the form
- Users just insert shortcode instead of HTML
- Even better UX!

**Affected Files:**
- `supabase-bridge.php:329` (setup instructions)

**References:**
Previous version had code directly in instructions (better UX).

**Resolution (v0.4.0):**
Implemented `[supabase_auth_form]` shortcode - users now insert shortcode instead of copying 1068 lines of code. Setup reduced from 7 steps to 4 steps. No FTP access required.

---

#### Issue #4: Modern WordPress plugin UX - Settings page with page selector
**Severity:** High (UX/Usability)
**Priority:** 🔥 High (Modern standard)
**Status:** ✅ Resolved in v0.4.0
**Reported:** 2025-10-23
**Resolved:** 2025-10-24
**Description:**
Current setup requires manual page creation, FTP access to copy HTML, and manual insertion. Modern WordPress plugins provide settings page with page selector and automatic setup.

**Current UX (manual):**
1. Read setup instructions
2. Manually create Login page in WordPress Admin
3. Open FTP, navigate to `/wp-content/plugins/supabase-bridge/auth-form.html`
4. Copy HTML code
5. Paste into page (Elementor/Gutenberg)
6. Manually create Thank You page
7. Repeat for every site

**Modern WordPress UX (automated):**
```
Settings Page:
┌─────────────────────────────────────────┐
│ 📄 Login Page:                          │
│ [Dropdown: Select page ▼] [+ Create]   │
│                                         │
│ 🎉 Thank You Page:                      │
│ [Dropdown: Select page ▼] [+ Create]   │
│                                         │
│ [Save Settings]                         │
└─────────────────────────────────────────┘

On "Save Settings":
1. Create pages if needed
2. Auto-insert [supabase_auth_form] shortcode on Login page
3. Save URLs to wp_options
4. Use saved URLs for redirects
5. Done! Zero manual work.
```

**Shortcode Benefits:**
- ✅ Works in **any page builder** (Gutenberg, Elementor, Divi, WPBakery)
- ✅ Update form once → all pages updated automatically
- ✅ Centralized control
- ✅ Can add parameters: `[supabase_auth_form theme="dark" providers="google,facebook"]`

**Implementation Plan:**
1. Add settings page with page selector dropdowns
2. Implement shortcode `[supabase_auth_form]` (outputs auth-form.html content)
3. Auto-create pages on "Save Settings" if selected
4. Auto-insert shortcode on Login page
5. Save page URLs to `wp_options` (e.g., `supabase_bridge_login_page`, `supabase_bridge_thankyou_page`)
6. Update auth-form.html to read Thank You URL from PHP config instead of hardcoded `/registr/`

**Example Implementation:**
```php
// Settings page
add_options_page('Supabase Bridge Settings', 'Supabase Bridge', 'manage_options', 'supabase-bridge-settings', 'sb_render_settings_page');

// Shortcode
add_shortcode('supabase_auth_form', function($atts) {
  $atts = shortcode_atts([
    'theme' => 'light',
    'providers' => 'google,facebook,magic-link'
  ], $atts);

  ob_start();
  include(__DIR__ . '/auth-form.html');
  return ob_get_clean();
});

// Auto-create pages
function sb_create_login_page() {
  $page_id = wp_insert_post([
    'post_title' => 'Login',
    'post_content' => '[supabase_auth_form]',
    'post_status' => 'publish',
    'post_type' => 'page'
  ]);
  update_option('supabase_bridge_login_page', $page_id);
}
```

**Affected Files:**
- `supabase-bridge.php` (add settings page, shortcode, auto-creation logic)
- `auth-form.html` (read Thank You URL from PHP config)

**Priority Justification:**
This is the **standard approach** in modern WordPress plugins (WooCommerce, MemberPress, LearnDash, etc.). Users expect this level of automation.

**Estimated Effort:** 4-6 hours

**Resolution (v0.4.0):**
Implemented Settings page with:
- Thank You Page selector (wp_dropdown_pages)
- Encrypted credentials storage (AES-256-CBC)
- Real-time credentials verification
- Modern WordPress admin UX
- Zero manual file editing required

---

#### Issue #5: Security - Credentials stored in plaintext in wp-config.php
**Severity:** 🔴 Critical (Security)
**Priority:** 🔥 Critical
**Status:** ✅ Resolved in v0.4.0
**Reported:** 2025-10-23
**Resolved:** 2025-10-24
**Description:**
Current implementation requires hardcoding Supabase credentials in `wp-config.php` as plaintext environment variables. This is insecure and not aligned with modern WordPress plugin standards.

**Current Approach (insecure):**
```php
// wp-config.php - plaintext credentials!
putenv('SUPABASE_PROJECT_REF=fomzkfdcueugsykhhzqe');
putenv('SUPABASE_URL=https://fomzkfdcueugsykhhzqe.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGci...');
```

**Security Problems:**
- 🔴 Credentials in **plaintext** in config file
- 🔴 wp-config.php often committed to Git (credential leak)
- 🔴 Included in backups, logs, version control
- 🔴 Requires manual file editing (FTP/SSH access)
- 🔴 If credentials stolen = full Supabase project access
- 🔴 No way to rotate keys without manual wp-config.php edit
- 🔴 Multiple WordPress sites = credentials duplicated everywhere

**Modern WordPress Plugin Standards:**

**Option A: OAuth Handshake (ideal, but Supabase doesn't support it)**
Examples: Stripe, Mailchimp, Google Analytics
```
Settings Page:
┌─────────────────────────────────────────┐
│ [🔗 Connect with Supabase]              │
└─────────────────────────────────────────┘
```
Flow:
1. Redirect to Supabase OAuth authorization
2. User logs into Supabase and selects project
3. Supabase verifies admin permissions
4. Returns authorization code
5. Plugin exchanges code for access token
6. Token saved **encrypted** in database

**ISSUE:** Supabase doesn't have OAuth provider for third-party apps (as of 2025-10-23)

**Option B: Settings Page + Encrypted Storage (realistic)**
Examples: SendGrid, Twilio, Custom API integrations
```
Settings Page:
┌──────────────────────────────────────────────┐
│ 🔗 Supabase Configuration                    │
├──────────────────────────────────────────────┤
│ Project URL:                                 │
│ [https://xxx.supabase.co              ]      │
│                                              │
│ Anon Key (Public):                           │
│ [eyJhbGci...                          ]      │
│                                              │
│ [🧪 Test Connection] [💾 Save (Encrypted)]   │
└──────────────────────────────────────────────┘
```

On "Save":
1. ✅ Test connection (validate credentials work)
2. ✅ **Encrypt** credentials (WordPress encryption or PHP Sodium)
3. ✅ Save to `wp_options` table (database, NOT wp-config.php)
4. ✅ Only admins can view/edit
5. ✅ Show masked values in UI (e.g., `eyJh...MASKED`)

**Implementation Example:**
```php
// Save (encrypted)
function sb_save_credentials($url, $anon_key) {
  $encrypted = [
    'url' => sb_encrypt($url),
    'anon_key' => sb_encrypt($anon_key),
    'updated_at' => time()
  ];
  update_option('supabase_bridge_credentials', $encrypted, false); // autoload=false
}

// Retrieve (decrypt in runtime)
function sb_get_credentials() {
  $encrypted = get_option('supabase_bridge_credentials');
  if (!$encrypted) return null;

  return [
    'url' => sb_decrypt($encrypted['url']),
    'anon_key' => sb_decrypt($encrypted['anon_key'])
  ];
}

// Encryption (using WordPress salts as key)
function sb_encrypt($value) {
  $key = wp_salt('secure_auth'); // Use WordPress salt as encryption key
  return openssl_encrypt($value, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}

function sb_decrypt($encrypted) {
  $key = wp_salt('secure_auth');
  return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}

// Test connection
function sb_test_connection($url, $anon_key) {
  $response = wp_remote_get($url . '/rest/v1/', [
    'headers' => [
      'apikey' => $anon_key,
      'Authorization' => 'Bearer ' . $anon_key
    ]
  ]);

  return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
}
```

**Additional Security Features:**

1. **IP/Domain Whitelisting** (Supabase Dashboard → API Settings)
   - Only specific WordPress domain can use the anon key

2. **Rate Limiting** (automatic in Supabase)
   - Prevents abuse if key somehow leaks

3. **Audit Logging** (Supabase Dashboard → Logs)
   - Track who/when uses credentials

4. **Key Rotation**
   - Settings page: [🔄 Rotate Keys] button
   - Generates new key in Supabase, updates plugin automatically

**Benefits:**
- ✅ Credentials **encrypted** in database (not plaintext)
- ✅ No manual file editing required
- ✅ Keys not in wp-config.php (safer for Git, backups)
- ✅ Easy key rotation through UI
- ✅ Audit trail of changes (WordPress user who saved credentials)
- ✅ Can be excluded from database exports
- ✅ Follows modern WordPress plugin standards

**Migration Plan:**
1. Add settings page with credential input fields
2. Implement encryption/decryption functions
3. Update plugin to read from `wp_options` instead of `getenv()`
4. Provide migration tool: "Import from wp-config.php" button
5. Update documentation to recommend Settings page approach
6. **Deprecate** wp-config.php method (still support for backward compatibility)

**Affected Files:**
- `supabase-bridge.php` (add settings page, encryption functions)
- All code using `sb_cfg()` function (switch to `sb_get_credentials()`)
- Documentation (update setup instructions)

**Similar Implementations:**
- WooCommerce (Stripe/PayPal keys)
- Jetpack (WordPress.com connection)
- MonsterInsights (Google Analytics)
- WPForms (payment gateway keys)

**Priority Justification:**
**CRITICAL** - Storing API credentials in plaintext is a security vulnerability. This is blocking wider adoption as security-conscious users won't use the plugin.

**Estimated Effort:** 6-8 hours

**Resolution (v0.4.0):**
Implemented encrypted credentials storage:
- AES-256-CBC encryption using WordPress salts
- Credentials saved to wp_options table (not wp-config.php)
- sb_encrypt() / sb_decrypt() functions
- Settings page with masked credential display
- Real-time API verification on save
- Backward compatibility with wp-config.php as fallback
- Zero plaintext credentials in config files

---

#### Issue #6: Confusing auth-form.html structure - unclear what code to copy
**Severity:** 🔴 Critical (UX/Usability)
**Priority:** 🔥 Critical
**Status:** ✅ Resolved in v0.4.0
**Reported:** 2025-10-23
**Resolved:** 2025-10-24
**Description:**
The `auth-form.html` file (1211 lines) contains 142 lines of instructional comments at the top, making it unclear where the actual code to copy begins. Users are confused about what portion of the file to copy to their WordPress page.

**Current File Structure:**
```
Lines 1-142:   <!-- HUGE comment block with instructions -->
Line 142:      -->
Line 144:      <style>   ← ACTUAL CODE STARTS HERE!
...
Line 1199:     </script> ← CODE ENDS HERE
Lines 1200-1212: <!-- Final comment -->
```

**User Experience (terrible):**
1. Open `auth-form.html` (1211 lines!)
2. See massive comment block with instructions
3. **CONFUSED:** Where does code to copy start?
4. **CONFUSED:** Do I copy the comments too?
5. **CONFUSED:** Where does code end?
6. May copy wrong sections or incomplete code

**Root Cause:**
- File mixes documentation (142 lines) with code (1068 lines)
- No clear visual separator
- Comment at line 8 says "Вставь весь этот код" (insert all this code) - but does "all" include the comments?
- Total mess for non-technical users

**Impact:**
- Users copy wrong portions of file
- Incomplete code = broken auth form
- Support burden: users reporting "form doesn't work" (because they copied wrong code)
- Poor first-time user experience
- Major barrier to plugin adoption

**Suggested Fixes:**

**Option A: Separate Files (best)**
```
/wp-content/plugins/supabase-bridge/
  ├── auth-form.html              ← ONLY clean code (no comments)
  ├── auth-form-docs.md           ← Instructions moved here
  └── README.md                   ← Quick start guide
```

Benefits:
- ✅ auth-form.html = pure code, no confusion
- ✅ Copy entire file = guaranteed correct
- ✅ Documentation separate = cleaner

**Option B: Clear Visual Markers**
```html
<!-- ========== INSTRUCTIONS END HERE ========== -->
<!-- ========== DO NOT COPY ANYTHING ABOVE THIS LINE ========== -->
<!-- ========== START COPYING FROM NEXT LINE ========== -->

<style>
  /* Auth form styles... */
```

Benefits:
- ✅ Crystal clear where to start copying
- ✅ Minimal changes required

**Option C: Shortcode (eliminates problem entirely)**
See Issue #4 - `[supabase_auth_form]` means user never copies anything!

**Recommended Solution:**
Implement **ALL THREE**:
1. **Short term:** Add visual markers (Option B) - 5 minutes
2. **Medium term:** Separate files (Option A) - 30 minutes
3. **Long term:** Implement shortcode (Option C, see Issue #4) - 4-6 hours

**Implementation Example (Option A):**
```
auth-form.html (clean code only):
<style>
  /* ... all styles ... */
</style>
<div class="sb-auth-wrapper">
  <!-- ... all HTML ... -->
</div>
<script>
  // ... all JavaScript ...
</script>

auth-form-docs.md (documentation):
# Supabase Auth Form Documentation
## Installation
1. Copy entire contents of `auth-form.html`
2. Paste into HTML widget in Elementor/Gutenberg
3. Configure AUTH_CONFIG if needed
...
```

**Affected Files:**
- `auth-form.html` (restructure/split)
- Setup instructions (update to point to clean file)

**Priority Justification:**
**CRITICAL** - This is a **blocker for plugin adoption**. Users literally cannot install the plugin without confusion. Every new user hits this issue.

**Estimated Effort:**
- Option B (markers): 5 minutes
- Option A (separate files): 30 minutes
- Option C (shortcode): 4-6 hours (see Issue #4)

**User Quote:**
> "Вот снова тупик UX - посмотрел в auth-form.html, а там mess - откуда я понимаю какой кусок кода куда вставлять?"

**Resolution (v0.4.0):**
Eliminated the problem entirely with `[supabase_auth_form]` shortcode:
- Users never see or copy auth-form.html
- Just insert shortcode in any page/post
- Works with all page builders (Gutenberg, Elementor, Divi)
- auth-form.html simplified to clean documentation header
- Problem solved by removing the need to copy code

---

#### Issue #7: No clear instructions on how to configure Thank You page URL
**Severity:** 🔴 Critical (UX/Configuration)
**Priority:** 🔥 Critical
**Status:** ✅ Resolved in v0.4.0
**Reported:** 2025-10-23
**Resolved:** 2025-10-24
**Description:**
After copying 1068 lines of code from `auth-form.html` to WordPress page, users have no clear guidance on WHERE and HOW to configure the Thank You page URL. The configuration is buried inside the code at line 722, and setup instructions don't mention this step at all.

**Current User Flow (confusing):**
1. Copy 1068 lines from auth-form.html
2. Paste into WordPress page HTML block
3. **❓ Now what? Where do I set my Thank You page URL?**
4. **Not mentioned in setup instructions!**
5. User must search through 1068 lines of code to find `AUTH_CONFIG`
6. Change line 722: `'default': '/registr/'` to their URL

**Configuration Location (hidden):**
```javascript
// Line 687-730 inside auth-form.html (buried in 1068 lines!)
const AUTH_CONFIG = {
  thankYouPages: {
    'default': '/registr/'  // ← User needs to find and change this!
  },
  defaultRedirect: '/',
  newUserThreshold: 60000
};
```

**Problems:**
- 🔴 Setup instructions (`supabase-bridge.php:326-389`) **don't mention** configuring Thank You URL
- 🔴 Configuration buried in middle of 1068 lines of code
- 🔴 No visual marker saying "⚠️ CHANGE THIS BEFORE USE"
- 🔴 After pasting in WordPress HTML block, finding line 722 is nearly impossible
- 🔴 Users will just use default `/registr/` without realizing it's configurable
- 🔴 Non-technical users have no way to find this

**What Users Expect (modern standard):**
```
Settings Page in WP Admin:
┌──────────────────────────────────────────┐
│ Thank You Page (for new users):          │
│ [Dropdown: Select page ▼]               │
│ └─ /welcome/ (current)                   │
│                                          │
│ [Save Settings]                          │
└──────────────────────────────────────────┘
```

**Current Workaround:**
User must manually edit JavaScript inside WordPress HTML block - extremely error-prone and breaks on code updates.

**Impact:**
- Users create Thank You page but can't configure it
- Plugin uses hardcoded `/registr/` for everyone
- Users confused why redirect goes to wrong page
- Support burden: "How do I change thank you page?"
- Non-technical users completely blocked

**Suggested Fixes:**

**Option A: Add to Setup Instructions (minimal fix, 5 min)**
Update `supabase-bridge.php` setup page:
```php
<h2>📄 Шаг 2.5: Настройка Thank You Page URL</h2>
<p><strong>ВАЖНО!</strong> После вставки кода найдите строку:</p>
<pre><code>'default': '/registr/'</code></pre>
<p>Измените <code>/registr/</code> на URL вашей страницы благодарности (например: <code>/welcome/</code>)</p>
<p><em>Эта строка находится примерно на строке 40-50 внутри секции <code>AUTH_CONFIG</code></em></p>
```

**Option B: Visual Marker in Code (better, 5 min)**
Add HUGE comment in auth-form.html before AUTH_CONFIG:
```javascript
// ═══════════════════════════════════════════════════════════
// ⚠️⚠️⚠️ ВАЖНО! ИЗМЕНИ СЛЕДУЮЩУЮ СТРОКУ! ⚠️⚠️⚠️
// ═══════════════════════════════════════════════════════════
// Укажи URL твоей страницы благодарности (например: /welcome/)
// ═══════════════════════════════════════════════════════════

const AUTH_CONFIG = {
  thankYouPages: {
    'default': '/registr/'  // ⚠️ ИЗМЕНИ ЗДЕСЬ!
  },
```

**Option C: Settings Page (best, see Issue #4)**
Store Thank You URL in WordPress options:
```php
// Plugin settings
$thank_you_url = get_option('supabase_bridge_thankyou_page', '/registr/');

// Pass to JavaScript
wp_localize_script('supabase-auth-form', 'SB_CONFIG', [
  'thankYouUrl' => $thank_you_url
]);

// In auth-form.html
const AUTH_CONFIG = {
  thankYouPages: {
    'default': window.SB_CONFIG.thankYouUrl  // From plugin settings!
  }
};
```

**Option D: Shortcode Parameters (elegant)**
```
[supabase_auth_form thank_you="/welcome/"]
```

**Recommended Solution:**
1. **Immediate (5 min):** Option A + B (update instructions + add visual markers)
2. **Short term (2-3 hours):** Option C (Settings page integration)
3. **Long term (see Issue #4):** Full settings page with page selectors

**Affected Files:**
- `supabase-bridge.php` (setup instructions)
- `auth-form.html` (add visual markers around AUTH_CONFIG)

**Priority Justification:**
**CRITICAL** - Users literally cannot configure the plugin for their needs. This is a **blocker for production use** - every site needs different Thank You page URL.

**Estimated Effort:**
- Option A + B: 10 minutes
- Option C: 2-3 hours
- Option D: 4-6 hours (part of Issue #4)

**User Quote:**
> "Еще одна UX проблема - а где надо указать, какой урл страницы благодарности?"
>
> "Исправил, но ручками это 100% ошибешься. Почему и нужно сначала создать / указать страницу благодарности, плагин берет ее URL, корректирует код под шорткодом"

**Key Insight from User Testing:**
User discovered the core UX problem during real usage: **manual URL configuration is error-prone and breaks the WordPress mental model**. In WordPress, you select pages from dropdowns - you don't manually type URLs into code. This validates the critical need for Issues #4 + #7 solutions.

**Proper WordPress Flow:**
1. Settings page → Select "Thank You Page" from dropdown
2. Plugin auto-extracts URL from selected page
3. Shortcode renders with correct URL automatically
4. **Zero manual work, zero errors, zero broken redirects**

**Resolution (v0.4.0):**
Implemented the proper WordPress flow:
- Settings page with Thank You Page dropdown (wp_dropdown_pages)
- Plugin reads selected page ID from wp_options
- AUTH_CONFIG.thankYouPages populated from Settings (not hardcoded)
- Users select page from dropdown - plugin handles URL extraction
- Zero manual URL typing, zero errors, zero broken redirects
- Exactly as user requested: "плагин берет ее URL, корректирует код под шорткодом"

---

### Critical Issues
Currently no known critical bugs. Project is production-ready.

---

## 📊 Feature Metrics

### Version 0.4.0 Statistics
- **Lines of Code:** 584 (plugin), 1068 (auth-form.html)
- **REST Endpoints:** 2 (`/callback`, `/logout`)
- **WordPress Hooks:** 4 (`wp_enqueue_scripts`, `rest_api_init`, `send_headers`, `admin_menu`)
- **Shortcodes:** 1 (`[supabase_auth_form]`)
- **External Dependencies:** 1 (`firebase/php-jwt` ^6.11.1)
- **Database Tables Modified:** 0 (uses existing WP tables + wp_options)
- **Encryption:** AES-256-CBC (WordPress salts as key)
- **Security Headers:** 5 (CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- **Settings Pages:** 1 (Supabase Bridge Setup)
- **Setup Complexity:** 4 steps (reduced from 7 steps in v0.3.5)
- **Issues Resolved:** 5 critical UX/security issues (#3, #4, #5, #6, #7)

### Version 0.3.3 Statistics
- **Lines of Code:** 388 (plugin), ~50 (HTML examples)
- **REST Endpoints:** 2 (`/callback`, `/logout`)
- **WordPress Hooks:** 3 (`wp_enqueue_scripts`, `rest_api_init`, `send_headers`)
- **External Dependencies:** 1 (`firebase/php-jwt` ^6.11.1)
- **Database Tables Modified:** 0 (uses existing WP tables)
- **Security Headers:** 5 (CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)

---

## 🎯 Roadmap Timeline

### Next 3 Releases
1. **v0.2.0:** Role mapping (1 week)
2. **v0.4.0:** User metadata sync (2 weeks)
3. **v0.5.0:** Email/password authentication (2 weeks)

### Q1 2025 Goals
- Complete v0.2.0 through v0.5.0 (high priority features)
- Reach 100+ WordPress site installations
- Gather user feedback for prioritization

### Long-Term Vision (v1.0+)
- Full-featured authentication plugin for WordPress
- Supabase database integration patterns
- Multi-site SSO support
- Enterprise features (audit logs, compliance)

---

## 📝 Notes

### Version Strategy
- **Patch (0.x.x):** Bug fixes only
- **Minor (0.x.0):** New features, backward compatible
- **Major (x.0.0):** Breaking changes (e.g., minimum PHP version bump)

### Documentation Requirements
All features must update:
- **BACKLOG.md** - Status change (this file)
- **ARCHITECTURE.md** - If architectural impact
- **AGENTS.md** - If new patterns/rules discovered
- **README.md** - If user-facing changes

### Feature Prioritization Criteria
1. **User Impact:** How many users benefit?
2. **Security:** Does it improve security?
3. **Effort:** How long to implement?
4. **Dependencies:** Does it unblock other features?

---

## 🔗 Related Documentation

- **README.md** (archive/legacy-docs/) - User-facing documentation and setup guide
- **ARCHITECTURE.md** (Init/) - Technical architecture and design decisions
- **AGENTS.md** (Init/) - AI assistant working instructions
- **SECURITY.md** (Init/) - Security policy and best practices

---

*This document is the authoritative source for project status and planning*
*Updated after every feature completion*
*Last updated: 2025-10-23*
*Migrated from docs/BACKLOG.md*
