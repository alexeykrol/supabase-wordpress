<?php
/**
 * Webhooks Tab - Full Implementation
 *
 * USAGE:
 * 1. Сначала добавьте простой placeholder (см. ADD-WEBHOOKS-TAB.md)
 * 2. Протестируйте что вкладка появляется
 * 3. Замените функцию sb_render_webhooks_tab() этим кодом
 */

// === Phase 3: Webhooks Tab (v0.8.0) - FULL IMPLEMENTATION ===
function sb_render_webhooks_tab() {
  // Get current settings
  $webhook_enabled = get_option('sb_webhook_enabled', '0');
  $webhook_url = get_option('sb_webhook_url', '');

  // Save settings if form submitted
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sb_webhook_settings_nonce'])) {
    if (wp_verify_nonce($_POST['sb_webhook_settings_nonce'], 'sb_webhook_settings')) {
      update_option('sb_webhook_enabled', isset($_POST['sb_webhook_enabled']) ? '1' : '0');
      update_option('sb_webhook_url', sanitize_text_field($_POST['sb_webhook_url']));

      echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Settings saved!</strong></p></div>';

      // Reload values
      $webhook_enabled = get_option('sb_webhook_enabled', '0');
      $webhook_url = get_option('sb_webhook_url', '');
    }
  }

  ?>
  <div style="margin-top: 20px;">
    <h2>🪝 Webhook System for n8n/make</h2>
    <p>Send webhooks to n8n/make automatically when users register.</p>

    <!-- Status Summary -->
    <div style="padding: 15px; background: #f9f9f9; border-left: 4px solid <?php echo $webhook_enabled === '1' && !empty($webhook_url) ? '#46b450' : '#dc3232'; ?>; margin: 20px 0;">
      <h3 style="margin-top: 0;">📊 Current Status</h3>
      <ul style="list-style: none; padding: 0; margin: 10px 0;">
        <li style="padding: 5px 0;">
          <strong>Webhooks:</strong>
          <?php if ($webhook_enabled === '1'): ?>
            <span style="color: #46b450;">✅ Enabled</span>
          <?php else: ?>
            <span style="color: #dc3232;">❌ Disabled</span>
          <?php endif; ?>
        </li>
        <li style="padding: 5px 0;">
          <strong>Webhook URL:</strong>
          <?php if (!empty($webhook_url)): ?>
            <code style="background: #fff; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($webhook_url); ?></code>
          <?php else: ?>
            <span style="color: #dc3232;">Not configured</span>
          <?php endif; ?>
        </li>
      </ul>
    </div>

    <!-- Configuration Form -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
      <h3 style="margin-top: 0; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">⚙️ Configuration</h3>

      <form method="post" action="">
        <?php wp_nonce_field('sb_webhook_settings', 'sb_webhook_settings_nonce'); ?>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="sb_webhook_enabled">Enable Webhooks</label>
            </th>
            <td>
              <label>
                <input
                  type="checkbox"
                  name="sb_webhook_enabled"
                  id="sb_webhook_enabled"
                  value="1"
                  <?php checked($webhook_enabled, '1'); ?>
                >
                Send webhooks to n8n/make on user registration
              </label>
              <p class="description">
                ⚠️ <strong>Important:</strong> Configure Supabase first (see instructions below)
              </p>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="sb_webhook_url">Webhook URL</label>
            </th>
            <td>
              <input
                type="url"
                name="sb_webhook_url"
                id="sb_webhook_url"
                value="<?php echo esc_attr($webhook_url); ?>"
                class="regular-text"
                placeholder="https://hooks.n8n.cloud/webhook/..."
              >
              <p class="description">
                Your n8n or make.com webhook endpoint URL<br>
                📌 Example: <code>https://hooks.n8n.cloud/webhook/abc123xyz</code>
              </p>
            </td>
          </tr>
        </table>

        <p class="submit">
          <input type="submit" name="submit" id="submit" class="button button-primary" value="💾 Save Settings">
        </p>
      </form>
    </div>

    <!-- Supabase Setup Instructions -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
      <h3 style="margin-top: 0; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">🛠️ Supabase Setup</h3>

      <p>Before enabling webhooks, you must deploy the webhook system to your Supabase project.</p>

      <details style="margin: 15px 0;">
        <summary style="cursor: pointer; padding: 10px; background: #f0f6fc; border-radius: 4px; font-weight: 600;">
          📋 Step 1: Deploy Database Schema (SQL)
        </summary>
        <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1;">
          <p>1. Open <strong>Supabase Dashboard → SQL Editor</strong></p>
          <p>2. Copy the SQL code from <code>webhook-system/webhook-system.sql</code></p>
          <p>3. Paste and execute in SQL Editor</p>
          <p>4. Verify: <code>SELECT COUNT(*) FROM webhook_logs;</code> should return 0</p>
        </div>
      </details>

      <details style="margin: 15px 0;">
        <summary style="cursor: pointer; padding: 10px; background: #f0f6fc; border-radius: 4px; font-weight: 600;">
          ⚡ Step 2: Deploy Edge Function
        </summary>
        <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1;">
          <p>1. Copy code from <code>webhook-system/send-webhook-function.ts</code></p>
          <p>2. Deploy via Supabase CLI:</p>
          <pre style="background: #2d2d2d; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow-x: auto;">mkdir -p supabase/functions/send-webhook
cp send-webhook-function.ts supabase/functions/send-webhook/index.ts
supabase functions deploy send-webhook</pre>
          <p>3. Configure secrets in <strong>Supabase Dashboard → Edge Functions → send-webhook → Secrets</strong>:</p>
          <ul>
            <li><code>SUPABASE_URL</code> = Your Supabase URL</li>
            <li><code>SUPABASE_SERVICE_ROLE_KEY</code> = Service Role Key (Dashboard → Settings → API)</li>
            <li><code>WEBHOOK_URL</code> = Webhook URL from configuration above</li>
          </ul>
        </div>
      </details>

      <details style="margin: 15px 0;">
        <summary style="cursor: pointer; padding: 10px; background: #f0f6fc; border-radius: 4px; font-weight: 600;">
          🔌 Step 3: Enable pg_net Extension
        </summary>
        <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1;">
          <p>1. Go to <strong>Supabase Dashboard → Database → Extensions</strong></p>
          <p>2. Find <code>pg_net</code> and toggle <strong>ON</strong></p>
          <p>3. Verify: <code>SELECT * FROM pg_available_extensions WHERE name = 'pg_net';</code></p>
        </div>
      </details>

      <p style="margin-top: 20px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745; color: #155724;">
        <strong>📖 Full Documentation:</strong> See <code>webhook-system/DEPLOYMENT.md</code> for detailed step-by-step guide.
      </p>
    </div>

    <!-- Testing (Stub) -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
      <h3 style="margin-top: 0; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">🧪 Testing</h3>

      <p>Test webhook delivery after configuration:</p>

      <button
        type="button"
        id="sb-test-webhook-btn"
        class="button button-secondary"
        <?php if ($webhook_enabled !== '1' || empty($webhook_url)): ?>disabled<?php endif; ?>
      >
        🚀 Send Test Webhook
      </button>

      <div id="sb-test-webhook-result" style="margin-top: 15px;"></div>

      <?php if ($webhook_enabled !== '1' || empty($webhook_url)): ?>
        <p class="description" style="margin-top: 10px;">
          ⚠️ Enable webhooks and configure URL first
        </p>
      <?php endif; ?>
    </div>

    <!-- Architecture Overview -->
    <div style="background: #f0f6fc; padding: 20px; border-left: 4px solid #2271b1; margin: 20px 0;">
      <h3 style="margin-top: 0;">🏗️ How It Works</h3>
      <pre style="background: #fff; padding: 15px; border-radius: 4px; overflow-x: auto;">User Registration (WordPress)
    ↓
INSERT wp_user_registrations
    ↓
Database Trigger: trigger_registration_webhook()
    ↓ (async via pg_net.http_post)
Edge Function: send-webhook
    ↓ (3 retries: 1s, 2s, 4s)
n8n/make Webhook Endpoint
    ↓
Update webhook_logs table</pre>

      <p><strong>Key Features:</strong></p>
      <ul>
        <li>✅ Immediate delivery (no cron delays)</li>
        <li>✅ Automatic retries with exponential backoff</li>
        <li>✅ Full logging in webhook_logs table</li>
        <li>✅ Secure (SERVICE_ROLE_KEY in Edge Function only)</li>
      </ul>
    </div>
  </div>

  <!-- JavaScript for test button (stub) -->
  <script>
  jQuery(document).ready(function($) {
    $('#sb-test-webhook-btn').on('click', function() {
      const btn = $(this);
      const resultDiv = $('#sb-test-webhook-result');

      btn.prop('disabled', true).text('⏳ Sending...');
      resultDiv.html('<p style="padding: 12px; background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460;">🔄 Отправка тестового webhook...</p>');

      // TODO: Implement AJAX call to send test webhook
      setTimeout(function() {
        btn.prop('disabled', false).text('🚀 Send Test Webhook');
        resultDiv.html('<p style="padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; color: #856404;">⚠️ Test functionality coming soon. Full implementation after Supabase deployment.</p>');
      }, 2000);
    });
  });
  </script>
  <?php
}
?>
