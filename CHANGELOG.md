# Changelog

All notable changes to Supabase Bridge are documented in this file.

## [0.9.1] - 2025-12-13

### Added
- **LearnDash Banner Management UI** - WordPress Admin interface for banner patch
  - New "🎓 Banner" tab in WordPress Admin
  - Checkbox to enable/disable enrollment banner removal
  - Real-time patch status indicator with color-coded badges (Active, Not Active, Update Needed, Not Found)
  - One-click apply/restore functionality via AJAX
  - Automatic backup creation before each patch modification
  - Warning notifications after LearnDash updates prompting patch reapplication
  - Collapsible technical details section explaining patch mechanism
  - Safe patch upgrade from old versions to latest

### Changed
- **LearnDash banner patch** - Now managed via WordPress Admin UI instead of CLI script
  - Replaces standalone `patch-learndash-free-banner.php` script execution
  - Integrated into plugin settings for better UX
  - Patch status automatically detected and displayed
  - Apply/restore operations accessible without command line access

### Technical Details
- New functions: `sb_get_learndash_path()`, `sb_get_learndash_patch_status()`, `sb_apply_learndash_banner_patch()`, `sb_restore_learndash_banner_original()`
- AJAX handler: `sb_ajax_save_learndash_banner` for asynchronous patch operations
- Status detection: Distinguishes between applied, not_applied, needs_reapply, and not_found states
- Backward compatible: Works with both old patch format and detects LearnDash updates

## [0.9.0] - 2025-12-13

### Added
- **MemberPress Integration** - Auto-assign FREE memberships on registration
  - New "🎫 Memberships" tab in WordPress Admin
  - Dropdown shows only FREE memberships (price = 0)
  - CRUD operations for membership assignment rules
  - Uses `MeprTransaction::store()` to trigger all MemberPress hooks
  - Automatic membership activation when users register from specific landing pages
- **LearnDash Integration** - Auto-enroll users in courses on registration
  - New "📚 Courses" tab in WordPress Admin
  - Dropdown lists all available LearnDash courses
  - CRUD operations for course enrollment rules
  - Uses native `ld_update_course_access()` for enrollment
  - Seamless course access when users register from designated pages
- **LearnDash Banner Removal Tool** - Patch script to disable unwanted enrollment banner
  - `patch-learndash-free-banner.php` - Idempotent patch script
  - Completely disables "NOT ENROLLED / Free / Take this Course" banner for ALL course types
  - Creates automatic backups before patching
  - Can re-run safely after LearnDash updates
  - Access control managed via MemberPress and custom Elementor conditions

### Changed
- **Registration Pairs architecture** - Removed redundant Supabase synchronization
  - Pairs now stored ONLY in WordPress `wp_options` (no Supabase sync)
  - Simplified architecture - settings belong in WordPress, not external database
  - Removed `sb_sync_pair_to_supabase()` and `sb_delete_pair_from_supabase()` calls
  - Cleaner separation: WordPress handles settings, Supabase logs events

### Technical Details
- MemberPress: Creates completed transaction with `gateway = 'free'` and `status = 'complete'`
- LearnDash: Enrolls user with `$remove_access = false` parameter
- Both integrations trigger on successful registration via callback endpoint
- Patch script detects and upgrades old patches to latest version
- All features fully tested with MemberPress 1.x and LearnDash 4.x

## [0.8.5] - 2025-12-13

### Fixed
- **Registration Pairs tracking accuracy** - Registration URL now sent explicitly in POST body
- No longer relies on unreliable HTTP Referer header
- Backward compatible fallback to Referer for older deployments
- **Registration logging bug** - Fixed HTTP 400 error caused by non-existent `thankyou_page_url` column
- Removed redundant column from INSERT (thank you page accessible via `pair_id` foreign key)
- **Duplicate callback handling** - Improved HTTP 409 response handling for seamless redirects
- First duplicate request now exits silently, allowing second request to complete authentication

### Added
- **Edit Pair functionality** - Can now modify existing Registration Pairs
- Modal pre-populates with current pair values (registration page and thank you page)
- Syncs updates to both WordPress wp_options and Supabase
- **Custom delete confirmation modal** - Replaces browser `confirm()` dialog (Safari compatible)
- Styled to match WordPress admin interface

### Technical Details
- JavaScript sends `registration_url` (ORIGIN_PAGE) with callback request
- PHP validates using `sb_validate_url_path()` before logging
- Edit function loads pair data from global JavaScript array `SB_PAIRS_DATA`
- Maintains backward compatibility - falls back to Referer if POST param missing
- HTTP 409 responses handled gracefully without showing errors to users
- RLS policies added for `anon` role on both `wp_registration_pairs` and `wp_user_registrations` tables

## [0.8.4] - 2025-12-11

### Fixed
- **Critical: Magic Link authentication** - Race condition causing duplicate callbacks
- Implemented atomic MySQL `GET_LOCK()` to prevent concurrent token processing
- WordPress cookies now properly saved when using Magic Link (email) authentication
- Fixes issue where callback succeeded but user wasn't logged in due to session corruption
- Added `credentials: 'include'` to fetch request for proper cookie handling
- **Clean localStorage on login page** - Auth form now automatically clears Supabase localStorage before showing login form
- Prevents re-login issues after logout without manually clearing browser data
- Ensures fresh authentication state every time user visits login page

### Changed
- Replaced non-atomic transient lock with MySQL `GET_LOCK()` for true concurrency protection
- Second duplicate request now returns HTTP 409 immediately
- Lock automatically released after callback completion via `register_shutdown_function()`
- Added `credentials: 'include'` to callback fetch request for proper cookie handling
- Added cleanup script to auth-form.html that runs immediately on page load
- Clears all `sb-*` and `sb_processed_*` localStorage keys before form initialization
- More reliable than wp_logout hook which can be interrupted by redirects

### Technical Details
- Root cause: Supabase `onAuthStateChange` fires twice simultaneously for Magic Link
- Both requests called `wp_set_auth_cookie()`, second call corrupted first session
- Solution: Atomic database-level lock ensures only one request processes each JWT token
- Tested successfully in Safari, Chrome, and Firefox

## [0.8.3] - 2025-12-11

### Fixed
- **sb_cfg() function** now correctly reads environment variables from `$_ENV` and `$_SERVER`
- Fixes issue where `getenv()` doesn't work with `putenv()` in wp-config.php
- JWKS cache clearing for JWT Signing Keys migration

### Added
- Support for Supabase JWT Signing Keys (migrated from Legacy JWT Secret)
- Better fallback chain for reading credentials: Database → $_ENV → $_SERVER → getenv()

## [0.8.2] - 2025-12-11

### Added
- **Webhooks Tab** in WordPress Admin UI (third tab)
- Complete integration of webhook system into main plugin interface
- Visual status indicators for webhook configuration
- Collapsible setup instructions for Supabase deployment
- Test webhook button in admin interface

### Fixed
- Missing Webhooks tab in admin interface (was developed but not integrated)

## [0.8.1] - 2025-10-27

### Added
- Webhook system for n8n/make integration
- Database triggers for immediate webhook delivery (no cron delays)
- Edge Function with retry logic (3 attempts, exponential backoff)
- WordPress Admin UI for webhook testing
- Comprehensive logging in `webhook_logs` table

### Fixed
- JWT Authentication - disabled Edge Function JWT verification (HTTP 401 fix)
- RLS Policies - added anon role INSERT/UPDATE permissions
- pg_net Extension - correct syntax for v0.19.5
- Edge Function error handling for failed webhook status updates
- WordPress encrypted URL decryption for project_ref extraction

### Security
- SERVICE_ROLE_KEY stored only in Edge Function secrets
- pg_net for server-side HTTP calls (cannot be intercepted)

## [0.7.0] - 2025-10-26

### Added
- Registration Pairs Analytics system
- Multi-site support with `site_url` filtering
- 6 implementation phases for analytics:
  - Supabase database tables (`wp_registration_pairs`, `wp_user_registrations`)
  - Settings UI with Registration Pairs CRUD
  - WordPress → Supabase sync
  - JavaScript injection of pairs
  - Page-specific Thank You redirects
  - Registration event logging
- Enterprise security architecture (4-layer defense)
- Input validation functions (`sb_validate_email`, `sb_validate_url_path`, `sb_validate_uuid`, `sb_validate_site_url`)
- `build-release.sh` for release automation
- Production documentation (`PRODUCTION_SETUP.md`, `QUICK_SETUP_CHECKLIST.md`)

### Security
- Anon Key + strict RLS policies with site_url filtering
- SQL injection, XSS, path traversal prevention

## [0.4.1] - 2025-10-25

### Fixed
- **Critical:** User duplication during Magic Link and OAuth authentication
- Race condition with server-side distributed lock (WordPress Transient API)
- Elementor CSP compatibility
- WordPress text filter bypass

### Added
- 3-layer protection: UUID check + distributed lock + retry logic
- `TROUBLESHOOTING.md` with diagnostic workflow

## [0.4.0] - 2025-10-24

### Added
- `[supabase_auth_form]` shortcode (replaces 1068-line code copy)
- Settings page with Thank You Page selector
- Encrypted credentials storage (AES-256-CBC)
- Real-time credentials verification via API
- Auto-extraction of Project Ref from Supabase URL

### Changed
- Setup reduced from 7 steps to 4 steps
- No FTP access required for installation

### Fixed
- Issue #3: Poor UX - auth-form.html code not embedded
- Issue #4: Settings page with page selector
- Issue #5: Credentials in plaintext
- Issue #6: Confusing auth-form.html structure
- Issue #7: No clear Thank You page URL configuration

## [0.3.5] - 2025-10-23

### Fixed
- Google OAuth email verification (allow NULL `email_verified`)
- Magic Link localStorage timing (move deduplication after WordPress response)

## [0.3.3] - 2025-10-07

### Added
- HTTP Security Headers (CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- Enhanced error handling (generic user messages, detailed server logs)
- Audit logging with IP tracking
- Improved JWT validation with SSL verification
- Stronger passwords (32 characters)
- Enhanced email validation (RFC 5322)
- Default subscriber role for new users
- `SECURITY.md` documentation

### Changed
- Rate limit clearing on successful auth

## [0.3.2] - 2025-10-05

### Security
- **Critical:** Origin/Referer bypass fix (strict host comparison)
- CSRF protection for logout endpoint

## [0.3.1] - 2025-10-05

### Added
- CSRF Protection (Origin/Referer validation)
- JWT `aud` validation
- Email verification enforcement
- Open redirect protection
- JWKS caching (1 hour)
- Rate limiting (10/60s per IP)

### Changed
- PHP >=8.0 requirement

## [0.3.0] - 2025-10-05

### Added
- Google OAuth support
- Facebook OAuth support (advanced access for email)
- Magic Link authentication (6-digit code)
- Smart redirects (new vs existing user)
- 3 redirect modes (standard, paired, flexible)
- `auth-form.html` with all 3 methods

## [0.1.0] - 2025-10-01

### Added
- JWT verification via JWKS (RS256)
- WordPress user synchronization
- OAuth provider support (Google, Apple, GitHub, etc.)
- REST API endpoints (`/callback`, `/logout`)
- Configuration via environment variables
- Supabase JS integration (CDN)
- Session management (`wp_set_auth_cookie`)
- User metadata storage (`supabase_user_id`)

---

*For detailed technical documentation, see `.claude/ARCHITECTURE.md`*
