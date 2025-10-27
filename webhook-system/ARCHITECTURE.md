# Webhook System Architecture (v0.8.0)

**Purpose:** Real-time webhook delivery system for n8n/make when new users register
**Created:** 2025-10-26
**Status:** Development

---

## 🎯 Goals

1. **Immediate delivery** - Webhook sent instantly when user registers (no cron delays)
2. **Reliability** - Automatic retries with exponential backoff (3 attempts)
3. **Monitoring** - Full logging of all webhook attempts in Supabase
4. **Testing** - Easy testing via WordPress Admin UI (no need for real registration)
5. **Transparency** - View JSON payload and delivery status for debugging

---

## 🏗️ Architecture Overview

```
User Registration (WordPress)
    ↓
INSERT wp_user_registrations (Phase 6 - existing)
    ↓
Database Trigger: trigger_registration_webhook()
    ↓ (async via pg_net.http_post)
Edge Function: send-webhook
    ↓ (3 retries with exponential backoff)
n8n/make Webhook Endpoint
    ↓
Log result in webhook_logs table
```

### Key Components

1. **Database Trigger** (`trigger_registration_webhook`)
   - Fires AFTER INSERT on `wp_user_registrations`
   - Builds JSON payload from registration data
   - Creates log entry in `webhook_logs` (status: 'pending')
   - Calls Edge Function asynchronously via `pg_net.http_post()`
   - Does NOT block main INSERT transaction

2. **Edge Function** (`send-webhook`)
   - Receives event from database trigger
   - Reads webhook URL from environment variable
   - Sends POST request to n8n/make with JSON payload
   - Implements retry logic (3 attempts, exponential backoff: 1s, 2s, 4s)
   - Updates `webhook_logs` with delivery status

3. **Webhook Logs Table** (`webhook_logs`)
   - Stores all webhook delivery attempts
   - Fields: payload, status, http_status, error_message, retry_count
   - Used by WordPress Admin UI for monitoring

4. **WordPress Admin UI** (new tab in Settings)
   - "Test Webhook" button - creates fake registration to test delivery
   - Real-time webhook logs table (auto-refresh every 10s)
   - View JSON payload for each webhook
   - Status indicators (✅ Sent, ❌ Failed, ⏳ Pending)

---

## 📊 Database Schema

### Table: `webhook_logs`

```sql
CREATE TABLE webhook_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  event_type TEXT NOT NULL DEFAULT 'user_registered',
  registration_id UUID REFERENCES wp_user_registrations(id),
  payload JSONB NOT NULL,
  webhook_url TEXT NOT NULL,
  status TEXT NOT NULL, -- 'pending'|'sent'|'failed'
  http_status INTEGER,
  response_body TEXT,
  error_message TEXT,
  retry_count INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  sent_at TIMESTAMPTZ
);
```

**Indexes:**
- `idx_webhook_logs_created` - Fast queries for recent webhooks
- `idx_webhook_logs_status` - Filter by status (pending/sent/failed)

---

## 🔄 Data Flow

### 1. User Registers (Existing Flow - Phase 6)

```php
// WordPress: supabase-bridge.php (sb_handle_callback)
wp_remote_post($supabase_url . '/rest/v1/wp_user_registrations', [
  'body' => json_encode([
    'user_id' => $supabase_user_id,
    'user_email' => $email,
    'registration_url' => $registration_url,
    'thankyou_page_url' => $thankyou_url,
    'pair_id' => $pair_id
  ])
]);
```

### 2. Database Trigger Fires

```sql
-- Trigger: on_registration_send_webhook
-- Executes: trigger_registration_webhook()

BEGIN
  -- Build payload
  payload := jsonb_build_object(
    'event', 'user_registered',
    'data', jsonb_build_object(
      'id', NEW.id,
      'user_id', NEW.user_id,
      'user_email', NEW.user_email,
      'registration_url', NEW.registration_url,
      'thankyou_page_url', NEW.thankyou_page_url,
      'pair_id', NEW.pair_id,
      'registered_at', NEW.registered_at
    ),
    'timestamp', NOW()
  );

  -- Log attempt
  INSERT INTO webhook_logs (
    registration_id, payload, webhook_url, status
  ) VALUES (
    NEW.id, payload, 'Edge Function URL', 'pending'
  );

  -- Call Edge Function (async, doesn't block)
  PERFORM net.http_post(
    url := 'https://PROJECT.supabase.co/functions/v1/send-webhook',
    headers := jsonb_build_object(
      'Content-Type', 'application/json',
      'Authorization', 'Bearer SERVICE_ROLE_KEY'
    ),
    body := payload
  );

  RETURN NEW;
END;
```

### 3. Edge Function Processes

```typescript
// 1. Parse incoming payload
const { event, data, timestamp } = await req.json()

// 2. Get n8n/make webhook URL
const webhookUrl = Deno.env.get('WEBHOOK_URL')

// 3. Retry loop (3 attempts)
for (let attempt = 0; attempt < 3; attempt++) {
  try {
    const response = await fetch(webhookUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ event, data, timestamp })
    })

    if (response.ok) {
      // Success - update log
      await supabase.from('webhook_logs')
        .update({
          status: 'sent',
          http_status: response.status,
          sent_at: NOW()
        })
      return
    }
  } catch (error) {
    // Retry with exponential backoff
    await sleep(Math.pow(2, attempt) * 1000)
  }
}

// Failed after 3 retries
await supabase.from('webhook_logs')
  .update({ status: 'failed', error_message: 'Max retries exceeded' })
```

### 4. n8n/make Receives

**Webhook Endpoint:** Configured in Supabase environment variable

**Payload Format:**
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

## 🧪 Testing Flow

### WordPress Admin: "Test Webhook" Button

1. User clicks "Send Test Webhook" in WordPress Admin → Settings → Supabase Bridge → Webhooks tab
2. AJAX handler creates fake registration:
   ```php
   wp_remote_post('/rest/v1/wp_user_registrations', [
     'body' => json_encode([
       'user_id' => wp_generate_uuid4(),
       'user_email' => 'test+' . time() . '@example.com',
       'registration_url' => '/test-registration/',
       'thankyou_page_url' => '/test-thankyou/',
       'pair_id' => null
     ])
   ]);
   ```
3. Database trigger fires → Edge Function called → Webhook sent
4. WordPress Admin UI refreshes logs after 2 seconds
5. User sees: ✅ Sent or ❌ Failed with error details

---

## 🔐 Security

### Supabase Side

- **SERVICE_ROLE_KEY** - Stored in Edge Function secrets (never exposed to WordPress)
- **pg_net.http_post** - Server-side HTTP call from database (can't be intercepted)
- **RLS disabled on webhook_logs** - Only Edge Function writes, WordPress reads via Anon Key

### WordPress Side

- **Admin-only access** - `current_user_can('manage_options')` check
- **Nonce verification** - AJAX requests protected with wp_create_nonce()
- **Read-only** - WordPress can only READ webhook_logs, not write

### n8n/make Side

- **No HMAC signature** - Own Supabase, trusted environment
- **Optional:** IP whitelist in n8n/make if needed

---

## 📈 Scalability

### Current Design (< 1000 registrations/day)

- ✅ **Immediate delivery** - No queue, webhook sent instantly
- ✅ **Database trigger** - Lightweight, async via pg_net
- ✅ **Edge Function** - Auto-scales, handles concurrent requests
- ✅ **3 retries** - Sufficient for transient failures

### Future Optimization (> 1000/day)

If webhook delivery becomes bottleneck:
1. Add `webhook_queue` table instead of direct trigger call
2. Implement cron-based Edge Function (every minute) to process queue
3. Batch delivery (send multiple webhooks in one n8n/make call)
4. Add dead letter queue for permanently failed webhooks

**Current decision:** Start simple, optimize if needed

---

## 🐛 Error Handling

### Retry Strategy

| Attempt | Delay | Total Time |
|---------|-------|------------|
| 1       | 0s    | 0s         |
| 2       | 1s    | 1s         |
| 3       | 2s    | 3s         |
| 4       | 4s    | 7s         |

**Max total delay:** ~7 seconds before marking as failed

### Failure Scenarios

| Scenario | Handling |
|----------|----------|
| n8n/make endpoint down | 3 retries with backoff, then mark as failed |
| Edge Function timeout (10s) | Abort request, count as retry, backoff |
| Database trigger fails | PostgreSQL logs error, webhook_logs not created |
| Invalid JSON payload | Edge Function logs error, mark as failed |
| Network timeout | Retry with exponential backoff |

### Monitoring

- **Edge Function Logs** - Real-time in Supabase Dashboard
- **WordPress Admin Logs** - Last 20 webhooks with status
- **Database Queries** - SQL to find failed webhooks:
  ```sql
  SELECT * FROM webhook_logs
  WHERE status = 'failed'
  ORDER BY created_at DESC
  LIMIT 20;
  ```

---

## 🔬 Critical Technical Details (v0.8.1)

> **⚠️ IMPORTANT:** These technical nuances cost 12 hours of debugging. Read carefully to avoid repeating mistakes.

### 1. Supabase Edge Functions and JWT Authentication

**Problem:** Supabase Edge Functions require JWT authentication by default, unlike n8n/make webhooks.

**Technical Details:**
- Edge Functions block unauthenticated requests with HTTP 401
- `pg_net.http_post()` does NOT send Authorization header by default
- n8n/make webhooks work without auth → false expectation for Supabase

**Symptom:**
```sql
-- Edge Function invocations show:
{
  "headers": [
    {"accept": "*/*"},
    {"content_length": "370"}
    // NO Authorization header!
  ],
  "status_code": 401
}
```

**Solution A: Disable JWT Verification (Recommended for Internal Use)**
1. Supabase Dashboard → Edge Functions → send-webhook → Details tab
2. Toggle OFF: "Verify JWT" or "Require JWT verification"
3. Save changes

**Solution B: Add Authorization Header (More Secure)**
```sql
-- Add Authorization to pg_net call
http_request_id := net.http_post(
  url := edge_function_url,
  headers := jsonb_build_object(
    'Content-Type', 'application/json',
    'Authorization', 'Bearer ' || current_setting('app.settings.service_role_key', true)
  ),
  body := webhook_payload
);
```

**Trade-offs:**
- **Solution A:** Simpler, works immediately, less secure (anyone with URL can call)
- **Solution B:** More secure, requires storing SERVICE_ROLE_KEY in database settings

**Deployment Note:** Documentation uses Solution A for simplicity. Production systems should consider Solution B.

---

### 2. Row-Level Security (RLS) and Database Triggers

**Problem:** Database triggers inherit the caller's role, not the function owner's role.

**Technical Details:**
- WordPress calls Supabase REST API with **anon** key (not service_role)
- INSERT into `wp_user_registrations` runs with **anon** role
- Trigger `trigger_registration_webhook()` inherits **anon** role
- Trigger tries to INSERT into `webhook_logs` → RLS checks **anon** permissions

**Symptom:**
```sql
-- webhook_logs shows no new entries
-- pg_net.http_post() executes successfully
-- No errors in logs
-- Status stays 'pending' forever
```

**Why This Happens:**
```sql
-- DEFAULT RLS POLICY (blocks trigger):
CREATE POLICY "Service role can insert logs"
ON webhook_logs FOR INSERT
WITH CHECK (auth.role() = 'service_role'); -- ❌ Trigger runs as 'anon'!
```

**Solution: Add RLS Policy for Anon**
```sql
-- Allow anon (WordPress) to INSERT webhook logs
DROP POLICY IF EXISTS "Anon can insert webhook logs" ON webhook_logs;

CREATE POLICY "Anon can insert webhook logs"
ON webhook_logs
FOR INSERT
TO anon
WITH CHECK (true); -- Allow all inserts from anon role

-- Also allow anon to UPDATE (for Edge Function response)
DROP POLICY IF EXISTS "Anon can update webhook logs" ON webhook_logs;

CREATE POLICY "Anon can update webhook logs"
ON webhook_logs
FOR UPDATE
TO anon
USING (true);
```

**Why Not SECURITY DEFINER?**
- SECURITY DEFINER makes trigger run as function owner (postgres superuser)
- More complex, requires careful permission management
- RLS policy is simpler and more explicit

**Key Insight:** Always test RLS policies with the actual role that will call the function (anon, not service_role).

---

### 3. pg_net Extension: Installation and Correct Syntax

**Problem:** pg_net extension must be explicitly enabled, and syntax is version-specific.

**Installation:**
```sql
-- Enable pg_net extension
CREATE EXTENSION IF NOT EXISTS pg_net;

-- Verify installation
SELECT extname, extversion FROM pg_extension WHERE extname = 'pg_net';
-- Expected: pg_net | 0.19.5 (or newer)
```

**Dashboard Method:**
1. Supabase Dashboard → Database → Extensions
2. Search: "pg_net"
3. Toggle: ON
4. Verify: extension appears in enabled list

**Correct Syntax (v0.19.5):**
```sql
-- ✅ CORRECT (named parameters, no headers)
http_request_id := net.http_post(
  url := 'https://example.com',
  body := '{"key":"value"}'
);

-- ❌ WRONG (positional parameters)
http_request_id := net.http_post(
  'https://example.com',
  '{"key":"value"}'
);

-- ❌ WRONG (headers parameter doesn't exist)
http_request_id := net.http_post(
  url := 'https://example.com',
  headers := jsonb_build_object('Content-Type', 'application/json'),
  body := '{"key":"value"}'
);
```

**Debugging pg_net:**
```sql
-- Check queued requests
SELECT * FROM net.http_request_queue
ORDER BY created_at DESC
LIMIT 10;

-- Columns:
-- id: request ID
-- url: target URL
-- method: POST
-- body: payload
-- status: 'pending' | 'success' | 'error'
-- response_status_code: HTTP code (200, 401, etc.)
-- response_body: response from endpoint
```

**Key Insight:** pg_net executes asynchronously. The function returns request ID immediately, actual HTTP call happens later in background.

---

### 4. Edge Function Error Handling Patterns

**Problem:** HTTP 4xx/5xx responses are valid responses, not exceptions. Retry loop must explicitly check `response.ok`.

**Flawed Pattern (v0.8.0):**
```typescript
// ❌ BUG: Only updates on success
for (let attempt = 0; attempt < 3; attempt++) {
  try {
    const response = await fetch(webhookUrl, {...});

    if (response.ok) {
      await supabase.from("webhook_logs").update({
        status: "sent", // ✅ Updates on success
        ...
      })...
      return;
    }

    // ❌ BUG: HTTP 401/404 don't throw errors!
    // Response is valid, so catch block never runs

  } catch (error) {
    // Only network errors reach here
  }
}

// ❌ BUG: No update after loop exits
// webhook_logs stays 'pending' forever
```

**Fixed Pattern (v0.8.1):**
```typescript
// ✅ FIX: Track last error/response outside loop
let lastResponse = null;
let lastError = null;

for (let attempt = 0; attempt < MAX_RETRIES; attempt++) {
  try {
    const response = await fetch(webhookUrl, {...});
    lastResponse = response; // ✅ Save response

    if (response.ok) {
      // Success path (unchanged)
      await supabase.from("webhook_logs").update({
        status: "sent",
        ...
      })...
      return new Response(JSON.stringify({ success: true }), { status: 200 });
    }

    // ✅ FIX: HTTP error is NOT an exception
    lastError = `HTTP ${response.status}: ${await response.text()}`;
    await delay(Math.pow(2, attempt) * 1000); // Backoff

  } catch (error) {
    lastError = error.message;
    await delay(Math.pow(2, attempt) * 1000); // Backoff
  }
}

// ✅ FIX: Update to 'failed' after all retries exhausted
await supabase.from("webhook_logs").update({
  status: "failed",
  http_status: lastResponse?.status || null,
  response_body: lastResponse ? await lastResponse.text() : null,
  error_message: lastError || "All retries failed",
  retry_count: MAX_RETRIES
}).eq("registration_id", registrationId)...

return new Response(JSON.stringify({ error: lastError }), { status: 500 });
```

**Key Insight:** Always update webhook_logs at END of function, regardless of success/failure. Don't rely solely on `catch` blocks.

---

### 5. WordPress Encrypted Settings

**Problem:** Supabase URL stored encrypted in WordPress options. SQL generation must decrypt first.

**Technical Details:**
- WordPress plugin encrypts sensitive settings (Supabase URL, Service Role Key)
- `get_option('sb_supabase_url')` returns base64-encoded encrypted string
- Must call `sb_decrypt()` to get actual URL

**Symptom:**
```php
// ❌ WRONG: Encrypted value
$supabase_url = get_option('sb_supabase_url', '');
// Result: "U2FsdGVkX1/abc123..." (encrypted base64)

// Extract project ref
preg_match('/https?:\/\/([^.]+)\.supabase\.co/', $supabase_url, $matches);
// Result: NO MATCH (encrypted string doesn't contain URL pattern)

// Generated SQL
edge_function_url := 'https://YOUR_PROJECT_REF.supabase.co/functions/v1/send-webhook';
// ❌ Placeholder not replaced!
```

**Solution:**
```php
// ✅ CORRECT: Decrypt first
$supabase_url_encrypted = get_option('sb_supabase_url', '');
$supabase_url = !empty($supabase_url_encrypted)
  ? sb_decrypt($supabase_url_encrypted)  // ✅ Decrypt
  : '';

$project_ref = 'YOUR_PROJECT_REF'; // Default fallback

if (!empty($supabase_url)) {
  // Extract from decrypted URL
  if (preg_match('/https?:\/\/([^.]+)\.supabase\.co/', $supabase_url, $matches)) {
    $project_ref = $matches[1]; // ✅ Extracted: 'fomzkfdcueugsykhhzqe'
  }
}

// Generated SQL with actual URL
$sql_edge_function_url = 'https://' . $project_ref . '.supabase.co/functions/v1/send-webhook';
// ✅ Result: 'https://fomzkfdcueugsykhhzqe.supabase.co/functions/v1/send-webhook'
```

**Key Insight:** Always decrypt settings before using them in string operations (regex, concatenation, etc.).

---

### 6. Debugging Workflow

**Step-by-Step Debugging Process:**

1. **Check Edge Function Invocations**
   - Dashboard → Edge Functions → send-webhook → Invocations tab
   - Look for: HTTP status code (200/401/500), headers, response body
   - Common issue: HTTP 401 = JWT verification enabled

2. **Check pg_net Queue**
   ```sql
   SELECT * FROM net.http_request_queue
   ORDER BY created_at DESC
   LIMIT 10;
   ```
   - Look for: status='error', response_status_code=401
   - Common issue: URL wrong, extension not installed

3. **Check webhook_logs Table**
   ```sql
   SELECT
     created_at,
     status,
     http_status,
     error_message,
     payload->>'data'->>'user_email' as email
   FROM webhook_logs
   ORDER BY created_at DESC
   LIMIT 10;
   ```
   - Look for: status='pending' (RLS issue), status='failed' (Edge Function issue)

4. **Check Database Logs**
   - Dashboard → Database → Logs
   - Search: "trigger_registration_webhook"
   - Look for: RAISE NOTICE, RAISE WARNING messages

5. **Test Edge Function Directly**
   ```bash
   curl -X POST https://your-project.supabase.co/functions/v1/send-webhook \
     -H "Content-Type: application/json" \
     -d '{"event":"user_registered","data":{...}}'
   ```
   - Isolates Edge Function from trigger
   - Common issue: Secrets not configured

**Key Insight:** Always start debugging from the TOP of the stack (Edge Function) down to the database (trigger, RLS).

---

### 7. Version Tracking in Generated Code

**Problem:** Auto-generated SQL code has no version history. Hard to know what changed.

**Solution: Add Version Header**
```sql
-- Webhook System for Supabase Bridge v0.8.3
-- FIX: Corrected pg_net.http_post signature (url, body)
-- Previous: v0.8.2 - Fixed RLS policies for anon role
-- Previous: v0.8.1 - Fixed Edge Function error handling
-- Previous: v0.8.0 - Initial release

CREATE OR REPLACE FUNCTION trigger_registration_webhook()
...
```

**Implementation in WordPress:**
```php
// supabase-bridge.php (line ~1700)
$sql_output = "-- Webhook System for Supabase Bridge v0.8.3\n";
$sql_output .= "-- FIX: Corrected pg_net.http_post signature (url, body)\n\n";
```

**Key Insight:** Version numbers prevent "did I deploy the latest code?" confusion. Always update version when changing generated code.

---

### 8. Common Pitfalls Summary

| Pitfall | Symptom | Root Cause | Fix |
|---------|---------|------------|-----|
| **HTTP 401 from Edge Function** | webhook_logs shows 'pending', pg_net queue shows 401 | JWT verification enabled | Disable JWT in Edge Function settings |
| **webhook_logs empty** | Trigger fires, no logs created | RLS blocks anon INSERT | Add RLS policy for anon role |
| **Status stays 'pending'** | Logs created, never updated | Edge Function doesn't update 'failed' after retries | Add final update after retry loop |
| **"function does not exist"** | pg_net call fails | Extension not installed or wrong syntax | Enable pg_net, use correct syntax |
| **URL not replaced in SQL** | SQL shows 'YOUR_PROJECT_REF' | Encrypted settings not decrypted | Call sb_decrypt() before regex |

---

**Documentation Updated:** 2025-10-27 (after 12-hour debugging session)
**Version:** 0.8.1 (added critical technical details)

---

## 📝 Environment Variables

**Supabase Edge Function Secrets:**

```bash
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_SERVICE_ROLE_KEY=eyJhbGci... (from Supabase Dashboard)
WEBHOOK_URL=https://hooks.n8n.cloud/webhook/... (or make.com webhook)
```

**Configuration in Supabase Dashboard:**
Settings → Edge Functions → send-webhook → Secrets

---

## 🚀 Deployment Checklist

- [ ] Run `webhook-system.sql` in Supabase SQL Editor
- [ ] Deploy Edge Function via Supabase CLI
- [ ] Configure environment variables in Supabase Dashboard
- [ ] Update `supabase-bridge.php` with Webhooks tab UI
- [ ] Test with "Send Test Webhook" button
- [ ] Verify webhook received in n8n/make
- [ ] Monitor Edge Function logs for errors
- [ ] Check webhook_logs table for delivery status

---

## 📚 Files Structure

```
webhook-system/
├── ARCHITECTURE.md           # This file
├── webhook-system.sql        # Database schema + triggers
├── send-webhook-function.ts  # Edge Function code
├── DEPLOYMENT.md             # Step-by-step deployment guide
└── wordpress-admin-ui.php    # WordPress Admin UI code (to be added to supabase-bridge.php)
```

---

## 🔗 Integration Points

### Existing System (v0.7.0)

- **Uses existing table:** `wp_user_registrations` (created in Phase 6)
- **Triggered by:** Existing WordPress registration flow
- **No changes needed:** WordPress plugin continues to work as-is

### New Components (v0.8.0)

- **New table:** `webhook_logs`
- **New trigger:** `on_registration_send_webhook`
- **New Edge Function:** `send-webhook`
- **New WordPress UI:** Webhooks tab in Settings page

---

*Architecture designed for simplicity, reliability, and easy debugging*
*Version: 0.8.1 (Webhook System)*
*Last Updated: 2025-10-27 (added critical technical details after 12-hour debugging session)*
*Status: Production Ready - End-to-end webhook delivery working*
