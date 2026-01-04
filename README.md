# Supabase Bridge (Auth) for WordPress

![Version](https://img.shields.io/badge/version-0.9.12-blue.svg)
![PHP](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0--6.8-21759B.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Security](https://img.shields.io/badge/security-enterprise%20grade-brightgreen.svg)
![Dependencies](https://img.shields.io/badge/dependencies-0%20vulnerabilities-success.svg)
![Production](https://img.shields.io/badge/production-tested-success.svg)

> WordPress plugin for Supabase Auth integration with MemberPress and LearnDash. Supports Google OAuth, Facebook OAuth, Magic Link authentication + Auto-assign memberships and courses + Registration tracking + Enterprise-grade security.

**🎉 Production Ready** | **✅ Tested on [alexeykrol.com](https://alexeykrol.com)** | **🔐 Enterprise-Grade Security**

>
> **🎓 Created to support students of the AI Agents course for beginners:**
> - Full course: [AI Agents Full Course](https://alexeykrol.com/courses/ai_full/) (Russian)
> - For complete beginners: [Free AI Intro Course](https://alexeykrol.com/courses/ai_intro/) (Russian)

---

## 🚀 Quick Start

### Prerequisites

Before installing the plugin, ensure you have:

**1. Supabase Account & Project**
- Register at [supabase.com](https://supabase.com)
- Create a new project
- Note your project URL and Anon Key (Settings → API)

**2. OAuth Provider Setup**

**Google OAuth:**
- Go to [Google Cloud Console](https://console.cloud.google.com/)
- Create OAuth 2.0 credentials
- Add authorized redirect URIs: `https://yourproject.supabase.co/auth/v1/callback`
- Configure in Supabase: Authentication → Providers → Google

**Facebook OAuth:**
- Go to [Facebook Developers](https://developers.facebook.com/)
- Create a new app
- Add Facebook Login product
- Add redirect URI: `https://yourproject.supabase.co/auth/v1/callback`
- Configure in Supabase: Authentication → Providers → Facebook

**3. Required WordPress Plugins**
- **MemberPress** - For membership management (free memberships supported)
- **LearnDash** - For course management and enrollment
- Both plugins must be installed and activated before Supabase Bridge

**4. Cache Plugin Configuration (CRITICAL)**

This plugin requires proper cache configuration to work correctly.

**Required cache exclusions:**

You must exclude the following page from caching (the process is similar in all caching plugins):

```
/test-no-elem-2/
```

This is the authentication callback page with dynamic content. If cached, authentication will fail.

**Also ensure these are excluded** (typically already in default exclusions):
- `/wp-json/*` - WordPress REST API endpoints
- `/wp-admin/*` - WordPress admin area

**How to configure:**
- **LiteSpeed Cache:** Cache → Excludes → "Do Not Cache URIs"
- **WP Rocket:** Settings → Advanced → "Never Cache URL(s)"
- **W3 Total Cache:** Performance → Page Cache → "Never cache the following pages"
- **WP Super Cache:** Advanced → "Rejected URIs"

**IMPORTANT:** Always purge/clear cache after plugin configuration changes.

**5. MemberPress Configuration**

Disable MemberPress default registration (conflicts with Supabase Auth):
- Go to MemberPress → Settings → General
- **Disable "Enable MemberPress Registration"** - uncheck this option
- Save changes
- This prevents duplicate registration forms and conflicts

### Installation (Standard WordPress Method)

1. **Download** the latest release:
   - [supabase-bridge-v0.9.11.zip](https://github.com/alexeykrol/supabase-wordpress/releases/download/v0.9.11/supabase-bridge-v0.9.11.zip)
   - Or build from source: `./build-release.sh` (requires git clone)

2. **Install plugin**:
   - WordPress Admin → Plugins → Add New → Upload Plugin
   - Choose `supabase-bridge-v0.9.11.zip`
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

6. **Configure MemberPress integration** (optional):
   - WordPress Admin → Supabase Bridge → Memberships tab
   - Click "Add New Pair"
   - Select **Landing Page**: Choose the registration page URL (e.g., `/reg_ai_intro/`)
   - Select **Membership**: Choose MemberPress membership to auto-assign
   - Click "Add Pair"
   - Users registering from this landing page will automatically receive this membership

7. **Configure LearnDash course enrollment** (optional):
   - WordPress Admin → Supabase Bridge → Courses tab
   - Click "Add New Pair"
   - Select **Landing Page**: Choose the registration page URL (e.g., `/reg_ai_intro/`)
   - Select **Course**: Choose LearnDash course for auto-enrollment
   - Click "Add Pair"
   - Users registering from this landing page will automatically be enrolled in this course

8. **Configure LearnDash banner visibility** (optional):
   - WordPress Admin → Supabase Bridge → Banner tab
   - Check "Hide enrollment banner" to remove "NOT ENROLLED / Take this Course" banner
   - Click "Apply Changes"
   - **IMPORTANT:** Clear cache after changes:
     - LiteSpeed Cache: WordPress Admin → LiteSpeed Cache → Purge All
     - Browser: Hard refresh (Ctrl+Shift+R on Windows, Cmd+Shift+R on Mac)

9. **Configure Supabase Auth**:
   - Supabase Dashboard → Authentication → Settings
   - **Enable email confirmations**: ON
   - **Password minimum length**: 10
   - Supabase Dashboard → Authentication → URL Configuration
   - **Redirect URLs**: `https://yourdomain.com/*`

10. **Done!** Users can now register, receive memberships, and be enrolled in courses automatically based on their landing page.

---

## 📁 Project Structure

### Main Plugin Files

**Core Files:**
- **`supabase-bridge.php`** - Main plugin file with WordPress integration, admin interface, and REST API endpoints
- **`auth-form.html`** - Authentication form with Google OAuth, Facebook OAuth, and Magic Link (email + 6-digit code)
- **`test-no-elem-2-wordpress-paste.html`** - Callback handler for authentication (paste into WordPress page editor)

**Configuration:**
- **`composer.json`** - PHP dependencies (firebase/php-jwt for JWT verification)
- **`vendor/`** - Composer dependencies (installed via `composer install`)

**Database Schema:**
- **`supabase/supabase-tables.sql`** - Creates tables in Supabase (wp_user_registrations, wp_registration_pairs, etc.)
- **`supabase/SECURITY_RLS_POLICIES_FINAL.sql`** - Row Level Security policies for Supabase

**Documentation:**
- **`README.md`** - This file
- **`CHANGELOG.md`** - Version history and release notes
- **`LICENSE`** - MIT License

### Installation Flow

1. Upload plugin ZIP via WordPress Admin → Plugins → Add New
2. Run SQL scripts in Supabase Dashboard
3. Configure plugin in WordPress Admin → Settings → Supabase Bridge
4. Paste callback handler HTML into WordPress page

---

## ✨ Core Features

### Authentication Methods (Production Tested ✅)
- 🔵 **Google OAuth** - One-click login with Google account
- 🔷 **Facebook OAuth** - Facebook Login integration
- ✉️ **Magic Link (Passwordless)** - Email + 6-digit code (no password needed!)

### Security Features 🔐
- ✅ **Safari Privacy Protection** - Works in Safari Privacy mode (iOS/macOS) with automatic localStorage fallback
- ✅ **JWT Verification** - Server-side RS256 signature validation via JWKS
- ✅ **CSRF Protection** - Origin/Referer validation on all endpoints
- ✅ **Rate Limiting** - 10 requests per 60 seconds per IP
- ✅ **HTTP Security Headers** - CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection
- ✅ **Audit Logging** - Complete authentication event trail with IP tracking
- ✅ **Email Verification** - OAuth providers require verified emails
- ✅ **Open Redirect Protection** - Same-origin validation on redirects
- ✅ **0 Vulnerabilities** - Clean `composer audit` report
- ✅ **4-Layer Security Architecture** - WordPress validation → Supabase RLS → Cloudflare WAF → AIOS

### LMS & Membership Integrations
- ✅ **MemberPress Integration** - Auto-assign FREE memberships on registration
- ✅ **LearnDash Integration** - Auto-enroll users in courses on registration
- ✅ **LearnDash Banner Management** - One-click enrollment banner removal with UI
- ✅ **Landing Page Mapping** - Different memberships/courses per registration source

---

## ⚠️ Important Notes

### Caching Plugins

**⚠️ CRITICAL:** If you use caching plugins (LiteSpeed Cache, WP Rocket, W3 Total Cache, etc.), you **MUST exclude pages with authentication forms from cache**.

**Why?**
- The auth form shortcode `[supabase_auth_form]` generates dynamic content
- Cached pages will show **empty content** instead of the form
- Users won't be able to register or login

**Solution:**

1. **Disable caching** for pages using `[supabase_auth_form]` shortcode:
   - `/reg_ai_intro/` (or your registration page)
   - Any other pages with the auth form

2. **How to exclude pages from cache:**

   **LiteSpeed Cache:**
   - Go to: LiteSpeed Cache → Cache → Excludes
   - Add URI: `/reg_ai_intro/`

   **WP Rocket:**
   - Go to: WP Rocket → Advanced Rules
   - Add "Never Cache URL(s)": `/reg_ai_intro/`

   **W3 Total Cache:**
   - Go to: Performance → Page Cache → Advanced
   - Add "Never cache the following pages": `/reg_ai_intro/`

3. **After adding exclusions:**
   - Clear all cache
   - Test the form appears correctly

**Verification:**
```bash
# Check if page is cached (should return no cache headers)
curl -I https://yoursite.com/reg_ai_intro/ | grep -i cache
```

---

## 📝 Support & Issues

**Production Status:** ✅ Plugin is stable and tested on [alexeykrol.com](https://alexeykrol.com)

**Need Help?**
- 🐛 **Found a Bug?** [Open an issue](https://github.com/alexeykrol/supabase-wordpress/issues)
- 💡 **Feature Request?** [Open an issue](https://github.com/alexeykrol/supabase-wordpress/issues) and vote 👍

**See all issues:** https://github.com/alexeykrol/supabase-wordpress/issues

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

---

## 🔗 Links

- **GitHub Repository:** https://github.com/alexeykrol/supabase-wordpress
- **Issues & Roadmap:** https://github.com/alexeykrol/supabase-wordpress/issues
- **Live Demo:** https://alexeykrol.com
- **Supabase Docs:** https://supabase.com/docs/guides/auth

---

**Made with ❤️ for the WordPress + Supabase community**

*Want to support development? ⭐ Star the repo on GitHub!*
