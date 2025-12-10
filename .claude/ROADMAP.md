# ROADMAP — Supabase Bridge

*Framework: Claude Code Starter v2.2*
*Last Updated: 2025-12-10*

> 🗺️ Strategic roadmap for future development
>
> **Workflow:** IDEAS.md → ROADMAP.md → BACKLOG.md

---

## 📊 Version History

| Version | Status | Description |
|---------|--------|-------------|
| v0.1.0 | ✅ Released | Core Authentication |
| v0.3.0 | ✅ Released | Multi-Provider Support |
| v0.3.3 | ✅ Released | Security Hardening |
| v0.3.5 | ✅ Released | Bug Fixes & Testing |
| v0.4.1 | ✅ Released | UX Improvements |
| v0.7.0 | ✅ Released | Analytics & Multi-Site |
| v0.8.1 | ✅ Released | Webhook System |
| v0.2.0 | 📋 Planned | Role Mapping |
| v0.4.0 | 📋 Planned | User Metadata Sync |
| v0.5.0 | 📋 Planned | Email/Password Auth |

---

## 🎯 Near-Term (Next 3 Releases)

### v0.2.0 - Role Mapping
**Priority:** High
**Status:** Planned

Map Supabase user roles to WordPress roles automatically.

**Requirements:**
- Read role from JWT `app_metadata` or custom claim
- Map Supabase roles to WordPress roles (admin, editor, subscriber, etc.)
- Update user role on each login (handle role changes)
- Configurable role mapping (via filter hook or config)

**Benefits:**
- WordPress plugins/themes respect user capabilities
- Supabase as single source of truth for permissions
- Automatic permission sync across systems

---

### v0.4.0 - User Metadata Sync
**Priority:** High
**Status:** Planned

Sync additional user metadata from Supabase to WordPress.

**Requirements:**
- Extract metadata from JWT `user_metadata` claim
- Map fields: `first_name`, `last_name`, `avatar_url`, etc.
- Store in WordPress usermeta table
- Handle missing/optional fields gracefully
- Support custom metadata mappings via filter

**Benefits:**
- Rich user profiles in WordPress
- Avatar synced from OAuth providers
- Custom user data accessible to WordPress themes/plugins

---

### v0.5.0 - Email/Password Authentication
**Priority:** Medium
**Status:** Planned

Add support for native Supabase email/password login.

**Requirements:**
- Frontend login form with email/password fields
- Use `supabase.auth.signInWithPassword()`
- Same callback flow as OAuth (JWT → WordPress)
- Registration form support (`signUp()`)
- Password reset flow (`resetPasswordForEmail()`)

**Benefits:**
- No dependency on external OAuth providers
- Traditional login UX for users who prefer it
- Password management handled by Supabase

---

## 🔮 Medium-Term (v0.6.0 - v0.9.0)

### v0.7.0 - Admin Settings Page (Enhanced)
- WordPress admin UI for non-sensitive plugin settings
- Configure: redirect URLs, default user role, button text
- Preview OAuth button styles

### v0.8.0 - Shortcodes for Login Buttons
- `[supabase_login provider="google"]` shortcode
- Customizable button text and CSS classes
- Support multiple providers in one shortcode

---

## 🌟 Long-Term (v1.0+)

### v1.0.0 - Multi-Provider Buttons
- Pre-built UI component with multiple OAuth provider buttons
- Auto-detect available providers from Supabase project
- Responsive grid layout with provider icons

### v1.1.0 - Supabase Database Integration
- Link WordPress users with Supabase database records via RLS
- Utility functions to query Supabase database
- Helper functions for CRUD operations

### v1.2.0 - SSO for Multiple WordPress Sites
- Single Sign-On across multiple WordPress sites
- Shared Supabase project across multiple WP installs
- Automatic session sync between sites

### v1.3.0 - WP-CLI Commands
- `wp supabase sync-users` - Sync existing WP users to Supabase
- `wp supabase test-connection` - Verify configuration
- `wp supabase clear-cache` - Clear JWKS cache

---

*This file contains strategic planning, not daily tasks*
*For current work items, see BACKLOG.md*
*For technical debt, see BACKLOG.md*
