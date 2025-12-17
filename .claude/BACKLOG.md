# BACKLOG — Supabase Bridge

*Framework: Claude Code Starter v2.3.1*
*Last Updated: 2025-12-16*

---

## Current Status

**Version:** 0.9.1 (Production Ready)
**Phase:** Maintenance

---

## Active Sprint

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
