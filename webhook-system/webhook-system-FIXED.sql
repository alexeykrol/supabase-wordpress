-- ИСПРАВЛЕННАЯ ВЕРСИЯ: Webhook System for Supabase Bridge v0.8.0
-- FIX: Hardcoded Edge Function URL (вместо current_setting которые не работают)
-- Только эту функцию нужно перезаписать!

-- ============================================================================
-- FUNCTION: trigger_registration_webhook (FIXED VERSION)
-- Purpose: Called by database trigger to send webhook via Edge Function
-- FIX: Hardcoded edge_function_url для вашего проекта
-- ============================================================================

CREATE OR REPLACE FUNCTION trigger_registration_webhook()
RETURNS TRIGGER AS $$
DECLARE
  webhook_payload JSONB;
  edge_function_url TEXT;
  log_id UUID;
  http_request_id BIGINT;
BEGIN
  -- HARDCODED URL для вашего проекта fomzkfdcueugsykhhzqe
  -- Если проект изменится - замени здесь!
  edge_function_url := 'https://fomzkfdcueugsykhhzqe.supabase.co/functions/v1/send-webhook';

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

  -- ШАГ 1: ВСЕГДА создать запись в webhook_logs
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

  -- ШАГ 2: Попытаться отправить через pg_net (в отдельном блоке для изоляции ошибок)
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
      -- Если pg_net.http_post упал - логируем, но НЕ откатываем INSERT
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
    -- Критическая ошибка (если даже INSERT упал)
    RAISE WARNING 'CRITICAL: trigger_registration_webhook failed: registration=%, error=%, SQLSTATE=%',
      NEW.id, SQLERRM, SQLSTATE;
    RETURN NEW; -- Всё равно возвращаем NEW чтобы не блокировать регистрацию
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION trigger_registration_webhook IS 'Triggers webhook delivery via Edge Function when new user registers (FIXED: hardcoded URL)';
