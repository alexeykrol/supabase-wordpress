# Development Backlog

**Project:** Supabase Bridge for WordPress
**Version:** 0.3.2 - Security Hotfix
**Last Updated:** 2025-10-05

> **⚠️ SINGLE SOURCE OF TRUTH:** This file is the ONLY authoritative source for implementation status and roadmap.

---

## 📋 Current Implementation Status

### ✅ Version 0.1.0 - Core Authentication (Current)
**Released:** 2025-10-01
**Status:** Complete

#### Implemented Features
- [x] **JWT Verification via JWKS** - Server-side JWT validation with Supabase public keys
- [x] **WordPress User Synchronization** - Automatic user creation on first login
- [x] **OAuth Provider Support** - Works with any Supabase-configured provider (Google, Apple, GitHub, etc.)
- [x] **REST API Endpoints** - `/callback` and `/logout` endpoints
- [x] **Configuration via Environment Variables** - Secure credential management
- [x] **Supabase JS Integration** - CDN-loaded client library with config injection
- [x] **Session Management** - WordPress authentication cookies
- [x] **User Metadata Storage** - `supabase_user_id` in wp_usermeta

#### Technical Details
- **Plugin Size:** ~100 lines (single PHP file)
- **Dependencies:** `firebase/php-jwt` ^6.10
- **WordPress Hooks:** `wp_enqueue_scripts`, `rest_api_init`
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

#### v0.3.0 - JWKS Caching
**Priority:** High
**Estimated Effort:** Small
**Status:** Planned

**Description:** Cache JWKS response to reduce external API calls

**Requirements:**
- Use WordPress Transients API (`set_transient`, `get_transient`)
- Cache JWKS for 15 minutes (Supabase key rotation policy)
- Handle cache invalidation on verification failure
- Fallback to fresh fetch if cache miss

**Technical Approach:**
```php
function sb_get_jwks($project_ref) {
  $cache_key = 'sb_jwks_' . $project_ref;
  $cached = get_transient($cache_key);

  if ($cached !== false) {
    return $cached;
  }

  $jwks_url = "https://{$project_ref}.supabase.co/auth/v1/.well-known/jwks.json";
  $response = wp_remote_get($jwks_url);
  $keys = json_decode(wp_remote_retrieve_body($response), true);

  set_transient($cache_key, $keys, 15 * MINUTE_IN_SECONDS);
  return $keys;
}
```

**Benefits:**
- Reduced latency on login (no external API call)
- Lower load on Supabase JWKS endpoint
- Better scalability for high-traffic sites

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
**Priority:** Medium
**Estimated Effort:** Small
**Status:** Planned

**Description:** Passwordless login via email magic links

**Requirements:**
- Frontend form for email input
- Use `supabase.auth.signInWithOtp()`
- Email sent by Supabase with login link
- Redirect to callback page after clicking link
- Same JWT verification flow

**Technical Approach:**
```javascript
const { data, error } = await sb.auth.signInWithOtp({
  email: email_input.value,
  options: {
    emailRedirectTo: window.location.origin + '/supabase-callback/'
  }
});

// User receives email, clicks link → redirects to callback → JWT verified
```

**Benefits:**
- Passwordless UX (better security)
- No password management for users
- Reduces credential theft risk

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
- [ ] **Troubleshooting guide** - Common issues and solutions (expand AGENTS.md)
- [ ] **Migration guide** - Moving from other auth plugins

---

### Low Priority

#### Performance
- [ ] **Lazy script loading** - Only load Supabase JS on login pages
- [ ] **Minified inline JS** - Reduce config injection size
- [ ] **Database query optimization** - Reduce `get_user_by()` calls

---

## 📊 Feature Metrics

### Version 0.1.0 Statistics
- **Lines of Code:** ~100 (plugin), ~50 (HTML examples)
- **REST Endpoints:** 2 (`/callback`, `/logout`)
- **WordPress Hooks:** 2 (`wp_enqueue_scripts`, `rest_api_init`)
- **External Dependencies:** 1 (`firebase/php-jwt`)
- **Database Tables Modified:** 0 (uses existing WP tables)

---

## 🎯 Roadmap Timeline

### Next 3 Releases
1. **v0.2.0:** Role mapping (1 week)
2. **v0.3.0:** JWKS caching (1 week)
3. **v0.4.0:** User metadata sync (2 weeks)

### Q1 2025 Goals
- Complete v0.2.0 through v0.4.0 (high priority features)
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
- **Patch (0.1.x):** Bug fixes only
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

- **README.md** - User-facing documentation and setup guide
- **ARCHITECTURE.md** - Technical architecture and design decisions
- **AGENTS.md** - AI assistant working instructions
- **FLOW.md** - Authentication flow diagram (Mermaid)

---

*This document is the authoritative source for project status and planning*
*Updated after every feature completion*
*Last updated: 2025-10-01*
