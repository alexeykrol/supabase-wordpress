<?php
/**
 * Plugin Name: Supabase Bridge (Auth)
 * Description: Mirrors Supabase users into WordPress and logs them in via JWT. Enhanced security with CSP, audit logging, and hardening. Includes webhook system for n8n/Make.com integration.
 * Version: 0.8.4
 * Author: Alexey Krol
 * License: MIT
 * Requires at least: 5.0
 * Tested up to: 6.8
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
  // Try $_ENV first (most reliable in wp-config.php)
  if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
    return $_ENV[$key];
  }

  // Try $_SERVER (also set in wp-config.php)
  if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
    return $_SERVER[$key];
  }

  // Last resort: getenv()
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

// === Phase 1: Supabase Tables Management ===
// Execute SQL query via Supabase REST API
function sb_execute_supabase_sql($sql) {
  $url = sb_cfg('SUPABASE_URL');
  $anon_key = sb_cfg('SUPABASE_ANON_KEY');

  if (empty($url) || empty($anon_key)) {
    return ['success' => false, 'error' => 'Supabase credentials not configured'];
  }

  // Supabase REST API endpoint for SQL queries
  $endpoint = rtrim($url, '/') . '/rest/v1/rpc/exec_sql';

  $response = wp_remote_post($endpoint, [
    'headers' => [
      'apikey' => $anon_key,
      'Authorization' => 'Bearer ' . $anon_key,
      'Content-Type' => 'application/json',
    ],
    'body' => json_encode(['query' => $sql]),
    'timeout' => 30,
  ]);

  if (is_wp_error($response)) {
    return ['success' => false, 'error' => $response->get_error_message()];
  }

  $code = wp_remote_retrieve_response_code($response);
  $body = wp_remote_retrieve_body($response);

  if ($code >= 200 && $code < 300) {
    return ['success' => true, 'data' => json_decode($body, true)];
  }

  return ['success' => false, 'error' => "HTTP $code: $body"];
}

// Check if Supabase tables exist
function sb_check_tables_exist() {
  $url = sb_cfg('SUPABASE_URL');
  $anon_key = sb_cfg('SUPABASE_ANON_KEY');

  if (empty($url) || empty($anon_key)) {
    return ['exists' => false, 'error' => 'Supabase credentials not configured'];
  }

  // Try to query wp_registration_pairs table
  $endpoint = rtrim($url, '/') . '/rest/v1/wp_registration_pairs?limit=0';

  $response = wp_remote_get($endpoint, [
    'headers' => [
      'apikey' => $anon_key,
      'Authorization' => 'Bearer ' . $anon_key,
    ],
    'timeout' => 10,
  ]);

  if (is_wp_error($response)) {
    return ['exists' => false, 'error' => $response->get_error_message()];
  }

  $code = wp_remote_retrieve_response_code($response);

  // 200 = table exists, 404/400 = table doesn't exist
  return ['exists' => ($code === 200), 'code' => $code];
}

// === Security: Input Validation Functions ===

/**
 * Validate email address (защита от SQL injection и XSS)
 *
 * @param string $email Email to validate
 * @return string|false Sanitized email or false if invalid
 */
function sb_validate_email($email) {
  if (empty($email)) {
    return false;
  }

  // Remove whitespace and convert to lowercase
  $email = trim(strtolower($email));

  // WordPress built-in email validation
  if (!is_email($email)) {
    error_log("Supabase Bridge: Invalid email format: " . sanitize_text_field($email));
    return false;
  }

  // Additional check: Max length 254 characters (RFC 5321)
  if (strlen($email) > 254) {
    error_log("Supabase Bridge: Email too long: " . strlen($email) . " characters");
    return false;
  }

  // Sanitize for database
  return sanitize_email($email);
}

/**
 * Validate URL path (защита от open redirect и path traversal)
 *
 * @param string $path URL path to validate
 * @return string|false Sanitized path or false if invalid
 */
function sb_validate_url_path($path) {
  if (empty($path)) {
    return false;
  }

  // Remove whitespace
  $path = trim($path);

  // Must start with /
  if (!str_starts_with($path, '/')) {
    error_log("Supabase Bridge: Invalid URL path (must start with /): $path");
    return false;
  }

  // Block path traversal attempts
  if (strpos($path, '..') !== false) {
    error_log("Supabase Bridge: Path traversal attempt detected: $path");
    return false;
  }

  // Block protocol attempts (http://, https://, javascript:, etc.)
  if (preg_match('/^[a-z]+:/i', $path)) {
    error_log("Supabase Bridge: Protocol in path not allowed: $path");
    return false;
  }

  // Sanitize for database (removes harmful characters)
  $path = esc_url_raw($path);

  // Max length check (reasonable URL path)
  if (strlen($path) > 2000) {
    error_log("Supabase Bridge: URL path too long: " . strlen($path) . " characters");
    return false;
  }

  return $path;
}

/**
 * Validate UUID format (защита от injection)
 *
 * @param string $uuid UUID to validate
 * @return string|false Sanitized UUID or false if invalid
 */
function sb_validate_uuid($uuid) {
  if (empty($uuid)) {
    return false;
  }

  // Remove whitespace and convert to lowercase
  $uuid = trim(strtolower($uuid));

  // UUID v4 format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
  // Where y is one of [8, 9, a, b]
  $uuid_pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

  if (!preg_match($uuid_pattern, $uuid)) {
    error_log("Supabase Bridge: Invalid UUID format: $uuid");
    return false;
  }

  return $uuid;
}

/**
 * Validate and sanitize site URL
 *
 * @param string $url Site URL to validate
 * @return string|false Sanitized URL or false if invalid
 */
function sb_validate_site_url($url) {
  if (empty($url)) {
    return false;
  }

  // WordPress built-in URL validation
  $url = esc_url_raw($url);

  if (empty($url)) {
    error_log("Supabase Bridge: Invalid site URL");
    return false;
  }

  // Must be http or https
  if (!preg_match('/^https?:\/\//', $url)) {
    error_log("Supabase Bridge: Site URL must use http or https protocol");
    return false;
  }

  return $url;
}

// === Phase 3: Supabase Sync Functions ===

// Sync registration pair to Supabase (non-blocking)
function sb_sync_pair_to_supabase($pair_data) {
  try {
    $url = sb_cfg('SUPABASE_URL');
    $anon_key = sb_cfg('SUPABASE_ANON_KEY');

    // Validate credentials
    if (empty($url) || empty($anon_key)) {
      error_log('Supabase Bridge: Sync failed - credentials not configured');
      return false;
    }

    // SECURITY: Validate all inputs before sending to Supabase
    $validated_site_url = sb_validate_site_url(get_site_url());
    $validated_reg_url = sb_validate_url_path($pair_data['registration_page_url'] ?? '');
    $validated_ty_url = sb_validate_url_path($pair_data['thankyou_page_url'] ?? '');
    $validated_id = sb_validate_uuid($pair_data['id'] ?? '');

    if (!$validated_site_url || !$validated_reg_url || !$validated_ty_url || !$validated_id) {
      error_log('Supabase Bridge: Sync failed - Invalid data detected (possible injection attempt)');
      error_log('  site_url: ' . ($validated_site_url ? 'OK' : 'INVALID'));
      error_log('  registration_page_url: ' . ($validated_reg_url ? 'OK' : 'INVALID: ' . ($pair_data['registration_page_url'] ?? 'empty')));
      error_log('  thankyou_page_url: ' . ($validated_ty_url ? 'OK' : 'INVALID: ' . ($pair_data['thankyou_page_url'] ?? 'empty')));
      error_log('  id: ' . ($validated_id ? 'OK' : 'INVALID: ' . ($pair_data['id'] ?? 'empty')));
      return false;
    }

    // Endpoint for wp_registration_pairs table
    $endpoint = rtrim($url, '/') . '/rest/v1/wp_registration_pairs';

    // Use validated data (защита от injection)
    $supabase_data = [
      'id' => $validated_id,
      'site_url' => $validated_site_url,
      'registration_page_url' => $validated_reg_url,
      'thankyou_page_url' => $validated_ty_url,
      'registration_page_id' => intval($pair_data['registration_page_id']),
      'thankyou_page_id' => intval($pair_data['thankyou_page_id']),
    ];

    // UPSERT using Prefer: resolution-merge-duplicates
    $response = wp_remote_post($endpoint, [
      'headers' => [
        'apikey' => $anon_key,
        'Authorization' => 'Bearer ' . $anon_key,
        'Content-Type' => 'application/json',
        'Prefer' => 'resolution=merge-duplicates',
        'x-site-url' => $validated_site_url, // For RLS policy check
      ],
      'body' => json_encode($supabase_data),
      'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
      error_log('Supabase Bridge: Sync failed - ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code < 300) {
      return true;
    } else {
      $body = wp_remote_retrieve_body($response);
      error_log("Supabase Bridge: Sync failed - HTTP $code: $body");
      return false;
    }
  } catch (Exception $e) {
    error_log('Supabase Bridge: Exception during sync - ' . $e->getMessage());
    return false;
  }
}

// Delete registration pair from Supabase (non-blocking)
function sb_delete_pair_from_supabase($pair_id) {
  try {
    $url = sb_cfg('SUPABASE_URL');
    $anon_key = sb_cfg('SUPABASE_ANON_KEY');

    // Validate credentials
    if (empty($url) || empty($anon_key)) {
      error_log('Supabase Bridge: Delete failed - credentials not configured');
      return false;
    }

    // SECURITY: Validate UUID before using in query
    $validated_id = sb_validate_uuid($pair_id);
    if (!$validated_id) {
      error_log('Supabase Bridge: Delete failed - Invalid pair_id (possible injection attempt): ' . $pair_id);
      return false;
    }

    // Endpoint with filter by ID (using validated UUID)
    $endpoint = rtrim($url, '/') . '/rest/v1/wp_registration_pairs?id=eq.' . urlencode($validated_id);

    // Validate site URL for RLS check
    $validated_site_url = sb_validate_site_url(get_site_url());

    $response = wp_remote_request($endpoint, [
      'method' => 'DELETE',
      'headers' => [
        'apikey' => $anon_key,
        'Authorization' => 'Bearer ' . $anon_key,
        'x-site-url' => $validated_site_url, // For RLS policy check
      ],
      'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
      error_log('Supabase Bridge: Delete failed - ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code < 300) {
      return true;
    } else {
      $body = wp_remote_retrieve_body($response);
      error_log("Supabase Bridge: Delete failed - HTTP $code: $body");
      return false;
    }
  } catch (Exception $e) {
    error_log('Supabase Bridge: Exception during delete - ' . $e->getMessage());
    return false;
  }
}

// === Phase 6: Registration Logging to Supabase ===

// Log user registration to Supabase (non-blocking analytics)
function sb_log_registration_to_supabase($user_email, $supabase_user_id, $registration_url) {
  try {
    $url = sb_cfg('SUPABASE_URL');
    $anon_key = sb_cfg('SUPABASE_ANON_KEY');

    // Validate credentials
    if (empty($url) || empty($anon_key)) {
      error_log('Supabase Bridge: Cannot log registration - credentials not configured');
      return false;
    }

    // SECURITY: Validate inputs before sending to Supabase
    $validated_email = sb_validate_email($user_email);
    $validated_user_id = sb_validate_uuid($supabase_user_id);
    $validated_reg_url = sb_validate_url_path($registration_url);

    if (!$validated_email || !$validated_user_id || !$validated_reg_url) {
      error_log('Supabase Bridge: Registration log failed - Invalid data detected (possible injection attempt)');
      error_log('  email: ' . ($validated_email ? 'OK' : 'INVALID: ' . $user_email));
      error_log('  user_id: ' . ($validated_user_id ? 'OK' : 'INVALID: ' . $supabase_user_id));
      error_log('  registration_url: ' . ($validated_reg_url ? 'OK' : 'INVALID: ' . $registration_url));
      return false;
    }

    // Find matching pair by registration_url
    $pairs = get_option('sb_registration_pairs', []);
    $pair_id = null;
    $thankyou_page_url = null;

    foreach ($pairs as $pair) {
      if ($pair['registration_page_url'] === $validated_reg_url) {
        // SECURITY: Validate pair_id before using
        $validated_pair_id = sb_validate_uuid($pair['id'] ?? '');
        $validated_ty_url = sb_validate_url_path($pair['thankyou_page_url'] ?? '');

        if ($validated_pair_id && $validated_ty_url) {
          $pair_id = $validated_pair_id;
          $thankyou_page_url = $validated_ty_url;
        } else {
          error_log('Supabase Bridge: Warning - Invalid pair data in wp_options, skipping pair_id');
        }
        break;
      }
    }

    // Endpoint for wp_user_registrations table
    $endpoint = rtrim($url, '/') . '/rest/v1/wp_user_registrations';

    // Use validated data (защита от injection)
    $log_data = [
      'user_id' => $validated_user_id,
      'pair_id' => $pair_id, // NULL if no pair found or invalid
      'user_email' => $validated_email,
      'registration_url' => $validated_reg_url,
      'thankyou_page_url' => $thankyou_page_url, // NULL if no pair found or invalid
    ];

    // Get validated site URL for RLS policy
    $validated_site_url = sb_validate_site_url(get_site_url());
    if (!$validated_site_url) {
      error_log('Supabase Bridge: Registration log failed - Invalid site URL');
      return false;
    }

    // Using Anon Key with RLS policy validation via x-site-url header
    $response = wp_remote_post($endpoint, [
      'headers' => [
        'apikey' => $anon_key,
        'Authorization' => 'Bearer ' . $anon_key,
        'Content-Type' => 'application/json',
        'x-site-url' => $validated_site_url, // For RLS policy validation
      ],
      'body' => json_encode($log_data),
      'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
      error_log('Supabase Bridge: Registration log failed - ' . $response->get_error_message());
      return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code < 300) {
      return true;
    } else {
      $body = wp_remote_retrieve_body($response);
      error_log("Supabase Bridge: Registration log failed - HTTP $code: $body");
      return false;
    }
  } catch (Exception $e) {
    error_log('Supabase Bridge: Exception during registration logging - ' . $e->getMessage());
    return false;
  }
}

// Create Supabase tables for registration pairs
function sb_create_supabase_tables() {
  // Read SQL file
  $sql_file = plugin_dir_path(__FILE__) . 'supabase-tables.sql';

  if (!file_exists($sql_file)) {
    return ['success' => false, 'error' => 'SQL file not found: supabase-tables.sql'];
  }

  $sql = file_get_contents($sql_file);

  if (empty($sql)) {
    return ['success' => false, 'error' => 'SQL file is empty'];
  }

  // Note: Supabase REST API doesn't support direct SQL execution via anon key
  // Tables must be created via Supabase Dashboard SQL Editor or Database URL
  // This function returns instructions for manual setup

  return [
    'success' => false,
    'manual_setup_required' => true,
    'instructions' => 'Please execute the SQL script via Supabase Dashboard:
1. Go to: https://supabase.com/dashboard/project/' . sb_cfg('SUPABASE_PROJECT_REF') . '/sql
2. Copy content from: ' . $sql_file . '
3. Paste and execute in SQL Editor
4. Verify tables created: wp_registration_pairs, wp_user_registrations',
    'sql_file' => $sql_file
  ];
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

  // === Phase 4: Inject registration pairs into JavaScript ===
  $pairs = get_option('sb_registration_pairs', []);

  // Prepare pairs for JavaScript (only needed fields)
  $js_pairs = [];
  foreach ($pairs as $pair) {
    $js_pairs[] = [
      'registration_url' => $pair['registration_page_url'],
      'thankyou_url' => $pair['thankyou_page_url'],
    ];
  }

  wp_add_inline_script('supabase-js', 'window.SUPABASE_CFG = ' . wp_json_encode([
    'url'  => sb_cfg('SUPABASE_URL', ''),       // напр. https://<project-ref>.supabase.co
    'anon' => sb_cfg('SUPABASE_ANON_KEY', ''),  // public anon key
    'thankYouUrl' => sb_get_thankyou_url(),     // Thank You Page URL from Settings (global fallback)
    'registrationPairs' => $js_pairs,           // Phase 4: Page-specific pairs
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

  // КРИТИЧНО: Защита от дублирующих callback запросов с атомарной блокировкой MySQL
  // Один токен должен обрабатываться только один раз
  global $wpdb;
  $token_lock_key = 'sb_lock_' . md5($jwt);

  // GET_LOCK() атомарно возвращает 1 если получили блокировку, 0 если уже заблокировано, NULL при ошибке
  // Таймаут 0 = не ждать, сразу вернуть результат
  $lock_acquired = $wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 0)", $token_lock_key));

  if ($lock_acquired != 1) {
    error_log('Supabase Bridge: Token already being processed (duplicate request blocked via MySQL lock)');
    return new \WP_Error('duplicate','Authentication already in progress',['status'=>409]);
  }

  // Блокировка автоматически освободится при закрытии соединения MySQL
  // Но для надежности освободим явно в конце функции через register_shutdown_function
  register_shutdown_function(function() use ($wpdb, $token_lock_key) {
    $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $token_lock_key));
  });

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

          // === Phase 6: Log registration to Supabase (non-blocking) ===
          // Extract registration URL from referer
          if ($referer) {
            $registration_url = parse_url($referer, PHP_URL_PATH);
            if ($registration_url) {
              sb_log_registration_to_supabase($email, $supabase_user_id, $registration_url);
              // Note: We don't check return value - logging is non-critical
            }
          }
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
    $service_key = sanitize_text_field($_POST['sb_supabase_service_key'] ?? '');

    $credentials_updated = false;

    if (!empty($url)) {
      update_option('sb_supabase_url', sb_encrypt($url));
      $credentials_updated = true;
    }
    if (!empty($anon_key)) {
      update_option('sb_supabase_anon_key', sb_encrypt($anon_key));
      $credentials_updated = true;
    }
    if (!empty($service_key)) {
      update_option('sb_supabase_service_key', sb_encrypt($service_key));
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

  // Get current tab
  $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
  ?>
  <div class="wrap">
    <h1>🚀 Supabase Bridge</h1>

    <!-- Tabs Navigation -->
    <h2 class="nav-tab-wrapper">
      <a href="?page=supabase-bridge-setup&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
        ⚙️ General Settings
      </a>
      <a href="?page=supabase-bridge-setup&tab=pairs" class="nav-tab <?php echo $current_tab === 'pairs' ? 'nav-tab-active' : ''; ?>">
        🔗 Registration Pairs
      </a>
      <a href="?page=supabase-bridge-setup&tab=webhooks" class="nav-tab <?php echo $current_tab === 'webhooks' ? 'nav-tab-active' : ''; ?>">
        🪝 Webhooks
      </a>
    </h2>

    <?php if ($current_tab === 'general'): ?>
      <!-- TAB 1: General Settings -->
      <div class="tab-content">
        <!-- Prerequisites Warning -->
        <div class="notice notice-warning" style="border-left-color: #f59e0b; padding: 15px; margin-top: 20px;">
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
              <label for="sb_supabase_anon_key">Anon Key (Public)</label>
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
              <p class="description">Used for frontend authentication (browser). Example: <code>eyJhbGciOiJIUzI1...</code></p>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="sb_supabase_service_key">Service Role Key (Secret) 🔐</label>
            </th>
            <td>
              <input
                type="password"
                name="sb_supabase_service_key"
                id="sb_supabase_service_key"
                value="<?php echo esc_attr(sb_cfg('SUPABASE_SERVICE_ROLE_KEY', '')); ?>"
                class="large-text"
                placeholder="eyJhbGci..."
              >
              <p class="description">
                <strong>⚠️ NEVER expose this key to frontend!</strong> Used for server-side operations (sync, logging).
                Bypasses RLS policies. Example: <code>eyJhbGciOiJIUzI1...</code>
              </p>
            </td>
          </tr>
        </table>

        <?php if ($verification_message): ?>
          <div style="margin: 15px 0; padding: 12px; border-radius: 4px; <?php echo $verification_message['type'] === 'success' ? 'background: #d4edda; border: 1px solid #c3e6cb; color: #155724;' : 'background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;'; ?>">
            <?php echo $verification_message['text']; ?>
          </div>
        <?php endif; ?>

        <h3 style="border-bottom: 1px solid #e0e0e0; padding-bottom: 10px; margin-top: 30px;">🎉 Global Thank You Page (Fallback)</h3>
        <p style="color: #666; margin-top: -5px; margin-bottom: 20px;">
          💡 <strong>Used when:</strong> No specific pair configured in Registration Pairs tab, or registration from non-mapped page
        </p>
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
                  <br><em>💡 Fallback redirect for new users when no specific pair exists</em>
                </p>
              <?php else: ?>
                <p class="description">Global fallback page for new user registrations (used when no specific pair configured)</p>
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

    <!-- Phase 1: Database Status -->
    <h2>📊 Supabase Database Status</h2>
    <?php
    // Check if tables exist
    $tables_check = sb_check_tables_exist();
    $tables_exist = $tables_check['exists'] ?? false;
    ?>

    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
      <?php if ($tables_exist): ?>
        <!-- Tables exist -->
        <div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724; margin-bottom: 15px;">
          <strong>✅ Tables Status: READY</strong>
          <p style="margin: 10px 0 0 0;">Registration pairs tables are created and accessible.</p>
          <ul style="margin: 10px 0 0 20px; list-style: disc;">
            <li><code>wp_registration_pairs</code> - Registration/Thank You page mappings</li>
            <li><code>wp_user_registrations</code> - User registration logs for analytics</li>
          </ul>
        </div>
        <p><em>💡 You're ready to create registration pairs in the next phase</em></p>
      <?php else: ?>
        <!-- Tables don't exist - show setup instructions -->
        <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404; margin-bottom: 15px;">
          <strong>⚠️ Tables Status: NOT CREATED</strong>
          <p style="margin: 10px 0 0 0;">Registration pairs feature requires database tables in Supabase.</p>
        </div>

        <h3 style="margin-top: 20px;">🔧 Setup Instructions</h3>
        <p>Follow these steps to create the required tables:</p>

        <div style="background: #f8f9fa; padding: 20px; border-left: 4px solid #0969da; border-radius: 4px; margin: 20px 0;">
          <h4 style="margin-top: 0;">Step 1: Open Supabase SQL Editor</h4>
          <p>
            <a href="https://supabase.com/dashboard/project/<?php echo esc_attr(sb_cfg('SUPABASE_PROJECT_REF', 'YOUR_PROJECT')); ?>/sql"
               target="_blank"
               class="button button-primary"
               style="text-decoration: none;">
              🔗 Open SQL Editor →
            </a>
          </p>

          <h4>Step 2: Copy SQL Script</h4>
          <p>SQL file location: <code><?php echo esc_html(plugin_dir_path(__FILE__) . 'supabase-tables.sql'); ?></code></p>
          <textarea readonly style="width: 100%; height: 150px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" onclick="this.select();">
<?php echo esc_textarea(file_get_contents(plugin_dir_path(__FILE__) . 'supabase-tables.sql')); ?>
          </textarea>
          <button
            onclick="const textarea = this.previousElementSibling; textarea.select(); document.execCommand('copy'); this.textContent = '✅ Copied!'; setTimeout(() => this.textContent = '📋 Copy SQL', 2000);"
            class="button"
            style="margin-top: 10px;">
            📋 Copy SQL
          </button>

          <h4>Step 3: Execute in Supabase</h4>
          <ol style="margin-left: 20px;">
            <li>Paste the SQL script into the Supabase SQL Editor</li>
            <li>Click <strong>Run</strong> button</li>
            <li>Verify success message: "Success. No rows returned"</li>
          </ol>

          <h4>Step 4: Verify Tables</h4>
          <p>Refresh this page to verify tables were created successfully.</p>
          <button
            onclick="window.location.reload();"
            class="button button-secondary">
            🔄 Refresh Status
          </button>
        </div>

        <div style="padding: 12px; background: #e7f3ff; border-left: 3px solid #0969da; border-radius: 4px; margin-top: 20px;">
          <strong>💡 What do these tables do?</strong>
          <p style="margin: 8px 0 0 0;">
            <strong>wp_registration_pairs:</strong> Maps registration pages to their thank you pages (e.g., /services/ → /services-thankyou/)<br>
            <strong>wp_user_registrations:</strong> Logs which users registered through which pages (for analytics & webhooks)
          </p>
        </div>
      <?php endif; ?>
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
      </div><!-- End Tab 1: General Settings -->

    <?php elseif ($current_tab === 'pairs'): ?>
      <!-- TAB 2: Registration Pairs -->
      <div class="tab-content">
        <?php sb_render_pairs_tab(); ?>
      </div><!-- End Tab 2: Registration Pairs -->

    <?php elseif ($current_tab === 'webhooks'): ?>
      <!-- TAB 3: Webhooks -->
      <div class="tab-content">
        <?php sb_render_webhooks_tab(); ?>
      </div><!-- End Tab 3: Webhooks -->

    <?php endif; ?>

  </div><!-- End .wrap -->
  <?php
}

// === Phase 2: Registration Pairs Tab ===
function sb_render_pairs_tab() {
  // Get pairs from wp_options
  $pairs = get_option('sb_registration_pairs', []);
  ?>
  <div style="margin-top: 20px;">
    <h2>🔗 Registration / Thank You Page Pairs</h2>
    <p>Map registration pages to their thank you pages. Each registration page will redirect users to its corresponding thank you page.</p>

    <!-- Add New Pair Button -->
    <p style="margin: 20px 0;">
      <button type="button" class="button button-primary" onclick="sbShowAddPairModal()">
        ➕ Add New Pair
      </button>
    </p>

    <?php if (empty($pairs)): ?>
      <!-- Empty State -->
      <div style="background: #f0f6fc; border: 1px solid #d1e4f5; border-radius: 6px; padding: 40px; text-align: center; margin: 20px 0;">
        <p style="font-size: 16px; color: #666; margin: 0;">
          📋 No registration pairs created yet.
        </p>
        <p style="color: #999; margin: 10px 0 0 0;">
          Click "Add New Pair" to create your first registration/thank-you page mapping.
        </p>
      </div>
    <?php else: ?>
      <!-- Pairs Table -->
      <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
        <thead>
          <tr>
            <th style="width: 35%;">Registration Page</th>
            <th style="width: 35%;">Thank You Page</th>
            <th style="width: 20%;">Created</th>
            <th style="width: 10%;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pairs as $pair): ?>
            <tr>
              <td>
                <strong><?php echo esc_html(get_the_title($pair['registration_page_id'])); ?></strong>
                <br>
                <code style="font-size: 11px; color: #666;"><?php echo esc_html($pair['registration_page_url']); ?></code>
              </td>
              <td>
                <strong><?php echo esc_html(get_the_title($pair['thankyou_page_id'])); ?></strong>
                <br>
                <code style="font-size: 11px; color: #666;"><?php echo esc_html($pair['thankyou_page_url']); ?></code>
              </td>
              <td>
                <?php echo esc_html(date('Y-m-d H:i', strtotime($pair['created_at']))); ?>
              </td>
              <td>
                <button type="button" class="button button-small" onclick="sbEditPair('<?php echo esc_js($pair['id']); ?>')">
                  ✏️ Edit
                </button>
                <button type="button" class="button button-small button-link-delete" onclick="sbDeletePair('<?php echo esc_js($pair['id']); ?>', '<?php echo esc_js(get_the_title($pair['registration_page_id'])); ?>')">
                  🗑️ Delete
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <!-- Add/Edit Pair Modal -->
    <div id="sb-pair-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
      <div style="background: #fff; margin: 50px auto; padding: 30px; width: 600px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <h2 id="sb-modal-title">Add New Pair</h2>
        <form id="sb-pair-form">
          <input type="hidden" id="sb-pair-id" name="pair_id" value="">

          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="sb-reg-page">Registration Page</label>
              </th>
              <td>
                <?php
                wp_dropdown_pages([
                  'name' => 'registration_page_id',
                  'id' => 'sb-reg-page',
                  'show_option_none' => '— Select a page —',
                  'option_none_value' => '0'
                ]);
                ?>
                <p class="description">The page where users fill the registration form</p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="sb-ty-page">Thank You Page</label>
              </th>
              <td>
                <?php
                wp_dropdown_pages([
                  'name' => 'thankyou_page_id',
                  'id' => 'sb-ty-page',
                  'show_option_none' => '— Select a page —',
                  'option_none_value' => '0'
                ]);
                ?>
                <p class="description">Where new users will be redirected after registration</p>
              </td>
            </tr>
          </table>

          <p style="margin-top: 20px;">
            <button type="button" class="button button-primary" onclick="sbSavePair()">💾 Save Pair</button>
            <button type="button" class="button" onclick="sbCloseModal()">Cancel</button>
          </p>
        </form>
      </div>
    </div>

    <script>
    function sbShowAddPairModal() {
      document.getElementById('sb-modal-title').textContent = 'Add New Pair';
      document.getElementById('sb-pair-id').value = '';
      document.getElementById('sb-reg-page').value = '0';
      document.getElementById('sb-ty-page').value = '0';
      document.getElementById('sb-pair-modal').style.display = 'block';
    }

    function sbEditPair(pairId) {
      // TODO: Load pair data and populate form
      alert('Edit functionality coming in next commit');
    }

    function sbCloseModal() {
      document.getElementById('sb-pair-modal').style.display = 'none';
    }

    function sbSavePair() {
      const formData = new FormData(document.getElementById('sb-pair-form'));
      formData.append('action', 'sb_save_pair');
      formData.append('nonce', '<?php echo wp_create_nonce('sb_pair_nonce'); ?>');

      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('✅ Pair saved successfully!');
          location.reload();
        } else {
          alert('❌ Error: ' + (data.data || 'Unknown error'));
        }
      })
      .catch(error => {
        alert('❌ Network error: ' + error.message);
      });
    }

    function sbDeletePair(pairId, pageName) {
      if (!confirm('Delete pair for "' + pageName + '"?')) {
        return;
      }

      const formData = new FormData();
      formData.append('action', 'sb_delete_pair');
      formData.append('nonce', '<?php echo wp_create_nonce('sb_pair_nonce'); ?>');
      formData.append('pair_id', pairId);

      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('✅ Pair deleted successfully!');
          location.reload();
        } else {
          alert('❌ Error: ' + (data.data || 'Unknown error'));
        }
      })
      .catch(error => {
        alert('❌ Network error: ' + error.message);
      });
    }
    </script>
  </div>
  <?php
}

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

// === Phase 2: AJAX Handlers ===

// AJAX: Save registration pair
add_action('wp_ajax_sb_save_pair', 'sb_ajax_save_pair');
function sb_ajax_save_pair() {
  // Verify nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sb_pair_nonce')) {
    wp_send_json_error('Invalid nonce');
    return;
  }

  // Check permissions
  if (!current_user_can('manage_options')) {
    wp_send_json_error('Permission denied');
    return;
  }

  // Validate input
  $registration_page_id = intval($_POST['registration_page_id'] ?? 0);
  $thankyou_page_id = intval($_POST['thankyou_page_id'] ?? 0);

  if ($registration_page_id === 0 || $thankyou_page_id === 0) {
    wp_send_json_error('Please select both pages');
    return;
  }

  // Get page URLs
  $registration_url = parse_url(get_permalink($registration_page_id), PHP_URL_PATH);
  $thankyou_url = parse_url(get_permalink($thankyou_page_id), PHP_URL_PATH);

  if (!$registration_url || !$thankyou_url) {
    wp_send_json_error('Invalid page selected');
    return;
  }

  // Get existing pairs
  $pairs = get_option('sb_registration_pairs', []);

  // Check if pair already exists (editing vs creating)
  $pair_id = sanitize_text_field($_POST['pair_id'] ?? '');

  if (empty($pair_id)) {
    // New pair - check for duplicate registration page
    foreach ($pairs as $existing) {
      if ($existing['registration_page_id'] === $registration_page_id) {
        wp_send_json_error('Registration page already has a pair');
        return;
      }
    }

    // Create new pair
    $pair_id = wp_generate_uuid4();
    $pairs[] = [
      'id' => $pair_id,
      'registration_page_id' => $registration_page_id,
      'registration_page_url' => $registration_url,
      'thankyou_page_id' => $thankyou_page_id,
      'thankyou_page_url' => $thankyou_url,
      'created_at' => current_time('mysql')
    ];
  } else {
    // Edit existing pair
    $pair_found = false;
    foreach ($pairs as &$pair) {
      if ($pair['id'] === $pair_id) {
        $pair['registration_page_id'] = $registration_page_id;
        $pair['registration_page_url'] = $registration_url;
        $pair['thankyou_page_id'] = $thankyou_page_id;
        $pair['thankyou_page_url'] = $thankyou_url;
        $pair_found = true;
        break;
      }
    }
    unset($pair);

    if (!$pair_found) {
      wp_send_json_error('Pair not found');
      return;
    }
  }

  // Save to wp_options
  update_option('sb_registration_pairs', $pairs);

  // === Phase 3: Sync to Supabase (non-blocking) ===
  // Find the saved pair data
  $saved_pair = null;
  foreach ($pairs as $pair) {
    if ($pair['id'] === $pair_id) {
      $saved_pair = $pair;
      break;
    }
  }

  if ($saved_pair) {
    // Try to sync to Supabase, but don't fail if it doesn't work
    sb_sync_pair_to_supabase($saved_pair);
    // Note: We don't check the return value - if Supabase fails, wp_options still works
  }

  wp_send_json_success(['message' => 'Pair saved successfully', 'pair_id' => $pair_id]);
}

// AJAX: Delete registration pair
add_action('wp_ajax_sb_delete_pair', 'sb_ajax_delete_pair');
function sb_ajax_delete_pair() {
  // Verify nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sb_pair_nonce')) {
    wp_send_json_error('Invalid nonce');
    return;
  }

  // Check permissions
  if (!current_user_can('manage_options')) {
    wp_send_json_error('Permission denied');
    return;
  }

  // Get pair ID
  $pair_id = sanitize_text_field($_POST['pair_id'] ?? '');

  if (empty($pair_id)) {
    wp_send_json_error('Pair ID required');
    return;
  }

  // Get existing pairs
  $pairs = get_option('sb_registration_pairs', []);

  // Find and remove pair
  $pair_found = false;
  $pairs = array_filter($pairs, function($pair) use ($pair_id, &$pair_found) {
    if ($pair['id'] === $pair_id) {
      $pair_found = true;
      return false; // Remove this pair
    }
    return true; // Keep this pair
  });

  if (!$pair_found) {
    wp_send_json_error('Pair not found');
    return;
  }

  // Reindex array
  $pairs = array_values($pairs);

  // Save to wp_options
  update_option('sb_registration_pairs', $pairs);

  // === Phase 3: Delete from Supabase (non-blocking) ===
  // Try to delete from Supabase, but don't fail if it doesn't work
  sb_delete_pair_from_supabase($pair_id);
  // Note: We don't check the return value - if Supabase fails, wp_options still works

  wp_send_json_success(['message' => 'Pair deleted successfully']);
}