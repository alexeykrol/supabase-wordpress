-- Webhook System for Supabase Bridge v0.8.5
-- Purpose: Send webhooks to n8n/make when users register
-- Updated: 2025-12-13 - Fixed for v0.8.5 (removed thankyou_page_url)

-- ============================================================================
-- STEP 1: Enable pg_net extension (required for async HTTP calls)
-- ============================================================================

CREATE EXTENSION IF NOT EXISTS pg_net;

-- ============================================================================
-- STEP 2: Create webhook_logs table
-- ============================================================================

CREATE TABLE IF NOT EXISTS webhook_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  event_type TEXT NOT NULL DEFAULT 'user_registered',
  registration_id UUID REFERENCES wp_user_registrations(id) ON DELETE CASCADE,
  payload JSONB NOT NULL,
  webhook_url TEXT NOT NULL,
  status TEXT NOT NULL CHECK (status IN ('pending', 'sent', 'failed')),
  http_status INTEGER,
  response_body TEXT,
  error_message TEXT,
  retry_count INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  sent_at TIMESTAMPTZ
);

-- Indexes for fast queries
CREATE INDEX IF NOT EXISTS idx_webhook_logs_created
ON webhook_logs(created_at DESC);

CREATE INDEX IF NOT EXISTS idx_webhook_logs_status
ON webhook_logs(status, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_webhook_logs_registration
ON webhook_logs(registration_id);

COMMENT ON TABLE webhook_logs IS 'Logs all webhook deliveries for n8n/make integration';

-- ============================================================================
-- STEP 3: Create trigger function (FIXED for v0.8.5)
-- ============================================================================

CREATE OR REPLACE FUNCTION trigger_registration_webhook()
RETURNS TRIGGER AS $$
DECLARE
  webhook_payload JSONB;
  edge_function_url TEXT;
  log_id UUID;
  http_request_id BIGINT;
BEGIN
  -- Edge Function URL (configured for mrwzuwdrmdfyleqwmuws project)
  edge_function_url := 'https://mrwzuwdrmdfyleqwmuws.supabase.co/functions/v1/send-webhook';

  -- Build webhook payload (v0.8.5 - thankyou_page_url removed)
  webhook_payload := jsonb_build_object(
    'event', 'user_registered',
    'data', jsonb_build_object(
      'id', NEW.id,
      'user_id', NEW.user_id,
      'user_email', NEW.user_email,
      'registration_url', NEW.registration_url,
      'pair_id', NEW.pair_id,
      'registered_at', NEW.registered_at
    ),
    'timestamp', NOW()
  );

  -- STEP 1: Always create log entry (protected from pg_net errors)
  INSERT INTO webhook_logs (
    event_type,
    registration_id,
    payload,
    webhook_url,
    status
  ) VALUES (
    'user_registered',
    NEW.id,
    webhook_payload,
    edge_function_url,
    'pending'
  ) RETURNING id INTO log_id;

  -- STEP 2: Try to send HTTP request (isolated error handling)
  BEGIN
    http_request_id := net.http_post(
      url := edge_function_url,
      headers := jsonb_build_object(
        'Content-Type', 'application/json'
      ),
      body := webhook_payload::text
    );

    RAISE NOTICE 'Webhook triggered: registration=%, log_id=%, http_request_id=%',
      NEW.id, log_id, http_request_id;

  EXCEPTION
    WHEN OTHERS THEN
      -- If pg_net.http_post fails, log error but don't rollback INSERT
      RAISE WARNING 'pg_net.http_post failed: log_id=%, error=%, SQLSTATE=%',
        log_id, SQLERRM, SQLSTATE;

      UPDATE webhook_logs
      SET
        status = 'failed',
        error_message = 'pg_net.http_post error: ' || SQLERRM || ' (SQLSTATE: ' || SQLSTATE || ')'
      WHERE id = log_id;
  END;

  RETURN NEW;

EXCEPTION
  WHEN OTHERS THEN
    -- Critical error (if even INSERT failed)
    RAISE WARNING 'CRITICAL: trigger_registration_webhook failed: registration=%, error=%, SQLSTATE=%',
      NEW.id, SQLERRM, SQLSTATE;
    RETURN NEW; -- Still return NEW to not block user registration
END;
$$ LANGUAGE plpgsql;

-- ============================================================================
-- STEP 4: Create trigger
-- ============================================================================

DROP TRIGGER IF EXISTS on_registration_send_webhook ON wp_user_registrations;

CREATE TRIGGER on_registration_send_webhook
AFTER INSERT ON wp_user_registrations
FOR EACH ROW
EXECUTE FUNCTION trigger_registration_webhook();

-- ============================================================================
-- STEP 5: Row Level Security (RLS) - CRITICAL!
-- ============================================================================

ALTER TABLE webhook_logs ENABLE ROW LEVEL SECURITY;

-- CRITICAL: Allow anon role to INSERT (trigger runs as anon from WordPress)
DROP POLICY IF EXISTS "Anon can insert webhook logs" ON webhook_logs;
CREATE POLICY "Anon can insert webhook logs"
ON webhook_logs
FOR INSERT
TO anon
WITH CHECK (true);

-- CRITICAL: Allow anon role to UPDATE (for failed status updates)
DROP POLICY IF EXISTS "Anon can update webhook logs" ON webhook_logs;
CREATE POLICY "Anon can update webhook logs"
ON webhook_logs
FOR UPDATE
TO anon
USING (true);

-- Allow WordPress (anon key) to read all logs
DROP POLICY IF EXISTS "WordPress can read webhook logs" ON webhook_logs;
CREATE POLICY "WordPress can read webhook logs"
ON webhook_logs FOR SELECT
TO anon
USING (true);

-- Allow authenticated users to read logs
DROP POLICY IF EXISTS "Authenticated can read webhook logs" ON webhook_logs;
CREATE POLICY "Authenticated can read webhook logs"
ON webhook_logs FOR SELECT
TO authenticated
USING (true);

-- ============================================================================
-- STEP 6: Grant permissions
-- ============================================================================

GRANT ALL ON webhook_logs TO service_role;
GRANT SELECT, INSERT, UPDATE ON webhook_logs TO anon;
GRANT SELECT ON webhook_logs TO authenticated;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check if table exists
SELECT 'webhook_logs table' as check_name,
       CASE WHEN EXISTS (
         SELECT 1 FROM information_schema.tables
         WHERE table_name = 'webhook_logs'
       ) THEN '✅ EXISTS' ELSE '❌ NOT FOUND' END as status;

-- Check if trigger exists
SELECT 'webhook trigger' as check_name,
       CASE WHEN EXISTS (
         SELECT 1 FROM information_schema.triggers
         WHERE trigger_name = 'on_registration_send_webhook'
       ) THEN '✅ EXISTS' ELSE '❌ NOT FOUND' END as status;

-- Check RLS policies
SELECT 'RLS policies' as check_name,
       COUNT(*)::text || ' policies' as status
FROM pg_policies
WHERE tablename = 'webhook_logs';

-- ============================================================================
-- NEXT STEPS
-- ============================================================================

-- 1. ✅ Run this SQL in Supabase SQL Editor
-- 2. ⏳ Deploy Edge Function (send-webhook-function.ts)
--    - Supabase Dashboard → Edge Functions → New Function
--    - Name: send-webhook
--    - Paste code from send-webhook-function.ts
--    - IMPORTANT: Disable "Verify JWT" in Edge Function settings!
-- 3. ⏳ Configure webhook URL in WordPress Admin
--    - WordPress Admin → Supabase Bridge → Webhooks tab
--    - Enable webhooks
--    - Enter your n8n/Make webhook URL
-- 4. ⏳ Test webhook
--    - Click "Send Test Webhook" button in WordPress Admin
--    - Check webhook_logs table for delivery status
