# Webhook System Deployment Guide (v0.8.1)

**Purpose:** Step-by-step instructions to deploy webhook system for n8n/make integration
**Prerequisites:** Supabase project, WordPress with supabase-bridge.php, n8n/make account
**Estimated Time:** 20-30 minutes

---

## ⚠️ CRITICAL: Read This First!

**These issues cost 12 hours of debugging. Don't skip these fixes:**

### 🔴 Issue #1: Edge Function Returns HTTP 401 (MOST COMMON)

**Problem:** pg_net sends requests WITHOUT Authorization header → Supabase blocks with 401

**Solution:** Disable JWT verification for Edge Function

1. Supabase Dashboard → Edge Functions → send-webhook
2. Click **"Details"** tab (or "Settings")
3. Find toggle: **"Verify JWT"** or **"Require JWT verification"**
4. **Turn it OFF** (disabled)
5. Save

**Why:** Supabase Edge Functions require JWT by default. pg_net.http_post() doesn't send auth headers. Unlike n8n/make webhooks, Supabase blocks unauthenticated requests.

**Alternative (more secure but complex):** Add Authorization header to pg_net call in trigger function (see Step 2D below)

---

### 🔴 Issue #2: Trigger Cannot Write to webhook_logs (RLS Blocks INSERT)

**Problem:** Database trigger runs as `anon` role → RLS policy blocks INSERT → webhook_logs stays empty

**Solution:** Add RLS policy to allow anon INSERT

```sql
-- Add this to your SQL deployment (already in webhook-system.sql v0.8.1+)
DROP POLICY IF EXISTS "Anon can insert webhook logs" ON webhook_logs;

CREATE POLICY "Anon can insert webhook logs"
ON webhook_logs
FOR INSERT
TO anon
WITH CHECK (true);
```

**Why:** Triggers inherit caller's role. WordPress uses `anon` key → trigger runs as `anon` → RLS blocks unless explicitly allowed.

---

### 🔴 Issue #3: pg_net Not Installed (Webhook Logs Stuck at "pending")

**Problem:** `net.http_post()` exists but does nothing → requests never sent → status stays "pending"

**Solution:** Install pg_net extension

```sql
CREATE EXTENSION IF NOT EXISTS pg_net;

-- Verify
SELECT extname, extversion FROM pg_extension WHERE extname = 'pg_net';
-- Expected: 1 row with version 0.19.5+
```

**Why:** pg_net is optional extension. Without it, `net.http_post()` function exists (no error) but requests are never queued/processed.

---

### 🔴 Issue #4: Edge Function Code Bug (Failed Webhooks Stay "pending")

**Problem:** When webhook endpoint returns 401/404, Edge Function retries but NEVER updates webhook_logs to "failed"

**Solution:** Use updated Edge Function code (send-webhook-function.ts v0.8.1)

**Bug location:**
```typescript
// OLD CODE (v0.8.0) - BUG
if (response.ok) {
  // Updates to 'sent' ✅
} else {
  // Does NOTHING - stays 'pending' ❌
}
```

**Fixed code (v0.8.1):**
```typescript
// After retry loop ends
await supabase.from("webhook_logs").update({
  status: "failed",
  error_message: lastError,
  retry_count: MAX_RETRIES
}).eq("registration_id", registrationId)...
```

**Why:** HTTP 401/404 are valid responses (not exceptions), so `catch` block never runs. Logs stay "pending" forever even after 3 failed retries.

---

### 🟡 Issue #5: WordPress Plugin Syntax (Older Code May Fail)

**Problem:** Current trigger function may have wrong pg_net syntax or hardcoded "YOUR_PROJECT_REF"

**Solution:** Use SQL code from WordPress Admin UI (Settings → Webhooks tab)

**Why:** WordPress plugin (supabase-bridge.php v0.8.3+) auto-generates correct URL from encrypted settings. Copy SQL from there, not from webhook-system.sql file.

---

## ✅ Quick Deployment Checklist (With Critical Fixes)

Use this checklist to avoid all issues above:

- [ ] **Step 0:** Read all 5 critical issues above
- [ ] **Step 1:** Create n8n/make webhook, get URL
- [ ] **Step 2A:** Deploy database schema (webhook-system.sql)
- [ ] **Step 2B:** Add RLS policy for anon INSERT (see Issue #2)
- [ ] **Step 2C:** Install pg_net extension (see Issue #3)
- [ ] **Step 3A:** Deploy Edge Function (send-webhook v0.8.1 code)
- [ ] **Step 3B:** **DISABLE JWT verification** (see Issue #1) ← CRITICAL
- [ ] **Step 3C:** Set WEBHOOK_URL secret
- [ ] **Step 4:** Test with real registration
- [ ] **Step 5:** Verify webhook_logs shows status='sent' (not 'pending')

---

## 📋 Pre-Deployment Checklist

- [ ] Supabase project created (free tier OK)
- [ ] WordPress plugin `supabase-bridge.php` installed and configured (v0.7.0+)
- [ ] n8n or make.com account with webhook workflow created
- [ ] Supabase CLI installed ([docs](https://supabase.com/docs/guides/cli))
- [ ] Node.js 18+ installed (for local testing)

---

## 🔧 Step 1: Prepare n8n/make Webhook

### n8n Setup:

1. Create new workflow in n8n
2. Add "Webhook" trigger node
3. Set Method: `POST`
4. Set Response: `Immediately`
5. Copy webhook URL (example: `https://hooks.n8n.cloud/webhook/abc123xyz`)
6. Test webhook with curl:
   ```bash
   curl -X POST https://hooks.n8n.cloud/webhook/abc123xyz \
     -H "Content-Type: application/json" \
     -d '{"event":"test","data":{"user_email":"test@example.com"}}'
   ```
7. Verify request received in n8n (check workflow executions)

### make.com Setup:

1. Create new scenario in make
2. Add "Webhooks" → "Custom webhook" module
3. Click "Add" to create webhook
4. Copy webhook URL (example: `https://hook.make.com/abc123xyz`)
5. Test webhook with curl (same as n8n example above)
6. Verify request received in make (check webhook history)

**Save webhook URL for Step 3!**

---

## 🗄️ Step 2: Deploy Database Schema

### A. Connect to Supabase SQL Editor

1. Go to [Supabase Dashboard](https://app.supabase.com/)
2. Select your project
3. Navigate to **SQL Editor** (left sidebar)
4. Click **New query**

### B. Run webhook-system.sql

1. Copy contents of `webhook-system/webhook-system.sql` from this repo
2. Paste into SQL Editor
3. Click **Run** (or press Cmd/Ctrl + Enter)
4. Verify success messages:
   - ✅ `CREATE TABLE` webhook_logs
   - ✅ `CREATE FUNCTION` trigger_registration_webhook
   - ✅ `CREATE TRIGGER` on_registration_send_webhook
   - ✅ `CREATE POLICY` (3 RLS policies)

### C. Verify Deployment

Run verification queries:

```sql
-- 1. Check table exists
SELECT table_name, table_type
FROM information_schema.tables
WHERE table_name = 'webhook_logs';
-- Expected: 1 row (webhook_logs | BASE TABLE)

-- 2. Check trigger exists
SELECT trigger_name, event_manipulation, event_object_table
FROM information_schema.triggers
WHERE trigger_name = 'on_registration_send_webhook';
-- Expected: 1 row (on_registration_send_webhook | INSERT | wp_user_registrations)

-- 3. Check RLS policies
SELECT policyname, cmd
FROM pg_policies
WHERE tablename = 'webhook_logs';
-- Expected: 3 rows (SELECT, INSERT, UPDATE policies)

-- 4. Test webhook_logs table (should be empty)
SELECT COUNT(*) FROM webhook_logs;
-- Expected: 0
```

**If all queries succeed → Database ready! ✅**

---

## ☁️ Step 3: Deploy Edge Function

### A. Install Supabase CLI (if not installed)

```bash
# macOS (Homebrew)
brew install supabase/tap/supabase

# Windows (Scoop)
scoop bucket add supabase https://github.com/supabase/scoop-bucket.git
scoop install supabase

# Linux
brew install supabase/tap/supabase

# Verify installation
supabase --version
```

### B. Initialize Supabase Project (if first time)

```bash
# Navigate to project root
cd /path/to/your/wordpress-project

# Login to Supabase CLI
supabase login

# Link to your project (get Project ID from Supabase Dashboard > Settings > General)
supabase link --project-ref YOUR_PROJECT_ID
```

### C. Create Edge Function Directory

```bash
# Create functions directory structure
mkdir -p supabase/functions/send-webhook

# Copy Edge Function code
cp webhook-system/send-webhook-function.ts supabase/functions/send-webhook/index.ts
```

### D. Deploy Edge Function

```bash
# Deploy to Supabase
supabase functions deploy send-webhook

# Expected output:
# Deploying send-webhook (project ref: your-project-id)
# ✓ Deployed send-webhook
```

### E. Set Environment Variables

**Option 1: Via Supabase Dashboard (Recommended)**

1. Go to Supabase Dashboard → **Edge Functions**
2. Click on `send-webhook` function
3. Go to **Secrets** tab
4. Add 3 secrets:

| Secret Name | Value | Example |
|-------------|-------|---------|
| `SUPABASE_URL` | Your Supabase URL | `https://abc123.supabase.co` |
| `SUPABASE_SERVICE_ROLE_KEY` | Service Role Key | `eyJhbGci...` (from Settings > API) |
| `WEBHOOK_URL` | n8n/make webhook URL | `https://hooks.n8n.cloud/webhook/...` |

**Option 2: Via Supabase CLI**

```bash
# Set secrets via CLI
supabase secrets set SUPABASE_URL=https://your-project.supabase.co
supabase secrets set SUPABASE_SERVICE_ROLE_KEY=eyJhbGci...
supabase secrets set WEBHOOK_URL=https://hooks.n8n.cloud/webhook/abc123
```

**⚠️ IMPORTANT: Never commit Service Role Key to git!**

### F. Verify Edge Function

```bash
# Test Edge Function manually via curl
curl -X POST https://YOUR_PROJECT.supabase.co/functions/v1/send-webhook \
  -H "Authorization: Bearer YOUR_SERVICE_ROLE_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "user_registered",
    "data": {
      "id": "00000000-0000-0000-0000-000000000000",
      "user_id": "test-user-id",
      "user_email": "test@example.com",
      "registration_url": "/test/",
      "thankyou_page_url": "/test-thankyou/",
      "pair_id": null,
      "registered_at": "2025-10-26T12:00:00Z"
    },
    "timestamp": "2025-10-26T12:00:00Z"
  }'

# Expected response:
# {"success":true,"message":"Webhook delivered successfully","registration_id":"..."}
```

**Check n8n/make for received test webhook!**

---

## 🔌 Step 4: Enable pg_net Extension

**⚠️ CRITICAL: Without pg_net, database trigger cannot call Edge Function!**

### A. Enable pg_net in Supabase

1. Go to Supabase Dashboard → **Database** → **Extensions**
2. Search for `pg_net`
3. Toggle **ON**
4. Confirm activation

**Alternative: Via SQL Editor**

```sql
CREATE EXTENSION IF NOT EXISTS pg_net;

-- Verify installation
SELECT * FROM pg_available_extensions WHERE name = 'pg_net';
-- Expected: 1 row with installed_version NOT NULL
```

### B. Configure Database Settings

**⚠️ REQUIRED: Tell database trigger where Edge Function is located**

```sql
-- Set Edge Function URL (replace with your project ID)
ALTER DATABASE postgres
SET app.settings.supabase_url = 'https://YOUR_PROJECT_ID.supabase.co';

-- Set Service Role Key (replace with your key from Dashboard > Settings > API)
ALTER DATABASE postgres
SET app.settings.service_role_key = 'eyJhbGci...YOUR_SERVICE_ROLE_KEY';

-- Optional: Set full Edge Function URL (auto-constructed if not set)
ALTER DATABASE postgres
SET app.settings.edge_function_url = 'https://YOUR_PROJECT_ID.supabase.co/functions/v1/send-webhook';
```

**Verify settings:**

```sql
SELECT name, setting
FROM pg_settings
WHERE name LIKE 'app.settings.%';
-- Expected: 2-3 rows (supabase_url, service_role_key, optional edge_function_url)
```

---

## 🔧 Step 5: Integrate WordPress Admin UI

### A. Backup supabase-bridge.php

```bash
# Create backup before modifying
cp wp-content/plugins/supabase-bridge.php wp-content/plugins/supabase-bridge.php.backup
```

### B. Add Webhooks Tab to Settings Page

**Find function `sb_render_settings_page()` in `supabase-bridge.php`**

1. Add tab button to navigation:

```php
// Around line 50-60 (in tab navigation section)
<a href="#settings" class="nav-tab">Settings</a>
<a href="#webhooks" class="nav-tab">Webhooks</a> <!-- ADD THIS LINE -->
<a href="#logs" class="nav-tab">Logs</a>
```

2. Add tab content:

```php
// Around line 150-200 (in tab content area)
<div id="webhooks" class="tab-content">
    <?php sb_render_webhooks_tab(); ?>
</div>
```

### C. Copy WordPress Admin UI Code

**Option 1: Include file (Recommended)**

1. Copy `webhook-system/wordpress-admin-ui.php` to plugin directory:
   ```bash
   cp webhook-system/wordpress-admin-ui.php wp-content/plugins/wordpress-admin-ui.php
   ```

2. Include in `supabase-bridge.php` (top of file, after initial comments):
   ```php
   // Include Webhook System UI (v0.8.0)
   require_once plugin_dir_path(__FILE__) . 'wordpress-admin-ui.php';
   ```

**Option 2: Inline code**

Copy entire contents of `wordpress-admin-ui.php` and paste into `supabase-bridge.php` before closing `?>` tag.

### D. Verify Integration

1. Go to WordPress Admin → **Settings** → **Supabase Bridge**
2. Verify **Webhooks** tab appears
3. Click **Webhooks** tab
4. Should see:
   - "Test Webhook Delivery" section with button
   - "Webhook Logs (Last 20)" section with table

**If tab doesn't appear: Check PHP error logs**

---

## ✅ Step 6: End-to-End Testing

### A. Test Webhook Delivery

1. Go to WordPress Admin → Settings → Supabase Bridge → **Webhooks** tab
2. Click **"Send Test Webhook"** button
3. Expected behavior:
   - Button shows "Sending..." (disabled)
   - Success message appears: "✅ Test webhook sent!"
   - Logs table refreshes after 2 seconds
   - New entry appears in logs with status:
     - ⏳ **Pending** (while Edge Function processing)
     - ✅ **Sent** (after successful delivery)
     - ❌ **Failed** (if delivery failed)

### B. Verify in n8n/make

1. Check n8n workflow executions (or make scenario history)
2. Latest execution should show:
   ```json
   {
     "event": "user_registered",
     "data": {
       "user_email": "test+123456789@example.com",
       "registration_url": "/test-registration/",
       ...
     },
     "timestamp": "2025-10-26T12:34:56.789Z"
   }
   ```

### C. View JSON Payload

1. In WordPress Admin logs table, click **"View JSON"** button
2. Modal popup shows formatted JSON payload
3. Verify all fields present (id, user_id, user_email, etc.)

### D. Test Real Registration Flow

1. Create real test user registration:
   - Open registration page in incognito browser
   - Complete registration form
   - Submit

2. Check webhook logs in WordPress Admin (should auto-refresh)
3. Verify webhook sent to n8n/make
4. Check `webhook_logs` table in Supabase:
   ```sql
   SELECT
     created_at,
     payload->>'data'->>'user_email' as email,
     status,
     http_status,
     retry_count
   FROM webhook_logs
   ORDER BY created_at DESC
   LIMIT 5;
   ```

---

## 🔍 Step 7: Monitoring & Debugging

### A. Monitor Edge Function Logs

**Supabase Dashboard:**
1. Go to **Edge Functions** → `send-webhook` → **Logs**
2. Real-time logs show:
   - Webhook attempts
   - HTTP status codes
   - Retry attempts
   - Errors (if any)

**Example successful log:**
```
[Attempt 1/3] Sending webhook to https://hooks.n8n.cloud/...
[Attempt 1] HTTP 200 OK
✅ Webhook delivered successfully
```

### B. Monitor Database Trigger Logs

**PostgreSQL Logs (Supabase Dashboard → Logs → Postgres Logs):**

Look for:
```
NOTICE:  Webhook triggered for registration abc-123... (log_id: def-456...)
```

**Or warnings:**
```
WARNING:  Webhook trigger failed for registration abc-123...: [error details]
```

### C. Query Failed Webhooks

```sql
-- Find all failed webhooks
SELECT
  id,
  created_at,
  payload->>'data'->>'user_email' as email,
  error_message,
  retry_count
FROM webhook_logs
WHERE status = 'failed'
ORDER BY created_at DESC;

-- Retry failed webhooks manually (resets status to pending)
UPDATE webhook_logs
SET status = 'pending', retry_count = 0
WHERE status = 'failed'
  AND created_at > NOW() - INTERVAL '1 hour';
-- Note: This will NOT automatically re-trigger Edge Function!
-- Use "Send Test Webhook" button in WordPress Admin instead.
```

### D. WordPress Error Logs

**Check PHP error log:**
```bash
# Find WordPress error log location
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
tail -f wp-content/debug.log        # WordPress debug mode
```

**Enable WordPress debug mode (if needed):**
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

## 🐛 Troubleshooting

### Issue 1: "Test Webhook" Button Returns Error

**Symptoms:**
- ❌ Test failed! HTTP 401 or 403

**Causes & Fixes:**

1. **Missing Supabase credentials**
   - Go to Settings tab, verify Supabase URL and Anon Key filled in
   - Test connection with "Test Connection" button

2. **RLS policies blocking INSERT**
   - Run this query to temporarily disable RLS (testing only):
     ```sql
     ALTER TABLE wp_user_registrations DISABLE ROW LEVEL SECURITY;
     ```
   - If test works, issue is RLS policy → check policy conditions

3. **Invalid Anon Key**
   - Regenerate Anon Key in Supabase Dashboard → Settings → API
   - Update in WordPress Settings tab

### Issue 2: Webhook Status Stuck on "Pending"

**Symptoms:**
- ⏳ Status never changes to Sent or Failed

**Causes & Fixes:**

1. **Edge Function not deployed**
   - Verify: `supabase functions list` shows `send-webhook`
   - Redeploy: `supabase functions deploy send-webhook`

2. **Environment variables missing**
   - Check Supabase Dashboard → Edge Functions → send-webhook → Secrets
   - Verify: SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, WEBHOOK_URL all set

3. **Database trigger not calling Edge Function**
   - Check pg_net extension enabled: `SELECT * FROM pg_available_extensions WHERE name = 'pg_net';`
   - Check database settings: `SELECT name, setting FROM pg_settings WHERE name LIKE 'app.settings.%';`

4. **Edge Function logs show errors**
   - Go to Edge Functions → send-webhook → Logs
   - Look for error messages (e.g., "Missing WEBHOOK_URL", "Fetch failed")

### Issue 3: Webhook Not Received in n8n/make

**Symptoms:**
- ✅ Status shows "Sent" in WordPress
- 🚫 No execution in n8n/make

**Causes & Fixes:**

1. **Wrong webhook URL**
   - Copy webhook URL from n8n/make again
   - Update Edge Function secret: `supabase secrets set WEBHOOK_URL=https://...`

2. **n8n workflow inactive**
   - Check workflow status in n8n (must be Active)
   - Activate workflow and retry test

3. **Webhook URL expired**
   - Some webhook URLs expire after inactivity
   - Create new webhook in n8n/make
   - Update Edge Function secret

4. **Firewall blocking Supabase IP**
   - Check n8n/make firewall settings
   - Whitelist Supabase IP ranges (if using self-hosted n8n)

### Issue 4: High Retry Count (2-3 retries for every webhook)

**Symptoms:**
- All webhooks eventually succeed but retry_count = 2 or 3

**Causes & Fixes:**

1. **n8n/make endpoint slow (>10s timeout)**
   - Increase Edge Function timeout in `send-webhook-function.ts`:
     ```typescript
     const REQUEST_TIMEOUT = 30000 // 30 seconds
     ```
   - Redeploy Edge Function

2. **Network latency**
   - Check Edge Function logs for timing info
   - Consider using n8n/make region closer to Supabase project region

3. **n8n/make rate limiting**
   - Check n8n/make logs for rate limit errors
   - Reduce registration volume or upgrade n8n/make plan

### Issue 5: WordPress Admin Logs Not Loading

**Symptoms:**
- "Loading webhook logs..." never finishes
- Or "Failed to load logs" error

**Causes & Fixes:**

1. **RLS policies blocking SELECT**
   - Check policy: `SELECT policyname FROM pg_policies WHERE tablename = 'webhook_logs';`
   - Verify "WordPress can read webhook logs" policy exists
   - If missing, re-run `webhook-system.sql`

2. **Anon Key incorrect**
   - WordPress uses Anon Key to read logs
   - Verify Anon Key in Settings tab matches Supabase Dashboard → Settings → API

3. **AJAX nonce expired**
   - Refresh WordPress Admin page (clear browser cache)
   - Try again

4. **CORS or firewall blocking request**
   - Check browser console for CORS errors (F12 → Console)
   - Check server firewall allows outbound HTTPS to Supabase

---

## 📊 Performance & Scaling

### Current Capacity

- **Registration volume:** < 1000/day (single Edge Function instance)
- **Webhook delivery:** Immediate (no queue delay)
- **Retry strategy:** 3 attempts over 7 seconds
- **Concurrent requests:** Edge Functions auto-scale

### Optimization Strategies (if needed)

1. **For > 1000 registrations/day:**
   - Add webhook queue table (decouple trigger from delivery)
   - Implement cron-based Edge Function (every minute, batch delivery)
   - Use Supabase Edge Functions v2 (higher concurrency limits)

2. **For high failure rates:**
   - Add dead letter queue for permanently failed webhooks
   - Implement exponential backoff with longer delays (1m, 5m, 15m)
   - Add manual retry button in WordPress Admin

3. **For large payloads:**
   - Compress JSON payload (gzip)
   - Send only essential fields, fetch full data via API in n8n/make
   - Paginate webhook_logs table (currently unlimited)

---

## 🔐 Security Checklist

- [ ] **Service Role Key** stored ONLY in Edge Function secrets (never in WordPress)
- [ ] **Anon Key** used in WordPress (read-only access to webhook_logs)
- [ ] **RLS policies** enabled on webhook_logs table
- [ ] **HTTPS only** for all Supabase and n8n/make communication
- [ ] **Database settings** (app.settings.*) do NOT contain sensitive data in plain text
- [ ] **Webhook URL** (n8n/make) kept private (not committed to git)
- [ ] **WordPress Admin** UI protected by `manage_options` capability
- [ ] **AJAX nonces** verified for all admin actions

---

## 📚 Additional Resources

- **Supabase Edge Functions:** https://supabase.com/docs/guides/functions
- **pg_net Extension:** https://supabase.com/docs/guides/database/extensions/pgnet
- **n8n Webhooks:** https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.webhook/
- **make.com Webhooks:** https://www.make.com/en/help/tools/webhooks

---

## 🎉 Deployment Complete!

You now have a fully functional webhook system that:

✅ Sends webhooks to n8n/make immediately when users register
✅ Retries failed deliveries automatically (3 attempts)
✅ Logs all webhook attempts in Supabase for monitoring
✅ Provides WordPress Admin UI for testing and debugging
✅ Scales automatically with Supabase Edge Functions

**Next Steps:**

1. Monitor webhook logs for first few days
2. Set up n8n/make workflow to process user registrations
3. Add custom fields to payload if needed (edit `webhook-system.sql` trigger function)
4. Implement error alerts (e.g., Slack notification if > 10 failures/hour)

---

*Webhook System v0.8.0 - Built for WordPress-Supabase Bridge*
*Questions? Check ARCHITECTURE.md or open GitHub issue*
