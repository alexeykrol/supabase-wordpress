# Changelog

All notable changes to Supabase Bridge are documented in this file.

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
