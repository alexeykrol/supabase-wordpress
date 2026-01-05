# Changelog

All notable changes to Supabase Bridge are documented in this file.

## [0.9.13] - 2026-01-05

### MemberPress Webhook System Upgrade

**Renamed webhook system from Make.com to MemberPress Webhooks** - Universal multi-platform webhook support
- **Rebranding:** Changed all function names from `sb_make_webhook_*` to `sb_memberpress_webhook_*`
- **Admin UI:** Updated tab from "🎣 Make.com" to "🎣 MemberPress Webhook"
- **Multiple URL Support:** Changed from single URL input to textarea (one URL per line)
  - Supports Make.com, Zapier, n8n, or any HTTP webhook endpoint
  - Sends to all configured URLs simultaneously
- **MemberPress-Compatible Payload:** Full 100+ field payload matching MemberPress webhook structure
  - Event type: `non-recurring-transaction-completed`
  - Nested objects: membership, member, transaction data
  - 100% compatible with existing MemberPress automations in Make.com, Zapier, n8n
- **Automatic Migration:** Created `sb_migrate_webhook_settings()` function
  - Runs once on `admin_init`, migrates old settings to new format
  - Backward compatibility wrapper (`sb_send_make_webhook()`)
  - Fallback logic to read old options if new ones are empty
  - Zero breaking changes during deployment
- **Real Data Testing:** Test webhook uses ACTUAL data from last registration
  - Queries `mepr_transactions` table for last `sb-%` transaction
  - Fetches real user email, membership ID, transaction details
  - `test_mode = false` for production-quality payload
  - No more stub/fake test data (999999, test@example.com)
- **Real Payload Preview:** Documentation section shows ACTUAL JSON preview
  - Generated from last registration data (like MemberPress does)
  - Shows what will be sent when test button is clicked
  - Conditional display: real preview or "no registrations yet" message
- **Dynamic Success Messages:** Test webhook shows details
  - Example: "Test webhook sent to 2 URL(s) using REAL data from last registration (User: email@example.com, Transaction ID: 12345)"
  - Instead of generic "Test webhook sent successfully!"
  - Provides verification of which user's data was sent

**Files Modified:**
- `supabase-bridge.php` (lines 4405-5019)
  - Migration function (4405-4459)
  - Admin tab rendering with real payload preview (4464-4592)
  - AJAX save handler (4660-4690)
  - AJAX test handler with real data (4757-4813)
  - Webhook send function (4789-5002)
  - Backward compatibility wrapper (5016-5019)

**Deployment:**
- Tested on production (alexeykrol.com) with zero downtime
- Auto-migration successful
- All existing Make.com automations continue working
- No errors in production logs

## [0.9.12] - 2026-01-04

### 2026-01-04 22:45 - Error Handling Enhancement
**Fixed: Supabase error detection on callback page** - Users stuck on "Welcome! Wait..." message
- Added error detection BEFORE token extraction in callback handler
- Parse Supabase errors from URL hash (`#error=otp_expired`, etc.)
- Show user-friendly error messages with specific instructions
- Provide "Return to form" button with link to `registration_url`
- Handles common errors:
  - `otp_expired` - "Link expired, request new one"
  - `otp_disabled` - "Email login unavailable, use Google/Facebook"
  - `access_denied` - "Access denied, contact support"
  - Generic errors - Show Supabase error description

**Error message format:**
```
⚠️ [Specific error message]

Чтобы войти снова, перейдите к форме входа:
[Перейти к форме входа] → {registration_url}
```

### 2026-01-04 14:30 - Critical Infrastructure Changes

**CRITICAL: SMTP Provider Migration** - Migrated from Supabase SMTP to Amazon SES
- **Root cause:** Supabase built-in SMTP hit rate limits during high traffic (European morning registrations)
- **Impact:** Magic Link emails stopped sending, blocking new user registrations
- **Solution:** Migrated to Amazon SES (Simple Email Service)
- **Implementation:**
  - Created AWS account and configured SES
  - Verified domain ownership (DKIM, SPF records)
  - Updated Supabase Dashboard → Authentication → SMTP Settings
  - Tested email delivery at scale
- **IMPORTANT:** Supabase SMTP is ONLY for MVP/testing. Production REQUIRES external SMTP provider.
- **Recommended providers:** Amazon SES (used here), SendGrid, Mailgun, Postmark

### 2026-01-04 10:15 - Data Integrity Fixes

**Fixed: Magic Link cross-device registration URL loss** - ~46% of Magic Link registrations losing pair_id
- Root cause: OAuth redirects lose localStorage when user opens email on different device/browser
- Solution: Pass `registration_url` via URL query parameter in Magic Link emails
- Modified `auth-form.html` to include registration_url in `emailRedirectTo` callback URL
- Modified callback page (`test-no-elem-2-wordpress-paste.html`) to read from URL param with localStorage fallback
- Priority-based detection: URL param → localStorage → current page path
- Fixes ~46% data loss in marketing analytics

**Fixed: Registration pair sync bug** - WordPress pairs not syncing to Supabase
- Added missing `sb_sync_pair_to_supabase()` call in `sb_ajax_save_pair()`
- Added missing `sb_delete_pair_from_supabase()` call in `sb_ajax_delete_pair()`
- Registration pairs now properly sync from WordPress to Supabase table

### Added
- **Data Integrity Monitoring System** - Local bash script for verifying registration tracking
  - `monitoring/check-integrity.sh` - Compares auth.users vs wp_user_registrations
  - Checks for lost registrations (not tracked in analytics)
  - Checks for missing landing page attribution (pair_id = NULL)
  - Configurable time period (default: 1 hour, supports custom periods)
  - Exit code 0 = all checks passed, exit code 1 = issues detected
  - Safe read-only queries (no data modifications)
- **Monitoring Documentation**
  - `monitoring/README.md` - Complete setup and usage guide
  - `monitoring/.env.example` - Credential template with clear instructions
  - Scheduling examples for automated checks
  - Security notes (credentials in .gitignore)

### Changed
- **Callback URL structure for Magic Link** - Now includes registration_url parameter
  - Old: `https://site.com/callback`
  - New: `https://site.com/callback?registration_url=/landing-page/`
  - Backward compatible - falls back to localStorage if param missing
- **Credential management** - Added Supabase section to `.production-credentials`
  - Consolidated credential storage for monitoring scripts
  - Already in .gitignore for security

### Testing
- Verified 100% pair_id tracking accuracy after fix (SQL queries on production data)
- Tested Magic Link cross-device flow (PC → mobile email → callback)
- Tested registration pair sync (WordPress → Supabase)
- All registration methods working: Google OAuth, Facebook OAuth, Magic Link

### Results
- 100% registration tracking accuracy (no more NULL pair_ids)
- Cross-device Magic Link authentication fully working
- Real-time data integrity monitoring capability
- No lost registrations for marketing attribution

## [0.9.11] - 2025-12-28

### Added
- **Helper Functions** - Check membership/enrollment status
  - `sb_has_membership($user_id, $membership_id)` - Check if user has active membership
  - `sb_is_enrolled($user_id, $course_id)` - Check if user is enrolled in course
- **User Status Analyzer Module** - Analyzes current memberships/enrollments
  - Checks registration URL against configured pairs
  - Determines what memberships/courses should be assigned
  - Returns clear data structure for action executor
- **Action Executor Module** - Executes membership/course assignments
  - Prevents duplicate assignments using helper functions
  - Comprehensive logging for debugging
  - Clean separation from analysis logic

### Fixed
- **Redirect Logic Conflict** - Clear priority between Registration Pairs and Return URL
  - Registration Pairs redirect takes priority over `redirect_to` parameter
  - Added documentation in code explaining redirect logic
  - Prevents accidental redirect URL overrides

### Testing
- All integration tests passed successfully
- Verified duplicate prevention for memberships and courses
- Tested redirect logic priority

## [0.9.10] - 2025-12-21

### Added
- **PKCE Flow Support** - OAuth now works in Chrome and Safari
  - Modified extractTokensFromHash() to support both OAuth flows:
    - Implicit flow (hash fragment #access_token=...)
    - PKCE flow (query string ?access_token=...)
  - Maintains backward compatibility with Firefox
  - Fixes OAuth login issues caused by Supabase JS SDK @2 from CDN

### Fixed
- **dotsTimer Bug** - Fixed ReferenceError in callback handler
  - Replaced clearInterval(countdownTimer) with clearInterval(dotsTimer)
  - Fixed 3 occurrences in callback handler (lines 431, 447, 456)
- **MemberPress Compatibility** - Added patch to hide login link
  - Prevents duplicate login links from showing
  - Improves UX when using MemberPress with Supabase Bridge

### Testing
- Verified OAuth login works in Chrome, Safari, Firefox
- Tested Google OAuth and Facebook OAuth
- Tested Magic Link authentication

## [0.9.9] - 2025-12-19

### Added
- **Safari Privacy Protection** - safeStorage wrapper for Enhanced Privacy Protection
  - Fixes authentication errors in Safari Private Browsing mode
  - Automatic fallback when localStorage is blocked
- **Complete Russian Localization** - All UI elements translated
- **Instant Loading Screen** - Animated dots while processing authentication
- **3-Step Troubleshooting** - Clear instructions for common issues

### Changed
- **UX Improvements** - Eliminated flickering screens during authentication flow
- **Repository Cleanup** - Removed 51 debug files (-20,315 lines)
- **Folder Reorganization** - Moved files to security/ and supabase/ folders

### Security
- **SSH Keys Removed** - Cleaned from git history
- **Production URL Updated** - Now using alexeykrol.com

## [0.9.8] - 2025-12-18

### Added
- **Security Hardening** - Enhanced security measures
- **Testing Infrastructure** - Comprehensive testing framework

### Changed
- **Code Quality** - Improved code organization and documentation

## [0.9.7] - 2025-12-18

### Added
- **Return-to-Origin Login Flow** - Users return to original page after login
- **Unified Shortcode Architecture** - Simplified integration

### Changed
- **Login Flow Improvements** - Better UX with automatic redirects

## [0.9.6] - 2025-12-18

### Added
- **Two-Page Architecture Refactoring** - Separated auth UI from callback handler
- **Improved Page Structure** - Better organization of authentication flow

## [0.9.5] - 2025-12-18

### Fixed
- **Critical: REST API namespace** - Updated from legacy `supabase-auth` to `supabase-bridge/v1`
  - Fixes 404 errors on callback endpoint in production
  - Both REST route registration and auth-form.html fetch URL updated
  - Required for Magic Link and OAuth authentication to work
  - **BREAKING:** Supabase Redirect URLs must be updated to new endpoint

### Migration Required
If upgrading from v0.9.4 or earlier, update your Supabase Redirect URLs:
- **Old:** `https://yoursite.com/wp-json/supabase-auth/callback`
- **New:** `https://yoursite.com/wp-json/supabase-bridge/v1/callback`

## [0.9.4] - 2025-12-17

### Added
- **Version Diagnostics Panel** - Plugin admin page now displays:
  - Actual installed plugin version (from file header)
  - Plugin filename and directory (for multi-version debugging)
  - Enhanced logging availability check (detects version mismatches)
  - Helps diagnose installation issues when old version gets cached

### Technical Details
- Uses WordPress `get_file_data()` to read version directly from plugin header
- Checks for `sb_log()` function existence to verify enhanced logging availability
- Critical for production debugging when multiple plugin versions might be installed

## [0.9.3] - 2025-12-17

### Fixed
- **CSP Blocking Registration Forms** - Disabled Content-Security-Policy for non-logged-in users
  - CSP was preventing Supabase Auth form from displaying for unauthenticated users
  - Registration/login pages now work correctly in private browsing mode
  - Kept other security headers (X-Frame-Options, X-Content-Type-Options, etc.)
  - Can be re-enabled per-page if needed for specific security requirements

### Changed
- **Description** - Removed "CSP" from plugin description (feature now optional/disabled)

## [0.9.2] - 2025-12-17

### Added
- **Production Debugging System** - Enhanced logging for remote debugging
  - New `sb_log()` function with multiple log levels (DEBUG, INFO, WARNING, ERROR)
  - Automatic sensitive data redaction (tokens, passwords, keys automatically masked)
  - Context-aware logging with structured data (JSON format)
  - Function entry/exit tracing for execution flow analysis
  - Comprehensive logging in `sb_handle_callback()` function:
    - Rate limiting checks
    - CSRF validation
    - JWT verification (JWKS cache hits/misses)
    - User sync operations (find by UUID/email, creation)
    - Authentication success/failure with IP tracking
    - Full error stack traces
  - Only active when `WP_DEBUG = true` (zero performance impact in production)
  - Log file: `/wp-content/debug.log`
- **Production Debugging Documentation**
  - `PRODUCTION_DEBUGGING.md` - Complete guide for enabling debug logging and SSH access
  - `PRODUCTION_DEBUGGING_QUICK_START.md` - 5-minute setup guide
  - SSH read-only access setup instructions
  - Supabase Dashboard access guide
  - Security checklist for safe production debugging

### Changed
- **Error Logging** - Enhanced existing `error_log()` calls with structured `sb_log()` wrapper
  - More context in logs (IP addresses, user IDs, error details)
  - Better error categorization (rate limits, CSRF failures, JWT errors)
  - Easier troubleshooting with timestamped, categorized entries

### Security
- **Automatic Data Sanitization** - `sb_sanitize_log_context()` function
  - Removes sensitive data from logs (passwords, tokens, secrets, keys)
  - Truncates long strings (>500 chars) to prevent log bloat
  - Safe to share debug.log files - all credentials automatically redacted

### Technical Details
- New functions: `sb_log()`, `sb_sanitize_log_context()`, `sb_log_function_entry()`, `sb_log_function_exit()`
- Log format: `[Timestamp] [Supabase Bridge] [LEVEL] Message | Context: {...}`
- Logs written via PHP `error_log()` - compatible with all hosting environments
- No external dependencies - pure PHP implementation

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
