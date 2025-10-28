# 🔗 Webhook System v0.8.0

**Real-time webhook delivery to n8n/make when users register**

---

## 🎯 Подход: UI First, Backend Later

**Философия:**
> "Практика показывает, что потом переделки в UI ведут к головной боли"

**Strategy:**
1. ✅ **Add UI tab** — Создать вкладку в текущем плагине (БЕЗ backend)
2. ⏳ **Test & iterate** — Проверить UI/UX, получить feedback
3. ⏳ **Implement backend** — AJAX handlers + Supabase integration (ПОСЛЕ утверждения UI)
4. ⏳ **Deploy** — Supabase SQL + Edge Function
5. ⏳ **Production** — Полная интеграция

---

## 🚀 Quick Start

### Шаг 1: Добавить Вкладку (5 минут)

**Читай:** [`QUICK-START.md`](./QUICK-START.md)

1. Следуй инструкциям в `ADD-WEBHOOKS-TAB.md`
2. Добавь 3-ю вкладку "Webhooks" в Settings
3. Протестируй navigation между вкладками

**Результат:**
- Новая вкладка "🪝 Webhooks" появится
- Placeholder "Under Construction" (пока что)
- Другие вкладки не затронуты

### Шаг 2: Добавить Полный UI (10 минут)

1. Открой `supabase-bridge.php`
2. Замени функцию `sb_render_webhooks_tab()` кодом из `webhooks-tab-full-code.php`
3. Протестируй формы, collapsible sections, test button

**Результат:**
- ✅ Configuration form (Enable, Webhook URL)
- ✅ Status indicators
- ✅ Supabase Setup instructions
- ✅ Test button (stub)
- ✅ Architecture overview

### Шаг 3: Дать Feedback

**После тестирования UI напиши:**
- ✅ Что работает хорошо
- ❌ Что непонятно/неудобно
- 💡 Предложения по улучшению

---

## 📦 What's Inside

```
webhook-system/
├── README.md                       ← You are here
├── QUICK-START.md                  ← START HERE! Step-by-step guide
├── ADD-WEBHOOKS-TAB.md             ← Instructions for adding tab
├── webhooks-tab-full-code.php      ← Full UI code (copy-paste ready)
├── ARCHITECTURE.md                 ← System architecture + critical technical details
├── DEPLOYMENT.md                   ← Backend deployment guide + critical issues
├── OAUTH-SETUP-GUIDE.md            ← Google & Facebook OAuth configuration
├── webhook-system.sql              ← Database schema
├── send-webhook-function.ts        ← Edge Function code v0.8.1
└── OUTBOX-PATTERN-PROPOSAL.md      ← Future enhancement proposal
```

---

## ✨ Features

### Current (v0.8.0 - UI Only)
✅ **Full UI** — Configuration form, setup instructions, test section
✅ **Tab integration** — Adds 3rd tab to existing Settings page
✅ **No breaking changes** — Doesn't touch current plugin functionality
✅ **Collapsible sections** — Clean, organized UI
✅ **Status indicators** — Visual feedback (✅/❌)

### Coming Soon (After UI Approval)
⏳ **Send Test Webhook** — AJAX handler + Supabase API call
⏳ **Webhook Logs** — Real-time log viewer
⏳ **Auto-refresh** — setInterval polling

---

## 🏗️ Architecture

```
User Registration (WordPress)
    ↓
INSERT wp_user_registrations
    ↓
Database Trigger: trigger_registration_webhook()
    ↓ (async via pg_net.http_post)
Edge Function: send-webhook
    ↓ (3 retries: 1s, 2s, 4s exponential backoff)
n8n/make Webhook Endpoint
    ↓
Update webhook_logs table
```

**Key Features:**
- ✅ Immediate delivery (no cron delays)
- ✅ Automatic retries with exponential backoff
- ✅ Full logging in `webhook_logs` table
- ✅ Secure (SERVICE_ROLE_KEY in Edge Function only)

---

## 📚 Documentation

### For Integration
- **[QUICK-START.md](./QUICK-START.md)** — Step-by-step integration guide
- **[ADD-WEBHOOKS-TAB.md](./ADD-WEBHOOKS-TAB.md)** — Exact code changes needed

### For Architecture
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** — Complete system design

### For Deployment (Later)
- **[DEPLOYMENT.md](./DEPLOYMENT.md)** — Supabase SQL + Edge Function deployment

---

## 🎯 Current Status

**Version:** 0.8.1
**Phase:** ✅ **DEPLOYED & WORKING** (Production Ready)
**Status:** End-to-end webhook delivery working successfully
**Last Updated:** 2025-10-27 (after 12-hour debugging session)

**Deployment Checklist (v0.8.1):**
- [x] **Step 1:** Deploy SQL schema (webhook_logs table, trigger, RLS policies)
- [x] **Step 2:** Deploy Edge Function (send-webhook v0.8.1)
- [x] **Step 3:** Configure environment variables (WEBHOOK_URL, SUPABASE_URL, SERVICE_ROLE_KEY)
- [x] **Step 4:** **CRITICAL:** Disable JWT verification in Edge Function settings
- [x] **Step 5:** **CRITICAL:** Add RLS policies for anon role (INSERT/UPDATE)
- [x] **Step 6:** Enable pg_net extension
- [x] **Step 7:** Test end-to-end webhook delivery
- [x] **Step 8:** Update documentation with critical issues

**⚠️ IMPORTANT:** See [DEPLOYMENT.md](./DEPLOYMENT.md) → "CRITICAL: Read This First!" section before deploying!

---

## 🔄 Development Approach

**Why Add Tab to Existing Plugin?**
> Webhook system is an optional feature, not separate functionality

**Why UI First?**
> "Практика показывает, что потом переделки в UI ведут к головной боли"

**Benefits:**
- ✅ Test UI/UX early without backend complexity
- ✅ Iterate quickly based on feedback
- ✅ No risk to current plugin functionality
- ✅ Easy to rollback if needed

**Strategy:**
1. Add UI tab (minimal changes to existing code)
2. Test & iterate on UI/UX
3. Implement backend only after UI approved
4. Deploy to Supabase
5. Production ready

---

## 📁 File Structure

```
webhook-system/
├── README.md                       # Project overview
├── QUICK-START.md                  # Step-by-step integration
├── ADD-WEBHOOKS-TAB.md             # Code changes for tab
├── webhooks-tab-full-code.php      # Full UI implementation
├── ARCHITECTURE.md                 # System architecture (400+ lines)
├── DEPLOYMENT.md                   # Supabase deployment guide
├── webhook-system.sql              # Database schema (10KB)
├── send-webhook-function.ts        # Edge Function (10KB)
└── OUTBOX-PATTERN-PROPOSAL.md      # Future: Transactional Outbox Pattern
```

---

## 📊 Webhook Payload Format

```json
{
  "event": "user_registered",
  "data": {
    "id": "uuid-v4",
    "user_id": "supabase-user-uuid",
    "user_email": "user@example.com",
    "registration_url": "/services/",
    "thankyou_page_url": "/services-thankyou/",
    "pair_id": "pair-uuid-or-null",
    "registered_at": "2025-10-26T12:34:56.789Z"
  },
  "timestamp": "2025-10-26T12:34:56.789Z"
}
```

---

## 💬 Feedback & Questions

**For UI/UX Feedback:**
- Test UI following QUICK-START.md
- Report what works well / what's unclear
- Suggest improvements

**For Technical Questions:**
- Read ARCHITECTURE.md
- Check DEPLOYMENT.md for deployment details

---

## 🚦 Roadmap

### Phase 1: UI Integration ✅ COMPLETED
- [x] Create webhook tab code
- [x] Create integration instructions
- [x] Test UI in WordPress
- [x] Get feedback
- [x] Iterate on UI based on feedback

### Phase 2: Backend Implementation ✅ COMPLETED
- [x] Implement `sb_webhook_ajax_send_test()`
- [x] Implement `sb_webhook_ajax_get_logs()`
- [x] Add AJAX nonce verification
- [x] Real-time log refresh

### Phase 3: Supabase Deployment ✅ COMPLETED (with critical fixes)
- [x] Deploy SQL schema
- [x] Deploy Edge Function
- [x] Configure environment variables
- [x] End-to-end testing with Make.com
- [x] **FIX:** Disable JWT verification in Edge Function
- [x] **FIX:** Add RLS policies for anon role
- [x] **FIX:** Enable pg_net extension
- [x] **FIX:** Update Edge Function to handle failed webhooks
- [x] **FIX:** Decrypt Supabase URL in WordPress plugin

### Phase 4: Production ✅ COMPLETED
- [x] Finalize integration
- [x] Update documentation
- [x] Create GitHub Release v0.8.1 (upcoming)

### Phase 5: Future Enhancements
- [ ] **Outbox Pattern** - See [OUTBOX-PATTERN-PROPOSAL.md](./OUTBOX-PATTERN-PROPOSAL.md)
  - Zero event loss guarantee
  - Multi-event support (pair.created, subscription.updated, etc.)
  - Automatic retry with exponential backoff
  - Batch processing for high volume
  - GitHub Issue: [#11](https://github.com/alexeykrol/supabase-wordpress/issues/11)

---

## 🛠️ Tech Stack

- **WordPress Admin UI** — Settings page with tabs
- **WordPress Options API** — Settings storage
- **WordPress AJAX** — For backend communication (to be implemented)
- **Supabase PostgreSQL** — Database triggers, webhook_logs table
- **Supabase Edge Functions** — Deno/TypeScript webhook delivery
- **pg_net Extension** — Server-side HTTP calls

---

## 📊 Version History

**v0.8.1** (2025-10-27) - **CURRENT**
- ✅ Fixed JWT authentication (Edge Function JWT verification disabled)
- ✅ Fixed RLS policies for anon role (INSERT/UPDATE)
- ✅ Fixed pg_net extension installation and syntax
- ✅ Fixed Edge Function error handling (failed webhooks)
- ✅ Fixed WordPress encrypted URL decryption
- ✅ Added comprehensive documentation (DEPLOYMENT.md, ARCHITECTURE.md)
- ✅ End-to-end webhook delivery working to Make.com

**v0.8.0** (2025-10-26)
- Initial release (UI + Backend + Deployment)
- Database trigger + Edge Function + WordPress UI
- Known issues: JWT verification, RLS policies, pg_net syntax

---

*Webhook System v0.8.1 — Built for WordPress-Supabase Bridge*
*Created: 2025-10-26*
*Deployed: 2025-10-27*
*Status: ✅ Production Ready*
*Next: GitHub Release + Outbox Pattern Enhancement*
