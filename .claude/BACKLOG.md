# BACKLOG — Supabase Bridge

*Framework: Claude Code Starter v2.3.1*
*Last Updated: 2025-12-24*

---

## Current Status

**Version:** 0.9.10 (Production Ready)
**Phase:** Maintenance

---

## Active Sprint

### Completed (v0.9.10) - PKCE Flow Support
- [x] Added PKCE flow support to callback handler (both hash fragment and query string)
- [x] Fixed dotsTimer bug in callback handler (ReferenceError: countdownTimer)
- [x] Investigated OAuth flow changes in Supabase SDK
- [x] Rollback and re-apply Phase 19 changes with fixes
- [x] Tested OAuth in Chrome, Safari, Firefox - all browsers working
- [x] Documented floating CDN dependency issue and solution

### Completed (v0.9.9) - Safari Privacy & UX Polish
- [x] Safari Privacy Protection (safeStorage wrapper with in-memory fallback)
- [x] Russian localization for all UI elements
- [x] UX improvements - eliminated flickering screens
- [x] 3-step troubleshooting instructions in footer
- [x] Instant loading screen for callback page
- [x] Animated dots instead of countdown timer
- [x] Security incident response - SSH keys removed from git history
- [x] Repository cleanup - removed 51 debug files (-20,315 lines)
- [x] Reorganized structure (security/, supabase/ folders)
- [x] CLAUDE.md Completion Protocol improvements
- [x] README.md fundamental overhaul

### Completed (v0.9.8)
- [x] Comprehensive security scanning system (bash-based credential detection)
- [x] Automated dialog file cleanup script with [REDACTED] markers
- [x] Integration testing for Registration Pairs, MemberPress, LearnDash
- [x] Unified test runner with smoke tests, unit tests, security scanning
- [x] LearnDash banner patch OPcache improvements
- [x] Git history cleanup with BFG Repo-Cleaner (removed all credentials)
- [x] .gitignore improvements (wildcard rules for dialog files)

### Completed (v0.9.7)
- [x] Return-to-Origin Login Flow - user returns to page where they clicked "Login"
- [x] Implemented `document.referrer` tracking on login page (localStorage)
- [x] Added redirect logic to callback handler (reads from localStorage)
- [x] Created `[supabase_auth_callback]` shortcode for unified architecture
- [x] Unified shortcode system - both auth pages use shortcodes for automatic updates
- [x] Tested Google OAuth login from multiple pages
- [x] Tested Facebook OAuth login from multiple pages
- [x] Tested Magic Link login from multiple pages
- [x] Verified in Chrome, Safari, Firefox (normal + incognito modes)

### Completed (v0.9.6)
- [x] Two-Page Authentication Architecture Refactoring
- [x] Analyzed Chrome/Safari hash detection issue - found duplicate callback code
- [x] Implemented two-page architecture: form page + callback handler
- [x] Created dedicated callback page `/test-no-elem-2/` with clean handler
- [x] Added `redirect_to` parameter support for login redirects
- [x] Removed ~112 lines of duplicate callback code from `auth-form.html`
- [x] Separated concerns: form display vs authentication processing
- [x] Fixed OAuth redirect URLs to point to callback page
- [x] Tested in Chrome, Safari, Firefox (normal + incognito modes)
- [x] Verified Google OAuth and Facebook OAuth login flows work correctly

### Completed (v0.9.1)
- [x] LearnDash Banner Management UI - WordPress Admin tab for banner patch control
- [x] New "🎓 Banner" tab with checkbox to enable/disable banner removal
- [x] Real-time patch status indicator with color-coded badges
- [x] One-click apply/restore via AJAX with automatic backups
- [x] Warning notifications after LearnDash updates
- [x] Backward compatible with old patch versions

### Completed (v0.9.0)
- [x] MemberPress Integration - Auto-assign FREE memberships on registration
- [x] New Memberships tab with CRUD operations
- [x] LearnDash Integration - Auto-enroll users in courses on registration
- [x] New Courses tab with CRUD operations
- [x] LearnDash banner removal patch script (idempotent, upgrade-safe)
- [x] Remove redundant Supabase sync for Registration Pairs
- [x] Test all integrations with MemberPress 1.x and LearnDash 4.x

### Completed (v0.8.5)
- [x] Fix Registration Pairs tracking accuracy (Referer → explicit POST param)
- [x] Implement Edit Pair functionality
- [x] Add custom delete confirmation modal (Safari compatible)
- [x] Fix registration logging bug (remove thankyou_page_url column)
- [x] Improve HTTP 409 duplicate callback handling
- [x] Add RLS policies for anon role on registration tables

*No active development tasks. Project is in maintenance mode.*

---

## Next Up

When development resumes, pick from ROADMAP.md:

1. **v0.10.0 - Role Mapping** (High priority)
2. **v0.11.0 - User Metadata Sync** (High priority)
3. **v0.12.0 - Email/Password Auth** (Medium priority)

---

## Technical Debt

### High Priority
- [ ] PHPStan analysis — static type checking
- [ ] WordPress Coding Standards (WPCS) compliance
- [ ] Unit tests for JWT verification
- [ ] Integration tests with mock Supabase

### Medium Priority
- [ ] Video tutorial (YouTube)
- [ ] Supabase RLS examples
- [ ] Troubleshooting guide

### Low Priority
- [ ] Lazy script loading (only on login pages)
- [ ] Minified inline JS
- [ ] Database query optimization

---

## Known Issues

*No open issues. All resolved issues archived in git history.*

---

## References

- **Strategic planning:** [ROADMAP.md](./ROADMAP.md)
- **Release history:** [CHANGELOG.md](../CHANGELOG.md)
- **Architecture:** [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Ideas:** [IDEAS.md](./IDEAS.md)

---

*This file tracks current work only*
*For history, see CHANGELOG.md*
*For future plans, see ROADMAP.md*
