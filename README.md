# Supabase Bridge (Auth) for WordPress

![Version](https://img.shields.io/badge/version-0.3.5-blue.svg)
![PHP](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-21759B.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Security](https://img.shields.io/badge/security-hardened-brightgreen.svg)
![Dependencies](https://img.shields.io/badge/dependencies-0%20vulnerabilities-success.svg)
![Production](https://img.shields.io/badge/production-tested-success.svg)

> WordPress plugin for Supabase Auth integration. Supports Google OAuth, Facebook OAuth, and Magic Link (passwordless) authentication with JWT verification and WordPress user sync.

**🎉 Production Ready** | **✅ Tested on [questtales.com](https://questtales.com)** | **🔐 Security Hardened**

---

## 🚀 Quick Start

### Installation

1. **Download** the latest release: [supabase-bridge-v0.3.5.zip](https://github.com/alexeykrol/supabase-wordpress/releases)
2. **Upload** to WordPress via Plugins → Add New → Upload Plugin
3. **Activate** the plugin
4. **Configure** Supabase credentials in `wp-config.php`:

```php
// Add to wp-config.php
putenv('SUPABASE_URL=https://yourproject.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGci...');
putenv('SUPABASE_PROJECT_REF=yourproject');
```

5. **Create login page** in WordPress and insert code from `auth-form.html`
6. **Done!** Users can now login with Google, Facebook, or Magic Link

📖 **Full Documentation:** See [`docs/QUICKSTART.md`](docs/QUICKSTART.md)

---

## ✨ Features

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

### WordPress Integration
- ✅ **Automatic User Sync** - Creates WordPress users on first login
- ✅ **Session Management** - WordPress authentication cookies
- ✅ **Supabase User ID Storage** - Links WP user to Supabase `auth.uid()`
- ✅ **Smart Redirects** - Different redirects for new vs existing users
- ✅ **Role Assignment** - Default subscriber role (configurable)

### Developer Experience
- ✅ **Ready-to-use Form** - `auth-form.html` with all 3 auth methods
- ✅ **REST API** - `/wp-json/supabase-auth/callback` and `/logout` endpoints
- ✅ **Environment Variables** - Secure configuration via `wp-config.php`
- ✅ **No Database Changes** - Uses existing `wp_users` and `wp_usermeta`
- ✅ **Composer** - Modern PHP dependency management

---

## 📊 What's New in v0.3.5

### Bug Fixes 🐛
- **Fixed:** Google OAuth "Email verification required" error ([#6](https://github.com/alexeykrol/supabase-wordpress/issues/6))
  - OAuth providers now work even if `email_verified` is NULL/missing in JWT
  - Logic: `true` ✅, `null/missing` ✅, `false` ❌

- **Fixed:** Magic Link registration failing on retry
  - localStorage timing fixed - token marked as processed AFTER WordPress response
  - 100% success rate in production testing (3/3 emails)

- **Fixed:** CSP headers conflict with MemberPress/Alpine.js
  - Conditional CSP - only applies to non-logged-in users

- **Fixed:** .gitignore security issue
  - wp-config files with credentials now properly ignored

### Testing Results ✅
- Magic Link: 100% success (3 different email addresses)
- Google OAuth: Working perfectly
- No duplicate users created
- Proper redirects for new/existing users

**Full Changelog:** See [BACKLOG.md](Init/BACKLOG.md#-version-035---oauth--magic-link-fixes-current)

---

## 🗺️ Roadmap

### Coming Soon

**v0.2.0 - Role Mapping** ([#6](https://github.com/alexeykrol/supabase-wordpress/issues/6))
- Map Supabase roles → WordPress roles (admin, editor, subscriber)
- Update roles on each login
- Configurable via filter hooks

**v0.4.0 - User Metadata Sync** ([#7](https://github.com/alexeykrol/supabase-wordpress/issues/7))
- Sync avatar, first name, last name from OAuth providers
- Custom field mapping via filters

**v0.7.0 - Admin Settings Page** ([#8](https://github.com/alexeykrol/supabase-wordpress/issues/8))
- WordPress UI for redirect URLs, default roles, button styles
- No PHP file editing required

**v0.8.0 - Shortcodes** ([#9](https://github.com/alexeykrol/supabase-wordpress/issues/9))
- `[supabase_login provider="google"]`
- `[supabase_auth_form]`
- Works in any page builder

**Full Roadmap:** See [BACKLOG.md](Init/BACKLOG.md)

---

## 🚨 Known Issues & Improvements

We're actively working on improving the plugin UX. Help us prioritize by 👍 voting on issues!

### Critical UX Issues 🔴

1. **[#1 - Setup requires FTP access to copy form code](https://github.com/alexeykrol/supabase-wordpress/issues/1)** 🔥
   - Current: Users need FTP to retrieve `auth-form.html` code
   - Planned: Embed code in setup page with copy button

2. **[#2 - Manual setup instead of automated WordPress UI](https://github.com/alexeykrol/supabase-wordpress/issues/2)** 🔥
   - Current: Manual page creation, code copy-paste
   - Planned: Settings page with page selectors + auto-setup

3. **[#3 - Plaintext credentials in wp-config.php](https://github.com/alexeykrol/supabase-wordpress/issues/3)** 🔥
   - Current: Supabase keys in plaintext config file
   - Planned: Encrypted storage in database with UI

4. **[#4 - Confusing auth-form.html structure](https://github.com/alexeykrol/supabase-wordpress/issues/4)** 🔥
   - Current: 1211 lines with 142 lines of comments
   - Planned: Separate clean code from documentation

5. **[#5 - No UI for Thank You page configuration](https://github.com/alexeykrol/supabase-wordpress/issues/5)** 🔥
   - Current: Must edit JavaScript code manually
   - Planned: Settings page with page dropdown

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

- **[Quick Start Guide](docs/QUICKSTART.md)** - Get running in 5 minutes
- **[Installation Guide](docs/INSTALL.md)** - Detailed setup instructions
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Production deployment checklist
- **[Redirect Guide](docs/AUTH-FORM-REDIRECT-GUIDE.md)** - Configure redirect behavior
- **[Debug Guide](docs/DEBUG.md)** - Troubleshooting common issues
- **[Architecture](Init/ARCHITECTURE.md)** - Technical deep dive
- **[Backlog](Init/BACKLOG.md)** - Feature roadmap and status

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Report bugs** - [Open an issue](https://github.com/alexeykrol/supabase-wordpress/issues)
2. **Suggest features** - Vote 👍 on existing issues or create new ones
3. **Submit PRs** - Especially for critical UX issues (#1-#5)
4. **Improve docs** - Documentation PRs always welcome
5. **Share feedback** - Let us know what you'd like to see!

**Contribution Guidelines:** See [WORKFLOW.md](Init/WORKFLOW.md)

---

## 📜 License

MIT License - see [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Alexey Krol

---

## 🙏 Acknowledgments

- **[Supabase](https://supabase.com)** - Amazing open-source Firebase alternative
- **[firebase/php-jwt](https://github.com/firebase/php-jwt)** - JWT verification library
- **Claude Code Starter Framework** - Documentation structure

---

## 🔗 Links

- **GitHub Repository:** https://github.com/alexeykrol/supabase-wordpress
- **Issues & Roadmap:** https://github.com/alexeykrol/supabase-wordpress/issues
- **Live Demo:** https://questtales.com
- **Supabase Docs:** https://supabase.com/docs/guides/auth

---

**Made with ❤️ for the WordPress + Supabase community**

*Want to support development? ⭐ Star the repo on GitHub!*
