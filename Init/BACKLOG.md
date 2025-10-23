# Project Backlog

**Project:** Supabase Bridge (Auth) for WordPress
**Version:** 0.3.3
**Last Updated:** 2025-10-23

---

## 🎯 Recent Updates

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
**Completion:** 100% of MVP features

### Quick Stats
- ✅ **Completed:** 8 core features
- 🚧 **In Progress:** 0 features
- 📋 **Planned:** 12 features (v0.2.0-v1.3.0)
- 🔴 **Blocked:** 0 features

---

## 📋 Current Implementation Status

### ✅ Version 0.3.3 - Enhanced Security & Hardening (Current)
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

### No Critical Issues
Currently no known critical bugs. Project is production-ready.

---

## 📊 Feature Metrics

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
