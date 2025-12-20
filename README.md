# Supabase Bridge (Auth) for WordPress

![Version](https://img.shields.io/badge/version-0.9.9-blue.svg)
![PHP](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0--6.8-21759B.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Security](https://img.shields.io/badge/security-enterprise%20grade-brightgreen.svg)
![Dependencies](https://img.shields.io/badge/dependencies-0%20vulnerabilities-success.svg)
![Production](https://img.shields.io/badge/production-tested-success.svg)

> WordPress plugin for Supabase Auth integration. Supports Google OAuth, Facebook OAuth, Magic Link authentication + Page-Specific Thank You Page Redirects + Enterprise-level Security.

**🎉 Production Ready** | **✅ Tested on [alexeykrol.com](https://alexeykrol.com)** | **🔐 Enterprise-Grade Security**

>
> **🎓 Created to support students of the AI Agents course for beginners:**
> - Full course: [AI Agents Full Course](https://alexeykrol.com/courses/ai_full/) (Russian)
> - For complete beginners: [Free AI Intro Course](https://alexeykrol.com/courses/ai_intro/) (Russian)

---

## 🚀 Quick Start

### Installation (Standard WordPress Method)

1. **Download** the latest release:
   - [supabase-bridge-v0.9.9.zip](https://github.com/alexeykrol/supabase-wordpress/releases/download/v0.9.9/supabase-bridge-v0.9.9.zip)
   - Or build from source: `./build-release.sh` (requires git clone)

2. **Install plugin**:
   - WordPress Admin → Plugins → Add New → Upload Plugin
   - Choose `supabase-bridge-v0.9.9.zip`
   - Click "Install Now" → "Activate Plugin"

3. **Setup Supabase database**:
   - Open Supabase Dashboard → SQL Editor
   - Run SQL from plugin directory:
     - `supabase-tables.sql` (creates tables)
     - `SECURITY_RLS_POLICIES_FINAL.sql` (applies RLS policies)

4. **Configure plugin**:
   - WordPress Admin → Settings → Supabase Bridge
   - **Supabase URL**: `https://yourproject.supabase.co`
   - **Supabase Anon Key**: `eyJhbGci...` (from Supabase Dashboard → Settings → API)
   - **Global Thank You Page**: Select a page (fallback)
   - Click "Save Settings"

5. **Create registration pairs** (optional):
   - WordPress Admin → Supabase Bridge → Registration Pairs
   - Click "Add New Pair"
   - Example: `/services/` → `/services-thankyou/`

6. **Configure Supabase Auth**:
   - Supabase Dashboard → Authentication → Settings
   - **Enable email confirmations**: ON
   - **Password minimum length**: 10
   - Supabase Dashboard → Authentication → URL Configuration
   - **Redirect URLs**: `https://yourdomain.com/*`

7. **Done!** Users can now register and be tracked by landing page.

📖 **Full Documentation:**
- **Complete feature list:** [FEATURES.md](FEATURES.md) - 🎯 **129 features organized by category!**
- Quick setup (5 min): [QUICK_SETUP_CHECKLIST.md](QUICK_SETUP_CHECKLIST.md)
- Production deployment: [PRODUCTION_SETUP.md](PRODUCTION_SETUP.md)
- Security architecture: [SECURITY_ROLLBACK_SUMMARY.md](SECURITY_ROLLBACK_SUMMARY.md)

---

## 🎉 What's New in v0.9.9

### Safari Privacy Protection & Complete Russian Localization

Released: 2025-12-19 | Status: ✅ Production Ready

#### 🔒 Safari Privacy Protection
- **safeStorage wrapper** with automatic localStorage/in-memory fallback
- **Works in Safari Privacy mode** - seamless authentication on iOS/macOS
- **Zero configuration** - automatically detects and adapts to browser privacy settings
- **Tested across all browsers** - Chrome, Safari, Firefox (normal + private modes)

#### 🌍 Complete Russian Localization
- **All UI elements** translated to Russian
- **Error messages** and notifications in Russian
- **Email instructions** and troubleshooting guides in Russian
- **Native UX** for Russian-speaking users

#### ✨ UX Polish & Improvements
- **Eliminated screen flickering** - smooth single-screen authentication flow
- **Instant loading screen** with animated dots (no countdown delays)
- **3-step troubleshooting guide** in email verification screen:
  1. Check email address for typos
  2. Check Spam/Promotions folders
  3. Resend verification email
- **Smart link behavior** - "wrong email" returns to form, "resend" sends to same email
- **Clean repository** - removed 51 debug files (-20,315 lines of code)

### Previous Releases

#### v0.9.1 - LearnDash Banner Management UI (2025-12-13)
- **New "🎓 Banner" tab** in WordPress Admin
- **One-click banner control** - enable/disable enrollment banner removal
- **Real-time status indicator** (Active, Not Active, Update Needed, Not Found)
- **Automatic backups** before modifications
- **AJAX-powered** - instant apply/restore without page reload
- **Backward compatible** with old patch versions

#### v0.9.0 - MemberPress & LearnDash Integration (2025-12-13)
- **MemberPress Integration:**
  - Auto-assign FREE memberships on registration
  - New "🎫 Memberships" tab in WordPress Admin
  - Automatic membership activation from specific landing pages
- **LearnDash Integration:**
  - Auto-enroll users in courses on registration
  - New "📚 Courses" tab in WordPress Admin
  - Native `ld_update_course_access()` integration
  - Optional banner removal patch (idempotent, upgrade-safe)

#### v0.8.5 - Registration Pairs Critical Fixes (2025-12-13)
- **Accurate tracking** - Registration URL in POST body (not HTTP Referer)
- **Edit functionality** - Modify existing pairs with modal pre-population
- **Safari-compatible delete** - Custom confirmation dialog
- **Full CRUD** - Complete Create, Read, Update, Delete operations

#### v0.7.0 - Page-Specific Redirects & Enterprise Security (2025-10-26)
- **Registration Pairs** - Map landing pages → thank you pages for analytics
- **4-layer security** - WordPress validation → RLS → WAF → Security plugins
- **Multi-site support** - Site-specific data filtering with RLS policies

---

## ✨ Core Features

### Authentication Methods (Production Tested ✅)
- 🔵 **Google OAuth** - One-click login with Google account
- 🔷 **Facebook OAuth** - Facebook Login integration
- ✉️ **Magic Link (Passwordless)** - Email + 6-digit code (no password needed!)

### Security Features 🔐
- ✅ **JWT Verification** - Server-side RS256 signature validation via JWKS
- ✅ **CSRF Protection** - Origin/Referer validation on all endpoints
- ✅ **Rate Limiting** - 10 requests per 60 seconds per IP
- ✅ **HTTP Security Headers** - CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection
- ✅ **Audit Logging** - Complete authentication event trail with IP tracking
- ✅ **Email Verification** - OAuth providers require verified emails
- ✅ **Open Redirect Protection** - Same-origin validation on redirects
- ✅ **0 Vulnerabilities** - Clean `composer audit` report
- ✅ **4-Layer Security Architecture** - WordPress validation → Supabase RLS → Cloudflare WAF → AIOS

### Analytics & Tracking
- ✅ **Registration Pairs** - Map landing pages → thank you pages for conversion tracking
- ✅ **Supabase Logging** - All registrations logged to Supabase with full metadata
- ✅ **Page-Specific Redirects** - Different thank you pages per landing page
- ✅ **Multi-Site Support** - Site-specific data filtering with RLS policies

### Webhook Integration (v0.8.1)
- ✅ **Real-time Webhooks** - Send registration events to n8n/Make.com instantly
- ✅ **Automatic Retries** - 3 attempts with exponential backoff (1s, 2s, 4s)
- ✅ **Webhook Logs** - Complete audit trail in Supabase
- ✅ **WordPress Admin UI** - Test webhooks and monitor logs in real-time
- ✅ **Database Triggers** - Immediate delivery via PostgreSQL triggers (no cron!)

### WordPress Integration
- ✅ **Automatic User Sync** - Creates WordPress users on first login
- ✅ **Session Management** - WordPress authentication cookies
- ✅ **Supabase User ID Storage** - Links WP user to Supabase `auth.uid()`
- ✅ **Smart Redirects** - Different redirects for new vs existing users
- ✅ **Role Assignment** - Default subscriber role (configurable)
- ✅ **Shortcode Support** - `[supabase_auth_form]` works in Elementor, Gutenberg, etc.
- ✅ **Settings UI** - WordPress Admin → Settings → Supabase Bridge (3 tabs)

### Developer Experience
- ✅ **Ready-to-use Form** - `auth-form.html` with all 3 auth methods
- ✅ **REST API** - `/wp-json/supabase-auth/callback` and `/logout` endpoints
- ✅ **Encrypted Settings** - AES-256-CBC encryption for credentials in database
- ✅ **No Database Changes** - Uses existing `wp_users` and `wp_usermeta`
- ✅ **Composer** - Modern PHP dependency management
- ✅ **ZIP Installation** - Standard WordPress plugin upload method

---

## 📊 What's New in v0.8.5

### ✨ Registration Pairs Complete - Critical Fixes (v0.8.5)

**Released:** 2025-12-13
**Status:** ✅ Production Ready

Complete overhaul of Registration Pairs feature with critical bug fixes and enhancements.

**Key Features:**
- ✅ **Accurate Tracking** - Registration URL sent explicitly in POST body (not relying on HTTP Referer)
- ✅ **Edit Functionality** - Modify existing Registration Pairs with modal pre-population
- ✅ **Custom Delete Modal** - Safari-compatible confirmation dialog (replaces browser confirm())
- ✅ **Seamless Redirects** - Improved HTTP 409 duplicate callback handling
- ✅ **Full CRUD** - Complete Create, Read, Update, Delete for Registration Pairs

**Critical Fixes:**
- ✅ Registration logging bug (removed non-existent `thankyou_page_url` column)
- ✅ Duplicate callback handling (first request exits silently)
- ✅ RLS policies for anon role on both registration tables
- ✅ Backward compatible with Referer fallback
- ✅ Fully tested - data successfully logged to Supabase

**Documentation:**
- [webhook-system/README.md](./webhook-system/README.md) - Project overview
- [webhook-system/DEPLOYMENT.md](./webhook-system/DEPLOYMENT.md) - **Start here!** Critical issues section
- [webhook-system/ARCHITECTURE.md](./webhook-system/ARCHITECTURE.md) - Technical details
- [webhook-system/OAUTH-SETUP-GUIDE.md](./webhook-system/OAUTH-SETUP-GUIDE.md) - Google & Facebook OAuth

**Architecture:**
```
WordPress Registration → Database Trigger → Edge Function → n8n/Make.com
                                    ↓
                          webhook_logs table (monitoring)
```

---

### 🎉 Page-Specific Redirects + Enterprise Security (v0.7.0)

**Released:** 2025-10-26

#### New Features:
- ✅ **Registration Pairs** - Map registration pages → thank you pages for analytics
  - Settings UI in WordPress Admin → Supabase Bridge → Registration Pairs tab
  - Add/Delete pairs with AJAX sync to Supabase
  - Page-specific redirects (e.g., `/services/` → `/services-thankyou/`)
  - Analytics: track which landing page converted each user

- ✅ **Registration Logging to Supabase** - Complete analytics data
  - `wp_user_registrations` table logs: user_id, email, registration_url, thankyou_page_url, pair_id
  - JOIN queries for conversion tracking
  - A/B testing support (multiple pairs for same audience)

- ✅ **Enterprise-Grade Security** (4 layers of defense)
  - **Layer 1:** WordPress input validation (`sb_validate_email`, `sb_validate_url_path`, `sb_validate_uuid`, `sb_validate_site_url`)
  - **Layer 2:** Supabase RLS policies with `x-site-url` filtering
  - **Layer 3:** Cloudflare WAF + Bot Fight + Turnstile (recommended)
  - **Layer 4:** WordPress security plugins (AIOS recommended)

- ✅ **Multi-layer Injection Protection**
  - SQL Injection via email field → blocked by `sb_validate_email()`
  - XSS via URL fields → blocked by `sb_validate_url_path()`
  - Path Traversal (e.g., `../../../etc/passwd`) → blocked
  - UUID injection → blocked by `sb_validate_uuid()`
  - Cross-site data injection → blocked by RLS policies

#### Security Architecture:
- **Anon Key + RLS approach** (not Service Role Key - see SECURITY_ROLLBACK_SUMMARY.md)
- Defense in depth: validation → RLS → WAF → AIOS
- Protection even if Anon Key compromised (RLS blocks cross-site operations)
- Production-ready with Cloudflare + LiteSpeed Cache configurations

#### Documentation:
- **[SECURITY_ROLLBACK_SUMMARY.md](SECURITY_ROLLBACK_SUMMARY.md)** - Security architecture explained
- **[PRODUCTION_SETUP.md](PRODUCTION_SETUP.md)** - AIOS/Cloudflare/LiteSpeed setup guide (no conflicts!)
- **[QUICK_SETUP_CHECKLIST.md](QUICK_SETUP_CHECKLIST.md)** - 1-page deployment checklist
- **[SECURITY_RLS_POLICIES_FINAL.sql](SECURITY_RLS_POLICIES_FINAL.sql)** - RLS policies for Supabase
- **[Init/BACKLOG.md](Init/BACKLOG.md)** - Complete development history and changelog

**Testing:** All 6 phases tested end-to-end with JOIN query validation ✅

---

### 🔧 Previous Releases

**v0.7.0** - Page-Specific Redirects + Enterprise Security (2025-10-26)
- Registration Pairs analytics system
- Multi-site support with RLS policies
- 4-layer security architecture

**v0.4.1** - Critical Bug Fix (2025-10-25)
- ✅ Fixed user duplication race condition
- Server-side distributed lock with WordPress Transient API

**v0.4.0** - Settings Page & Shortcodes (2025-10-25)
- WordPress Admin settings UI
- `[supabase_auth_form]` shortcode
- Encrypted credentials storage (AES-256-CBC)

**v0.3.5** - Bug Fixes & Production Testing (2025-10-23)
- Google OAuth email verification fix
- Magic Link localStorage timing fix
- CSP compatibility improvements

**Full Changelog:** See [Init/BACKLOG.md](Init/BACKLOG.md)

---

## 🗺️ Roadmap

### Current Status: v0.9.9 ✅ Production Ready

**Complete Feature Set** - All planned MVP features implemented:

#### Authentication & Security
- ✅ Multi-provider authentication (Google, Facebook, Magic Link)
- ✅ Safari Privacy Protection (works on iOS/macOS Privacy mode)
- ✅ Enterprise-grade security (4-layer defense architecture)
- ✅ Russian localization (complete UI translation)

#### Analytics & Integrations
- ✅ Registration Pairs (landing page → thank you page tracking)
- ✅ Webhook integration (n8n/Make.com with automatic retries)
- ✅ MemberPress integration (auto-assign FREE memberships)
- ✅ LearnDash integration (auto-enroll in courses)
- ✅ LearnDash banner management UI (one-click enable/disable)

#### WordPress Integration
- ✅ WordPress settings UI (5 tabs: General, Pairs, Webhooks, Memberships, Courses, Banner)
- ✅ Shortcode system (`[supabase_auth_form]` + `[supabase_auth_callback]`)
- ✅ Two-page architecture (form page + callback page)
- ✅ Smart redirects (return to origin page after login)

#### Security & Testing
- ✅ Comprehensive security scanning (dialog files + source code)
- ✅ Integration testing suite (all core features)
- ✅ Clean repository (no credentials, production code only)

### Future Enhancements (v0.10.0+)

**Community-Driven Features** - Based on user feedback:

- **Role Mapping** - Map Supabase roles → WordPress roles (admin, editor, subscriber)
- **Enhanced Metadata Sync** - Sync avatar, first name, last name from OAuth providers
- **Email/Password Authentication** - Native Supabase email/password login
- **Outbox Pattern for Webhooks** - Zero event loss guarantee ([#11](https://github.com/alexeykrol/supabase-wordpress/issues/11))
- **Multi-language Support** - Add more languages beyond Russian

**Full Development History:** See [.claude/BACKLOG.md](.claude/BACKLOG.md)

**Want a feature?** [Open an issue](https://github.com/alexeykrol/supabase-wordpress/issues) or ⭐ star the repo!

---

## 📝 Support & Issues

**Production Status:** ✅ Plugin is stable and tested on [alexeykrol.com](https://alexeykrol.com)

**Need Help?**
- 📖 **Documentation:** See [QUICK_SETUP_CHECKLIST.md](QUICK_SETUP_CHECKLIST.md) for quick start
- 📖 **Production Setup:** See [PRODUCTION_SETUP.md](PRODUCTION_SETUP.md) for detailed deployment
- 📖 **Webhook Setup:** See [webhook-system/DEPLOYMENT.md](webhook-system/DEPLOYMENT.md) for webhook integration
- 🐛 **Found a Bug?** [Open an issue](https://github.com/alexeykrol/supabase-wordpress/issues)
- 💡 **Feature Request?** [Open an issue](https://github.com/alexeykrol/supabase-wordpress/issues) and vote 👍

**See all issues:** https://github.com/alexeykrol/supabase-wordpress/issues

---

## 🏗️ Architecture

```
┌─────────────┐
│   Browser   │
│  (User)     │
└──────┬──────┘
       │ 1. Click "Login with Google"
       ▼
┌─────────────────┐
│  Supabase Auth  │ ← OAuth providers (Google, Facebook, etc.)
│  (supabase.co)  │
└────────┬────────┘
         │ 2. Redirect with access_token (JWT)
         ▼
┌──────────────────────┐
│  WordPress Plugin    │
│  REST API Endpoint   │ ← /wp-json/supabase-auth/callback
│                      │
│  1. Verify JWT (JWKS)│
│  2. Find/Create User │
│  3. Set WP Session   │
└──────────┬───────────┘
           │ 3. WordPress user logged in
           ▼
┌──────────────────────┐
│   WordPress Site     │ ← User can access WP content, plugins, admin
│   (wp_users)         │
└──────────────────────┘
```

**Key Components:**
- **Frontend:** Vanilla JavaScript with `@supabase/supabase-js` (CDN)
- **Backend:** WordPress REST API + PHP JWT verification
- **Security:** RS256 signature validation via Supabase JWKS endpoint
- **Storage:** WordPress `wp_users` + `wp_usermeta` tables

---

## 🔐 Security

This plugin follows WordPress security best practices:

- ✅ **Never trust client input** - All JWT validation on server
- ✅ **Defense in depth** - Multiple security layers (CSRF, rate limiting, headers)
- ✅ **Audit trail** - All authentication events logged with IP
- ✅ **Secure defaults** - Email verification required for OAuth
- ✅ **Regular updates** - `composer audit` runs clean (0 vulnerabilities)

**Security Policy:** See [SECURITY.md](Init/SECURITY.md)

**Found a vulnerability?** Please report privately to the maintainer.

---

## 📖 Documentation

### Feature Overview:
- **[FEATURES.md](FEATURES.md)** - 🎯 Complete feature list (129 features organized by category!)

### Quick Start & Deployment:
- **[QUICK_SETUP_CHECKLIST.md](QUICK_SETUP_CHECKLIST.md)** - 1-page deployment guide (5 minutes)
- **[PRODUCTION_SETUP.md](PRODUCTION_SETUP.md)** - Detailed AIOS/Cloudflare/LiteSpeed configuration

### Security & Architecture:
- **[SECURITY_ROLLBACK_SUMMARY.md](SECURITY_ROLLBACK_SUMMARY.md)** - Security architecture explained
- **[SECURITY_RLS_POLICIES_FINAL.sql](SECURITY_RLS_POLICIES_FINAL.sql)** - RLS policies for Supabase

### Database Schema:
- **[supabase-tables.sql](supabase-tables.sql)** - Create tables in Supabase

### Webhook System:
- **[webhook-system/README.md](webhook-system/README.md)** - Webhook system overview
- **[webhook-system/DEPLOYMENT.md](webhook-system/DEPLOYMENT.md)** - Deployment guide with critical issues
- **[webhook-system/ARCHITECTURE.md](webhook-system/ARCHITECTURE.md)** - Technical architecture details
- **[webhook-system/OAUTH-SETUP-GUIDE.md](webhook-system/OAUTH-SETUP-GUIDE.md)** - Google & Facebook OAuth setup

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Report bugs** - [Open an issue](https://github.com/alexeykrol/supabase-wordpress/issues)
2. **Suggest features** - Vote 👍 on existing issues or create new ones
3. **Submit PRs** - Code improvements, bug fixes, documentation
4. **Share feedback** - Let us know what you'd like to see!

**Before submitting:**
- Test your changes thoroughly
- Follow WordPress coding standards
- Update documentation if needed

---

## 📜 License

MIT License - see [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Alexey Krol

---

## 🙏 Acknowledgments

- **[Supabase](https://supabase.com)** - Amazing open-source Firebase alternative
- **[firebase/php-jwt](https://github.com/firebase/php-jwt)** - JWT verification library
- **[Claude Code Starter Framework](https://github.com/anthropics/claude-code-starter)** - Documentation structure
- **[Build AI Agents Course](https://github.com/alexeykrol/build-ai-agents-course)** - This plugin was developed as part of a comprehensive course on AI-assisted development (370-470 person-hours of work completed in 21 days)

---

## 🔗 Links

- **GitHub Repository:** https://github.com/alexeykrol/supabase-wordpress
- **Issues & Roadmap:** https://github.com/alexeykrol/supabase-wordpress/issues
- **Live Demo:** https://alexeykrol.com
- **Supabase Docs:** https://supabase.com/docs/guides/auth

---

**Made with ❤️ for the WordPress + Supabase community**

*Want to support development? ⭐ Star the repo on GitHub!*
