<?php
/**
 * Plugin Name: Supabase Bridge (Auth)
 * Description: Mirrors Supabase users into WordPress and logs them in via JWT. Enhanced security with CSP, audit logging, and hardening.
 * Version: 0.4.0
 * Author: Alexey Krol
 * License: MIT
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) exit;

require __DIR__ . '/vendor/autoload.php'; // после composer шага

// === Security Headers ===
add_action('send_headers', 'sb_add_security_headers');
function sb_add_security_headers() {
  if (!headers_sent()) {
    // Prevent clickjacking attacks
    header('X-Frame-Options: SAMEORIGIN');
    // Prevent MIME-type sniffing
    header('X-Content-Type-Options: nosniff');
    // Enable XSS protection in older browsers
    header('X-XSS-Protection: 1; mode=block');
    // Referrer policy for privacy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Content Security Policy (strict for login, relaxed for logged-in users)
    // Only apply strict CSP to non-admin, non-logged-in users (login/registration pages)
    // This prevents conflicts with MemberPress/Alpine.js which require 'unsafe-eval'
    if (!is_admin() && !is_user_logged_in()) {
      $csp = "default-src 'self'; " .
             "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
             "connect-src 'self' https://*.supabase.co; " .
             "style-src 'self' 'unsafe-inline'; " .
             "img-src 'self' data: https:; " .
             "font-src 'self' data:; " .
             "frame-ancestors 'self';";
      header("Content-Security-Policy: " . $csp);
    }
  }
}

// === Supabase Credentials Verification ===
function sb_verify_supabase_credentials($url, $anon_key) {
  // Quick validation
  if (empty($url) || empty($anon_key)) {
    return ['success' => false, 'error' => 'URL or Anon Key is empty'];
  }

  // Check URL format
  if (!preg_match('/^https?:\/\/.+\.supabase\.co$/', $url)) {
    return ['success' => false, 'error' => 'Invalid Supabase URL format (should be https://yourproject.supabase.co)'];
  }

  // Test API connection
  $response = wp_remote_get($url . '/auth/v1/settings', [
    'headers' => [
      'apikey' => $anon_key,
      'Authorization' => 'Bearer ' . $anon_key
    ],
    'timeout' => 5
  ]);

  if (is_wp_error($response)) {
    return ['success' => false, 'error' => 'Connection failed: ' . $response->get_error_message()];
  }

  $status_code = wp_remote_retrieve_response_code($response);
  if ($status_code === 200) {
    return ['success' => true];
  } else {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $error_msg = $body['message'] ?? 'HTTP ' . $status_code;
    return ['success' => false, 'error' => $error_msg];
  }
}

// === Encryption helpers ===
function sb_encrypt($value) {
  if (empty($value)) return '';
  $key = wp_salt('auth');
  $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
  $encrypted = openssl_encrypt($value, 'aes-256-cbc', $key, 0, $iv);
  return base64_encode($encrypted . '::' . $iv);
}

function sb_decrypt($encrypted_value) {
  if (empty($encrypted_value)) return '';
  $key = wp_salt('auth');
  $data = base64_decode($encrypted_value);
  if (strpos($data, '::') === false) return $encrypted_value; // fallback for unencrypted
  list($encrypted, $iv) = explode('::', $data, 2);
  return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

// === Конфиг из БД (encrypted) или окружения (fallback) ===
function sb_cfg($key, $def = null) {
  // Special handling for SUPABASE_PROJECT_REF - extract from URL
  if ($key === 'SUPABASE_PROJECT_REF') {
    $url = sb_cfg('SUPABASE_URL', '');
    if (!empty($url) && preg_match('/https?:\/\/([^.]+)\.supabase\.co/', $url, $matches)) {
      return $matches[1]; // Extract project ref from URL
    }
  }

  // Try database first (encrypted storage)
  $db_key = 'sb_' . strtolower($key);
  $encrypted_value = get_option($db_key, false);

  if ($encrypted_value !== false && !empty($encrypted_value)) {
    return sb_decrypt($encrypted_value);
  }

  // Fallback to environment variables (wp-config.php)
  $v = getenv($key);
  return $v !== false ? $v : $def;
}
// Прим.: Credentials хранятся зашифрованными в БД через Settings UI
// SUPABASE_PROJECT_REF извлекается автоматически из SUPABASE_URL
// Fallback: можно добавить в wp-config.php: SUPABASE_URL, SUPABASE_ANON_KEY

// === Helper: Get Thank You Page URL from Settings ===
function sb_get_thankyou_url() {
  $page_id = get_option('sb_thankyou_page_id');
  if ($page_id && $page_id !== '') {
    $url = get_permalink($page_id);
    if ($url) {
      // Extract just the path from the full URL
      $path = parse_url($url, PHP_URL_PATH);
      return $path ?: '/registr/'; // fallback if parsing fails
    }
  }
  return '/registr/'; // default fallback
}

// Глобальная переменная для хранения контента auth form
global $sb_auth_form_content;
$sb_auth_form_content = null;

// === Shortcode [supabase_auth_form] ===
add_shortcode('supabase_auth_form', function() {
  global $sb_auth_form_content;

  $auth_form_path = plugin_dir_path(__FILE__) . 'auth-form.html';

  if (!file_exists($auth_form_path)) {
    return '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">⚠️ auth-form.html not found. Please reinstall the Supabase Bridge plugin.</div>';
  }

  // Сохраняем контент в глобальную переменную
  $sb_auth_form_content = file_get_contents($auth_form_path);

  // Возвращаем уникальный placeholder
  return '<div id="sb-auth-form-placeholder-' . uniqid() . '"></div>';
});

// === Заменяем placeholder на реальный контент ПОСЛЕ всех WordPress фильтров ===
add_filter('the_content', function($content) {
  global $sb_auth_form_content;

  // Если есть сохраненный контент формы
  if ($sb_auth_form_content && strpos($content, 'sb-auth-form-placeholder-') !== false) {
    // Заменяем placeholder на реальный контент (ПОСЛЕ всех фильтров WordPress)
    $content = preg_replace(
      '/<div id="sb-auth-form-placeholder-[^"]+"><\/div>/',
      $sb_auth_form_content,
      $content
    );

    // Очищаем переменную
    $sb_auth_form_content = null;
  }

  return $content;
}, 9999); // Максимальный приоритет - выполняется последним

// === Добавляем shortcode в whitelist ===
add_filter('no_texturize_shortcodes', function($shortcodes) {
  $shortcodes[] = 'supabase_auth_form';
  return $shortcodes;
});

// Подключим supabase-js и прокинем public-конфиг (чтоб не хардкодить в HTML)
add_action('wp_enqueue_scripts', function () {
  // Только для страниц сайта (не админки)
  if (is_admin()) return;
  wp_enqueue_script('supabase-js', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', [], null, true);
  wp_add_inline_script('supabase-js', 'window.SUPABASE_CFG = ' . wp_json_encode([
    'url'  => sb_cfg('SUPABASE_URL', ''),       // напр. https://<project-ref>.supabase.co
    'anon' => sb_cfg('SUPABASE_ANON_KEY', ''),  // public anon key
    'thankYouUrl' => sb_get_thankyou_url(),     // Thank You Page URL from Settings
  ]) . ';', 'before');
});

// === Elementor CSP Compatibility ===
// Отключаем CSP для страниц с шорткодом Supabase (для совместимости с Elementor)
add_action('send_headers', function() {
  global $post;

  // Проверяем есть ли шорткод на странице
  if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'supabase_auth_form')) {
    // Удаляем CSP headers которые могут блокировать Supabase SDK
    header_remove('Content-Security-Policy');
    header_remove('X-Content-Security-Policy');
    header_remove('X-WebKit-CSP');
  }
}, 1);

// Альтернативный метод через template_redirect (работает раньше)
add_action('template_redirect', function() {
  global $post;

  if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'supabase_auth_form')) {
    header_remove('Content-Security-Policy');
    header_remove('X-Content-Security-Policy');
    header_remove('X-WebKit-CSP');
  }
}, 1);

// REST: приём токена, верификация, создание/логин WP-пользователя
add_action('rest_api_init', function () {
  register_rest_route('supabase-auth', '/callback', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true',
    'callback' => 'sb_handle_callback',
  ]);
  register_rest_route('supabase-auth', '/logout', [
    'methods'  => 'POST',
    'permission_callback' => function(){ return is_user_logged_in(); },
    'callback' => 'sb_handle_logout',
  ]);
});

function sb_handle_callback(\WP_REST_Request $req) {
  // Rate Limiting: Prevent brute force attacks
  $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $rate_key = 'sb_rate_' . md5($client_ip);
  $attempts = get_transient($rate_key) ?: 0;

  if ($attempts >= 10) {
    return new \WP_Error('rate_limit', 'Too many requests. Please try again later.', ['status'=>429]);
  }

  set_transient($rate_key, $attempts + 1, 60); // 60 seconds window

  // CSRF Protection: Strict Origin/Referer validation
  $origin = $req->get_header('origin');
  $referer = $req->get_header('referer');
  $allowed_host = parse_url(home_url(), PHP_URL_HOST);

  // Extract host from Origin or Referer header
  $request_host = null;
  if ($origin) {
    $request_host = parse_url($origin, PHP_URL_HOST);
  } elseif ($referer) {
    $request_host = parse_url($referer, PHP_URL_HOST);
  }

  // MUST have Origin or Referer, and it MUST exactly match our host
  if (!$request_host || $request_host !== $allowed_host) {
    return new \WP_Error('csrf', 'Invalid origin', ['status'=>403]);
  }

  $jwt = $req->get_param('access_token');
  if (!$jwt) return new \WP_Error('no_jwt','Missing access_token',['status'=>400]);

  $projectRef = sb_cfg('SUPABASE_PROJECT_REF', '');
  if (!$projectRef) {
    error_log('Supabase Bridge: SUPABASE_PROJECT_REF not configured');
    return new \WP_Error('cfg','Authentication service not configured',['status'=>500]);
  }

  $issuer = "https://{$projectRef}.supabase.co/auth/v1";
  $jwks  = "{$issuer}/.well-known/jwks.json";

  try {
    // 1) Забираем JWKS (with caching for performance)
    $cache_key = 'sb_jwks_' . md5($jwks);
    $keys = get_transient($cache_key);

    if ($keys === false) {
      // Cache miss - fetch from Supabase
      $resp = wp_remote_get($jwks, [
        'timeout' => 5,
        'sslverify' => true, // Ensure SSL verification
        'user-agent' => 'Supabase-Bridge-WordPress/0.3.3'
      ]);

      if (is_wp_error($resp)) {
        error_log('Supabase Bridge: JWKS fetch failed - ' . $resp->get_error_message());
        throw new Exception('Authentication service unavailable');
      }

      $status_code = wp_remote_retrieve_response_code($resp);
      if ($status_code !== 200) {
        error_log('Supabase Bridge: JWKS fetch returned status ' . $status_code);
        throw new Exception('Authentication service unavailable');
      }

      $keys = json_decode(wp_remote_retrieve_body($resp), true);
      if (!isset($keys['keys']) || !is_array($keys['keys'])) {
        error_log('Supabase Bridge: Invalid JWKS format');
        throw new Exception('Authentication service error');
      }

      // Cache for 1 hour (3600 seconds)
      set_transient($cache_key, $keys, 3600);
    }

    // 2) Проверяем JWT (RS256) и клеймы
    $publicKeys = \Firebase\JWT\JWK::parseKeySet($keys);
    $decoded = \Firebase\JWT\JWT::decode($jwt, $publicKeys);
    $claims = (array)$decoded;

    // Validate issuer
    if (($claims['iss'] ?? '') !== $issuer) {
      error_log('Supabase Bridge: Invalid issuer claim');
      throw new Exception('Invalid authentication token');
    }

    // Validate audience
    if (($claims['aud'] ?? '') !== 'authenticated') {
      error_log('Supabase Bridge: Invalid audience claim');
      throw new Exception('Invalid authentication token');
    }

    // Validate expiration
    if (isset($claims['exp']) && time() >= intval($claims['exp'])) {
      error_log('Supabase Bridge: Token expired');
      throw new Exception('Authentication token expired');
    }

    // Validate required fields
    if (empty($claims['email']) || empty($claims['sub'])) {
      error_log('Supabase Bridge: Missing required claims (email/sub)');
      throw new Exception('Invalid authentication token');
    }

    // Security: Require verified email for OAuth providers (not for Magic Link)
    // Magic Link = provider is "email" - clicking the link IS verification
    // OAuth (google/facebook) = trust the provider unless email_verified is explicitly false
    //   - email_verified=true: Pass ✅
    //   - email_verified=null/missing: Pass ✅ (OAuth provider implicitly verified)
    //   - email_verified=false: Fail ❌ (explicit rejection)
    $provider = $claims['app_metadata']->provider ?? 'unknown';
    $isMagicLink = ($provider === 'email');

    // DEBUG: Log provider and email_verified status
    error_log('Supabase Bridge DEBUG: provider=' . $provider . ', email_verified=' . var_export($claims['email_verified'] ?? null, true) . ', email=' . sanitize_email($claims['email']));

    // Only block if email_verified is explicitly set to false
    if (!$isMagicLink && isset($claims['email_verified']) && $claims['email_verified'] === false) {
      error_log('Supabase Bridge: Email explicitly NOT verified for OAuth provider (' . $provider . ') - ' . sanitize_email($claims['email']));
      throw new Exception('Email verification required');
    }

    // 3) Найдём/создадим WP-пользователя
    $email = sanitize_email($claims['email']);
    $supabase_user_id = sanitize_text_field($claims['sub']);

    // Additional email validation
    if (!is_email($email)) {
      error_log('Supabase Bridge: Invalid email format - ' . $email);
      throw new Exception('Invalid email address');
    }

    // КРИТИЧНО: Проверяем по Supabase UUID ПЕРВЫМ (уникальный идентификатор)
    // Это предотвращает дублирование даже при race condition
    $existing_users = get_users([
      'meta_key' => 'supabase_user_id',
      'meta_value' => $supabase_user_id,
      'number' => 1
    ]);

    if (!empty($existing_users)) {
      // Пользователь с таким Supabase ID уже существует
      $user = $existing_users[0];
      error_log('Supabase Bridge: User found by supabase_user_id - ' . $email);
    } else {
      // Проверяем по email
      $user = get_user_by('email', $email);
    }

    if (!$user) {
      // Distributed lock для предотвращения race condition
      $lock_key = 'sb_create_lock_' . md5($supabase_user_id);

      // Проверяем есть ли уже lock (другой процесс создает пользователя)
      if (get_transient($lock_key)) {
        // Ждем 2 секунды и пробуем найти пользователя снова
        sleep(2);

        // Проверяем по Supabase UUID снова
        $existing_users = get_users([
          'meta_key' => 'supabase_user_id',
          'meta_value' => $supabase_user_id,
          'number' => 1
        ]);

        if (!empty($existing_users)) {
          $user = $existing_users[0];
          error_log('Supabase Bridge: User found after lock wait - ' . $email);
        } else {
          // Все еще нет - пробуем по email
          $user = get_user_by('email', $email);

          if (!$user) {
            error_log('Supabase Bridge: Lock expired but user not found - ' . $email);
            throw new Exception('User creation timeout - please try again');
          }
        }
      } else {
        // Устанавливаем lock на 5 секунд
        set_transient($lock_key, 1, 5);

        // Generate strong random password
        $password = wp_generate_password(32, true, true);
        $uid = wp_create_user($email, $password, $email);

        if (is_wp_error($uid)) {
          // Удаляем lock
          delete_transient($lock_key);

          // Race condition: user might have been created between checks and wp_create_user
          // Try finding the user again by Supabase ID
          $existing_users = get_users([
            'meta_key' => 'supabase_user_id',
            'meta_value' => $supabase_user_id,
            'number' => 1
          ]);

          if (!empty($existing_users)) {
            $user = $existing_users[0];
            error_log('Supabase Bridge: User found by UUID after failed creation - ' . $email);
          } else {
            // Пробуем по email
            $user = get_user_by('email', $email);
            if (!$user) {
              // Still not found - real error
              error_log('Supabase Bridge: User creation failed - ' . $uid->get_error_message());
              throw new Exception('Unable to create user account');
            }
            error_log('Supabase Bridge: User found by email after failed creation - ' . $email);
          }
        } else {
          // User created successfully
          // Store Supabase user ID
          update_user_meta($uid, 'supabase_user_id', $supabase_user_id);

          // Set default role (subscriber)
          $user = get_user_by('id', $uid);
          if ($user) {
            $user->set_role('subscriber');
          }

          // Удаляем lock
          delete_transient($lock_key);

          error_log('Supabase Bridge: User created successfully - User ID: ' . $uid);
        }
      }
    }

    // Update Supabase user ID for user (handles both new and existing users)
    if ($user && $user->ID) {
      update_user_meta($user->ID, 'supabase_user_id', $supabase_user_id);
    }

    // 4) Логиним в WP
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());

    // Clear rate limit on successful authentication
    delete_transient($rate_key);

    // Audit log (success)
    error_log(sprintf(
      'Supabase Bridge: Successful authentication - User ID: %d, Email: %s, IP: %s',
      $user->ID,
      $email,
      $client_ip
    ));

    return ['ok'=>true, 'user_id'=>$user->ID];
  } catch (\Throwable $e) {
    // Audit log (failure)
    error_log(sprintf(
      'Supabase Bridge: Authentication failed - Error: %s, IP: %s',
      $e->getMessage(),
      $client_ip
    ));

    return new \WP_Error('auth_failed', $e->getMessage(), ['status'=>401]);
  }
}

function sb_handle_logout(\WP_REST_Request $req) {
  // CSRF Protection: Strict Origin validation (same as callback)
  $origin = $req->get_header('origin');
  $referer = $req->get_header('referer');
  $allowed_host = parse_url(home_url(), PHP_URL_HOST);

  // Extract host from Origin or Referer header
  $request_host = null;
  if ($origin) {
    $request_host = parse_url($origin, PHP_URL_HOST);
  } elseif ($referer) {
    $request_host = parse_url($referer, PHP_URL_HOST);
  }

  // MUST have Origin or Referer, and it MUST exactly match our host
  if (!$request_host || $request_host !== $allowed_host) {
    return new \WP_Error('csrf', 'Invalid origin', ['status'=>403]);
  }

  // User already verified by permission_callback (is_user_logged_in)
  $user_id = get_current_user_id();
  $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

  wp_destroy_current_session();
  wp_clear_auth_cookie();

  // Audit log (logout)
  error_log(sprintf(
    'Supabase Bridge: User logout - User ID: %d, IP: %s',
    $user_id,
    $client_ip
  ));

  return ['ok' => true];
}

// === Activation Hook - redirect to setup page ===
register_activation_hook(__FILE__, 'sb_activation_redirect');
function sb_activation_redirect() {
  add_option('sb_activation_redirect', true);
}

add_action('admin_init', function() {
  if (get_option('sb_activation_redirect', false)) {
    delete_option('sb_activation_redirect');
    wp_redirect(admin_url('admin.php?page=supabase-bridge-setup'));
    exit;
  }
});

// === Admin Menu - Setup Instructions Page ===
add_action('admin_menu', function() {
  add_menu_page(
    'Supabase Bridge Setup',           // Page title
    'Supabase Bridge',                 // Menu title
    'manage_options',                  // Capability
    'supabase-bridge-setup',           // Menu slug
    'sb_render_setup_page',            // Callback
    'dashicons-admin-network',         // Icon
    80                                 // Position
  );
});

function sb_render_setup_page() {
  $verification_message = null;

  // Handle settings form submission
  if (isset($_POST['sb_save_settings']) && check_admin_referer('sb_settings_nonce')) {
    // Page configuration
    update_option('sb_thankyou_page_id', intval($_POST['sb_thankyou_page_id'] ?? 0));

    // Supabase credentials (encrypted)
    $url = sanitize_text_field($_POST['sb_supabase_url'] ?? '');
    $anon_key = sanitize_text_field($_POST['sb_supabase_anon_key'] ?? '');

    $credentials_updated = false;

    if (!empty($url)) {
      update_option('sb_supabase_url', sb_encrypt($url));
      $credentials_updated = true;
    }
    if (!empty($anon_key)) {
      update_option('sb_supabase_anon_key', sb_encrypt($anon_key));
      $credentials_updated = true;
    }

    // Verify credentials if they were updated
    if ($credentials_updated && !empty($url) && !empty($anon_key)) {
      $verification = sb_verify_supabase_credentials($url, $anon_key);
      if ($verification['success']) {
        $verification_message = ['type' => 'success', 'text' => '✅ Credentials verified and encrypted in database.'];
      } else {
        $verification_message = ['type' => 'error', 'text' => '⚠️ Verification failed: ' . esc_html($verification['error'])];
      }
    } else {
      $verification_message = ['type' => 'success', 'text' => '✅ Settings saved!'];
    }
  }

  $thankyou_page_id = get_option('sb_thankyou_page_id', 0);
  ?>
  <div class="wrap">
    <h1>🚀 Supabase Bridge - Setup Instructions</h1>

    <!-- Prerequisites Warning -->
    <div class="notice notice-warning" style="border-left-color: #f59e0b; padding: 15px;">
      <h3 style="margin-top: 0;">⚠️ Before You Start</h3>
      <p><strong>Prerequisites:</strong> You must configure Google OAuth and Facebook OAuth in your Supabase Dashboard first.</p>
      <p>📖 <strong>Documentation:</strong> <a href="https://supabase.com/docs/guides/auth/social-login/auth-google" target="_blank">Google OAuth Setup</a> | <a href="https://supabase.com/docs/guides/auth/social-login/auth-facebook" target="_blank">Facebook OAuth Setup</a></p>
      <p>💡 <strong>Magic Link</strong> (passwordless email) works out of the box - no extra setup needed.</p>
    </div>

    <h2>📋 Step 1: Configure Plugin Settings</h2>

    <!-- Settings Section -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
      <form method="post" action="">
        <?php wp_nonce_field('sb_settings_nonce'); ?>

        <h3 style="margin-top: 0; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">🔐 Supabase Credentials (Encrypted Storage)</h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="sb_supabase_url">Supabase URL</label>
            </th>
            <td>
              <input
                type="url"
                name="sb_supabase_url"
                id="sb_supabase_url"
                value="<?php echo esc_attr(sb_cfg('SUPABASE_URL', '')); ?>"
                class="regular-text"
                placeholder="https://your-project.supabase.co"
              >
              <p class="description">Example: <code>https://abcdefghijk.supabase.co</code> (Project Ref extracted automatically)</p>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="sb_supabase_anon_key">Anon Key</label>
            </th>
            <td>
              <input
                type="password"
                name="sb_supabase_anon_key"
                id="sb_supabase_anon_key"
                value="<?php echo esc_attr(sb_cfg('SUPABASE_ANON_KEY', '')); ?>"
                class="large-text"
                placeholder="eyJhbGci..."
              >
              <p class="description">Example: <code>eyJhbGciOiJIUzI1...</code> (will be encrypted in database)</p>
            </td>
          </tr>
        </table>

        <?php if ($verification_message): ?>
          <div style="margin: 15px 0; padding: 12px; border-radius: 4px; <?php echo $verification_message['type'] === 'success' ? 'background: #d4edda; border: 1px solid #c3e6cb; color: #155724;' : 'background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;'; ?>">
            <?php echo $verification_message['text']; ?>
          </div>
        <?php endif; ?>

        <h3 style="border-bottom: 1px solid #e0e0e0; padding-bottom: 10px; margin-top: 30px;">🎉 Thank You Page (New Users Redirect)</h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="sb_thankyou_page_id">Select Page</label>
            </th>
            <td>
              <?php
              wp_dropdown_pages([
                'name' => 'sb_thankyou_page_id',
                'id' => 'sb_thankyou_page_id',
                'selected' => $thankyou_page_id,
                'show_option_none' => '— Select a page —',
                'option_none_value' => '0'
              ]);
              ?>
              <?php if ($thankyou_page_id): ?>
                <p class="description">
                  <strong>Current URL:</strong> <a href="<?php echo esc_url(get_permalink($thankyou_page_id)); ?>" target="_blank"><?php echo esc_url(get_permalink($thankyou_page_id)); ?></a>
                  <br><em>💡 New users (registered < 60 seconds ago) will be redirected here</em>
                </p>
              <?php else: ?>
                <p class="description">Select the page where new users will be redirected after registration</p>
              <?php endif; ?>
            </td>
          </tr>
        </table>

        <p class="submit">
          <input type="submit" name="sb_save_settings" class="button button-primary" value="💾 Save Settings">
        </p>
      </form>
    </div>

    <hr style="margin: 40px 0;">

    <h2>Step 2: Add shortcode to your login page</h2>
    <p>Create or edit a page, add <strong>Shortcode block</strong>, insert shortcode, and publish.</p>
    <div style="margin: 20px 0; padding: 15px; background: #f0f6fc; border-left: 4px solid #0969da; border-radius: 6px;">
      <code style="font-size: 16px; font-weight: 600; color: #0969da;">[supabase_auth_form]</code>
      <button
        onclick="navigator.clipboard.writeText('[supabase_auth_form]').then(() => { const btn = event.target; btn.textContent = '✅ Copied!'; setTimeout(() => btn.textContent = '📋 Copy', 2000); })"
        style="padding: 6px 12px; background: #0969da; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 15px; font-size: 13px;"
      >
        📋 Copy
      </button>
    </div>
    <p><em>💡 Save your login page URL - you'll need it for Step 3</em></p>

    <hr style="margin: 40px 0;">

    <h2>Step 3: Add login page URL to Supabase</h2>
    <p>Go to <a href="https://app.supabase.com" target="_blank">app.supabase.com</a> → your project<?php if (sb_cfg('SUPABASE_PROJECT_REF')): ?> (<code><?php echo esc_html(sb_cfg('SUPABASE_PROJECT_REF')); ?></code>)<?php endif; ?> → <strong>Authentication → URL Configuration</strong> → add your login page URL to <strong>Redirect URLs</strong> → Save.</p>

    <hr style="margin: 40px 0;">

    <h2>Step 4: Test</h2>
    <p>Open your login page (incognito mode). Try <strong>Google OAuth</strong>, <strong>Facebook OAuth</strong>, and <strong>Magic Link</strong>. Check <strong>WordPress → Users</strong> for new user.</p>

    <hr style="margin: 40px 0;">

    <h2>🐛 Troubleshooting</h2>
    <p><strong>Form doesn't appear:</strong> Open console (F12) → run <code>console.log(window.SUPABASE_CFG)</code> → should show <code>url</code> and <code>anon</code></p>
    <p><strong>OAuth doesn't work:</strong> Check Prerequisites + verify login URL in Supabase Redirect URLs</p>
    <p><strong>⚠️ Development environment:</strong> Ensure permalink structure (Settings → Permalinks) matches production for OAuth testing</p>

    <div class="notice notice-success" style="margin-top: 30px;">
      <p><strong>🎉 Done!</strong> Your Supabase authentication is integrated with WordPress.</p>
    </div>
  </div>
  <?php
}