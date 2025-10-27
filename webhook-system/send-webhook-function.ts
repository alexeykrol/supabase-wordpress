// Supabase Edge Function: send-webhook
// Purpose: Deliver webhooks to n8n/make with retry logic
// Version: 0.8.0
// Created: 2025-10-26

import { serve } from "https://deno.land/std@0.168.0/http/server.ts"
import { createClient } from "https://esm.sh/@supabase/supabase-js@2.39.0"

// ============================================================================
// TYPES
// ============================================================================

interface WebhookPayload {
  event: string
  data: {
    id: string
    user_id: string
    user_email: string
    registration_url: string
    thankyou_page_url: string
    pair_id: string | null
    registered_at: string
  }
  timestamp: string
}

interface WebhookLog {
  id?: string
  registration_id: string
  status: "pending" | "sent" | "failed"
  http_status?: number
  response_body?: string
  error_message?: string
  retry_count: number
  sent_at?: string
}

// ============================================================================
// CONFIGURATION
// ============================================================================

const SUPABASE_URL = Deno.env.get("SUPABASE_URL") || ""
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") || ""
const WEBHOOK_URL = Deno.env.get("WEBHOOK_URL") || ""

const MAX_RETRIES = 3
const RETRY_DELAYS = [1000, 2000, 4000] // Exponential backoff: 1s, 2s, 4s
const REQUEST_TIMEOUT = 10000 // 10 seconds timeout per request

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Sleep utility for retry delays
 */
function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

/**
 * Initialize Supabase client with service role
 */
function getSupabaseClient() {
  if (!SUPABASE_URL || !SUPABASE_SERVICE_ROLE_KEY) {
    throw new Error("Missing SUPABASE_URL or SUPABASE_SERVICE_ROLE_KEY environment variables")
  }
  return createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY)
}

/**
 * Update webhook log in database
 */
async function updateWebhookLog(
  supabase: ReturnType<typeof createClient>,
  registrationId: string,
  update: Partial<WebhookLog>
): Promise<void> {
  const { error } = await supabase
    .from("webhook_logs")
    .update(update)
    .eq("registration_id", registrationId)
    .order("created_at", { ascending: false })
    .limit(1)

  if (error) {
    console.error("Failed to update webhook_logs:", error)
    throw error
  }
}

/**
 * Send webhook to n8n/make with timeout
 */
async function sendWebhookRequest(
  url: string,
  payload: WebhookPayload,
  attempt: number
): Promise<Response> {
  console.log(`[Attempt ${attempt + 1}/${MAX_RETRIES}] Sending webhook to ${url}`)

  const controller = new AbortController()
  const timeoutId = setTimeout(() => controller.abort(), REQUEST_TIMEOUT)

  try {
    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "User-Agent": "WordPress-Supabase-Bridge/0.8.0",
      },
      body: JSON.stringify(payload),
      signal: controller.signal,
    })

    clearTimeout(timeoutId)
    return response
  } catch (error) {
    clearTimeout(timeoutId)
    throw error
  }
}

/**
 * Deliver webhook with retry logic
 */
async function deliverWebhook(
  payload: WebhookPayload,
  supabase: ReturnType<typeof createClient>
): Promise<void> {
  const registrationId = payload.data.id

  if (!WEBHOOK_URL) {
    const errorMsg = "WEBHOOK_URL environment variable not set"
    console.error(errorMsg)
    await updateWebhookLog(supabase, registrationId, {
      status: "failed",
      error_message: errorMsg,
      retry_count: 0,
    })
    throw new Error(errorMsg)
  }

  let lastError: Error | null = null
  let lastResponse: Response | null = null

  // Retry loop
  for (let attempt = 0; attempt < MAX_RETRIES; attempt++) {
    try {
      // Send webhook
      const response = await sendWebhookRequest(WEBHOOK_URL, payload, attempt)
      lastResponse = response

      // Read response body (limit to first 1000 chars for logging)
      let responseBody = ""
      try {
        const text = await response.text()
        responseBody = text.substring(0, 1000)
      } catch (e) {
        responseBody = "Failed to read response body"
      }

      console.log(`[Attempt ${attempt + 1}] HTTP ${response.status} ${response.statusText}`)

      // Success case (2xx status codes)
      if (response.ok) {
        console.log(`✅ Webhook delivered successfully to ${WEBHOOK_URL}`)
        await updateWebhookLog(supabase, registrationId, {
          status: "sent",
          http_status: response.status,
          response_body: responseBody,
          retry_count: attempt,
          sent_at: new Date().toISOString(),
        })
        return // Success - exit function
      }

      // Non-2xx status code
      console.warn(
        `⚠️ Webhook returned non-2xx status: ${response.status} ${response.statusText}`
      )
      lastError = new Error(
        `HTTP ${response.status}: ${response.statusText} - ${responseBody}`
      )
    } catch (error) {
      // Network error, timeout, or other exception
      console.error(`❌ Attempt ${attempt + 1} failed:`, error)
      lastError = error as Error
    }

    // If not last attempt, wait before retry
    if (attempt < MAX_RETRIES - 1) {
      const delay = RETRY_DELAYS[attempt]
      console.log(`⏳ Retrying in ${delay}ms...`)
      await sleep(delay)
    }
  }

  // All retries failed - mark as failed
  console.error(`❌ All ${MAX_RETRIES} attempts failed for registration ${registrationId}`)

  const errorMessage = lastError
    ? `${lastError.name}: ${lastError.message}`
    : "Unknown error after all retries"

  await updateWebhookLog(supabase, registrationId, {
    status: "failed",
    http_status: lastResponse?.status,
    error_message: errorMessage.substring(0, 500), // Limit error message length
    retry_count: MAX_RETRIES,
  })

  throw new Error(errorMessage)
}

// ============================================================================
// MAIN HANDLER
// ============================================================================

serve(async (req: Request) => {
  // CORS headers for local testing (optional)
  const corsHeaders = {
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "POST, OPTIONS",
    "Access-Control-Allow-Headers": "authorization, content-type",
  }

  // Handle OPTIONS preflight request
  if (req.method === "OPTIONS") {
    return new Response(null, { status: 204, headers: corsHeaders })
  }

  // Only allow POST
  if (req.method !== "POST") {
    return new Response(
      JSON.stringify({ error: "Method not allowed, use POST" }),
      { status: 405, headers: { ...corsHeaders, "Content-Type": "application/json" } }
    )
  }

  try {
    console.log("=== Webhook Edge Function Started ===")

    // Parse incoming payload from database trigger
    let payload: WebhookPayload
    try {
      payload = await req.json()
    } catch (error) {
      console.error("Failed to parse request JSON:", error)
      return new Response(
        JSON.stringify({ error: "Invalid JSON payload" }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      )
    }

    // Validate payload structure
    if (!payload.event || !payload.data || !payload.data.id) {
      console.error("Invalid payload structure:", payload)
      return new Response(
        JSON.stringify({ error: "Invalid payload: missing required fields" }),
        { status: 400, headers: { ...corsHeaders, "Content-Type": "application/json" } }
      )
    }

    console.log(`Processing webhook for registration ${payload.data.id}`)
    console.log(`Event: ${payload.event}`)
    console.log(`User: ${payload.data.user_email}`)

    // Initialize Supabase client
    const supabase = getSupabaseClient()

    // Deliver webhook with retry logic
    await deliverWebhook(payload, supabase)

    console.log("=== Webhook Edge Function Completed Successfully ===")

    return new Response(
      JSON.stringify({
        success: true,
        message: "Webhook delivered successfully",
        registration_id: payload.data.id,
      }),
      { status: 200, headers: { ...corsHeaders, "Content-Type": "application/json" } }
    )
  } catch (error) {
    console.error("=== Webhook Edge Function Failed ===")
    console.error(error)

    return new Response(
      JSON.stringify({
        success: false,
        error: error instanceof Error ? error.message : "Unknown error",
      }),
      { status: 500, headers: { ...corsHeaders, "Content-Type": "application/json" } }
    )
  }
})

// ============================================================================
// DEPLOYMENT NOTES
// ============================================================================

/*
DEPLOYMENT CHECKLIST:

1. Deploy via Supabase CLI:
   supabase functions deploy send-webhook

2. Set environment variables in Supabase Dashboard:
   - SUPABASE_URL: https://your-project.supabase.co
   - SUPABASE_SERVICE_ROLE_KEY: eyJhbGci... (from Dashboard > Settings > API)
   - WEBHOOK_URL: https://hooks.n8n.cloud/webhook/... (or make.com webhook)

3. Test locally:
   supabase functions serve send-webhook --env-file .env.local

4. Verify in Supabase Dashboard:
   - Edge Functions > send-webhook > Logs
   - Check for successful invocations

5. Test end-to-end:
   - WordPress Admin > Settings > Supabase Bridge > Webhooks
   - Click "Send Test Webhook"
   - Verify webhook received in n8n/make
   - Check webhook_logs table for delivery status

ERROR HANDLING:
- Network timeouts: 10s timeout per request, counted as retry
- Non-2xx responses: Retry with exponential backoff
- Max retries exceeded: Mark as failed in webhook_logs
- Database errors: Logged to Edge Function logs

MONITORING:
- Real-time logs: Supabase Dashboard > Edge Functions > send-webhook > Logs
- Webhook history: WordPress Admin > Settings > Supabase Bridge > Webhooks
- Failed webhooks: SELECT * FROM webhook_logs WHERE status = 'failed'

SECURITY:
- SERVICE_ROLE_KEY: Never exposed to WordPress, only in Edge Function secrets
- WEBHOOK_URL: Stored in Edge Function secrets, not in database
- RLS: Edge Function uses service role to write to webhook_logs
- No HMAC: Own Supabase, trusted environment
*/
