-- Webhook System for Supabase Bridge v0.8.0
-- Purpose: Send webhooks to n8n/make when users register
-- Created: 2025-10-26

-- ============================================================================
-- TABLE: webhook_logs
-- Purpose: Log all webhook delivery attempts for monitoring and debugging
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

-- Comments for documentation
COMMENT ON TABLE webhook_logs IS 'Logs all webhook deliveries for n8n/make integration';
COMMENT ON COLUMN webhook_logs.event_type IS 'Event type (currently only user_registered)';
COMMENT ON COLUMN webhook_logs.registration_id IS 'FK to wp_user_registrations.id';
COMMENT ON COLUMN webhook_logs.payload IS 'Full JSON payload sent to webhook';
COMMENT ON COLUMN webhook_logs.webhook_url IS 'Target webhook URL (n8n/make endpoint)';
COMMENT ON COLUMN webhook_logs.status IS 'Delivery status: pending|sent|failed';
COMMENT ON COLUMN webhook_logs.http_status IS 'HTTP response code from webhook endpoint';
COMMENT ON COLUMN webhook_logs.response_body IS 'Response body from webhook endpoint (first 1000 chars)';
COMMENT ON COLUMN webhook_logs.error_message IS 'Error message if delivery failed';
COMMENT ON COLUMN webhook_logs.retry_count IS 'Number of delivery attempts (0-3)';

-- ============================================================================
-- FUNCTION: trigger_registration_webhook
-- Purpose: Called by database trigger to send webhook via Edge Function
-- ============================================================================

CREATE OR REPLACE FUNCTION trigger_registration_webhook()
RETURNS TRIGGER AS $$
DECLARE
  webhook_payload JSONB;
  edge_function_url TEXT;
  log_id UUID;
  http_request_id BIGINT;
BEGIN
  -- Edge Function URL: Replace YOUR_PROJECT_REF with your actual project reference ID
  -- Find it in: Supabase Dashboard → Project Settings → General → Reference ID
  -- Example: If your URL is https://abc123xyz.supabase.co, then abc123xyz is your project ref
  edge_function_url := 'https://YOUR_PROJECT_REF.supabase.co/functions/v1/send-webhook';

  -- Build webhook payload
  webhook_payload := jsonb_build_object(
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

COMMENT ON FUNCTION trigger_registration_webhook IS 'Triggers webhook delivery via Edge Function when new user registers';

-- ============================================================================
-- TRIGGER: on_registration_send_webhook
-- Purpose: Fire webhook on every new registration
-- ============================================================================

DROP TRIGGER IF EXISTS on_registration_send_webhook ON wp_user_registrations;

CREATE TRIGGER on_registration_send_webhook
AFTER INSERT ON wp_user_registrations
FOR EACH ROW
EXECUTE FUNCTION trigger_registration_webhook();

COMMENT ON TRIGGER on_registration_send_webhook ON wp_user_registrations IS 'Sends webhook to n8n/make when user registers';

-- ============================================================================
-- ROW LEVEL SECURITY (RLS)
-- Purpose: Allow WordPress to read logs, only Edge Function can write
-- ============================================================================

ALTER TABLE webhook_logs ENABLE ROW LEVEL SECURITY;

-- Policy: Allow WordPress (anon key) to read all logs
DROP POLICY IF EXISTS "WordPress can read webhook logs" ON webhook_logs;
CREATE POLICY "WordPress can read webhook logs"
ON webhook_logs FOR SELECT
USING (true); -- All users can read (anon key from WordPress)

-- Policy: Only service role can insert/update logs
DROP POLICY IF EXISTS "Service role can insert logs" ON webhook_logs;
CREATE POLICY "Service role can insert logs"
ON webhook_logs FOR INSERT
WITH CHECK (auth.role() = 'service_role');

DROP POLICY IF EXISTS "Service role can update logs" ON webhook_logs;
CREATE POLICY "Service role can update logs"
ON webhook_logs FOR UPDATE
USING (auth.role() = 'service_role');

-- ============================================================================
-- CONFIGURATION SETTINGS
-- Purpose: Store Edge Function URL and Service Role Key for trigger
-- ============================================================================

-- These settings are used by trigger_registration_webhook()
-- Set via SQL or Supabase Dashboard

-- Example (replace with your actual values):
-- ALTER DATABASE postgres SET app.settings.supabase_url = 'https://your-project.supabase.co';
-- ALTER DATABASE postgres SET app.settings.service_role_key = 'eyJhbGci...';
-- ALTER DATABASE postgres SET app.settings.edge_function_url = 'https://your-project.supabase.co/functions/v1/send-webhook';

-- ============================================================================
-- HELPER QUERIES FOR MONITORING
-- ============================================================================

-- View recent webhooks
-- SELECT
--   created_at,
--   payload->>'data'->>'user_email' as email,
--   status,
--   http_status,
--   retry_count,
--   error_message
-- FROM webhook_logs
-- ORDER BY created_at DESC
-- LIMIT 20;

-- Count webhooks by status
-- SELECT
--   status,
--   COUNT(*) as count,
--   MAX(created_at) as last_delivery
-- FROM webhook_logs
-- GROUP BY status;

-- Find failed webhooks
-- SELECT
--   id,
--   created_at,
--   payload->>'data'->>'user_email' as email,
--   error_message,
--   retry_count
-- FROM webhook_logs
-- WHERE status = 'failed'
-- ORDER BY created_at DESC;

-- Retry failed webhooks manually (if needed)
-- UPDATE webhook_logs
-- SET status = 'pending', retry_count = 0
-- WHERE status = 'failed' AND created_at > NOW() - INTERVAL '1 hour';

-- ============================================================================
-- GRANT PERMISSIONS
-- ============================================================================

-- Grant necessary permissions to service role
GRANT ALL ON webhook_logs TO service_role;
GRANT EXECUTE ON FUNCTION trigger_registration_webhook TO service_role;

-- Grant read-only to anon (WordPress)
GRANT SELECT ON webhook_logs TO anon;
GRANT SELECT ON webhook_logs TO authenticated;

-- ============================================================================
-- DEPLOYMENT VERIFICATION
-- ============================================================================

-- Verify table exists
-- SELECT table_name, table_type
-- FROM information_schema.tables
-- WHERE table_name = 'webhook_logs';

-- Verify trigger exists
-- SELECT trigger_name, event_manipulation, event_object_table
-- FROM information_schema.triggers
-- WHERE trigger_name = 'on_registration_send_webhook';

-- Verify RLS policies
-- SELECT schemaname, tablename, policyname, permissive, roles, cmd, qual
-- FROM pg_policies
-- WHERE tablename = 'webhook_logs';

-- ============================================================================
-- NOTES
-- ============================================================================

-- 1. pg_net extension required for async HTTP calls
--    Enable: CREATE EXTENSION IF NOT EXISTS pg_net;
--
-- 2. Configure settings via SQL:
--    ALTER DATABASE postgres SET app.settings.supabase_url = 'https://xxx.supabase.co';
--    ALTER DATABASE postgres SET app.settings.service_role_key = 'eyJhbGci...';
--
-- 3. Edge Function must be deployed separately (see send-webhook-function.ts)
--
-- 4. Test with WordPress Admin "Send Test Webhook" button

-- ============================================================================
-- END OF FILE
-- ============================================================================
