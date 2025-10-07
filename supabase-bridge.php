<?php
/**
 * Plugin Name: Supabase Bridge (Auth)
 * Description: Mirrors Supabase users into WordPress and logs them in via JWT. Enhanced security with CSP, audit logging, and hardening.
 * Version: 0.3.3
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
    // Content Security Policy (strict but allows Supabase CDN)
    if (!is_admin()) {
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

// === Конфиг из окружения (ЗАПОЛНИ) ===
function sb_cfg($key, $def = null) {
  $v = getenv($key);
  return $v !== false ? $v : $def;
}
// Прим.: добавь эти переменные в wp-config.php или панель хостинга:
// SUPABASE_PROJECT_REF, SUPABASE_URL, SUPABASE_ANON_KEY

// Подключим supabase-js и прокинем public-конфиг (чтоб не хардкодить в HTML)
add_action('wp_enqueue_scripts', function () {
  // Только для страниц сайта (не админки)
  if (is_admin()) return;
  wp_enqueue_script('supabase-js', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', [], null, true);
  wp_add_inline_script('supabase-js', 'window.SUPABASE_CFG = ' . wp_json_encode([
    'url'  => sb_cfg('SUPABASE_URL', ''),       // напр. https://<project-ref>.supabase.co
    'anon' => sb_cfg('SUPABASE_ANON_KEY', ''),  // public anon key
  ]) . ';', 'before');
});

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

    // Security: Require verified email (mandatory by default)
    if (($claims['email_verified'] ?? false) !== true) {
      error_log('Supabase Bridge: Email not verified for ' . sanitize_email($claims['email']));
      throw new Exception('Email verification required');
    }

    // 3) Найдём/создадим WP-пользователя
    $email = sanitize_email($claims['email']);

    // Additional email validation
    if (!is_email($email)) {
      error_log('Supabase Bridge: Invalid email format - ' . $email);
      throw new Exception('Invalid email address');
    }

    $user  = get_user_by('email', $email);
    if (!$user) {
      // Generate strong random password
      $password = wp_generate_password(32, true, true);
      $uid = wp_create_user($email, $password, $email);

      if (is_wp_error($uid)) {
        error_log('Supabase Bridge: User creation failed - ' . $uid->get_error_message());
        throw new Exception('Unable to create user account');
      }

      // Store Supabase user ID
      update_user_meta($uid, 'supabase_user_id', sanitize_text_field($claims['sub']));

      // Set default role (subscriber)
      $user = get_user_by('id', $uid);
      if ($user) {
        $user->set_role('subscriber');
      }
    } else {
      // Update Supabase user ID for existing user
      update_user_meta($user->ID, 'supabase_user_id', sanitize_text_field($claims['sub']));
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
  ?>
  <div class="wrap">
    <h1>🚀 Supabase Bridge - Setup Instructions</h1>

    <div class="notice notice-info">
      <p><strong>Плагин активирован!</strong> Используйте готовую форму auth-form.html с Google + Facebook + Magic Link.</p>
    </div>

    <h2>📋 Шаг 1: Конфигурация wp-config.php</h2>
    <p>Добавьте эти строки в <code>wp-config.php</code> ПЕРЕД строкой <code>/* That's all, stop editing! */</code>:</p>
    <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa; overflow-x: auto;">
<code>// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=<?php echo esc_html(sb_cfg('SUPABASE_PROJECT_REF', 'your-project-ref')); ?>');
putenv('SUPABASE_URL=<?php echo esc_html(sb_cfg('SUPABASE_URL', 'https://your-project-ref.supabase.co')); ?>');
putenv('SUPABASE_ANON_KEY=<?php echo esc_html(sb_cfg('SUPABASE_ANON_KEY', 'your-anon-key')); ?>');</code></pre>

    <h2>📄 Шаг 2: Создайте WordPress страницы</h2>

    <h3>1️⃣ Страница входа (Login Page)</h3>
    <p><strong>Создайте новую страницу</strong> (например, "Вход") и вставьте код из файла <code>auth-form.html</code> в HTML виджет Elementor.</p>
    <p><strong>Что включает auth-form.html:</strong></p>
    <ul>
      <li>✅ Google OAuth</li>
      <li>✅ Facebook OAuth</li>
      <li>✅ Magic Link (Passwordless)</li>
      <li>✅ Умные редиректы (новый/существующий пользователь)</li>
      <li>✅ Автоматическая обработка OAuth callback</li>
    </ul>
    <p><em>📌 Сохраните URL этой страницы (например: <code><?php echo esc_url(home_url('/login/')); ?></code>)</em></p>
    <p><em>⚠️ auth-form.html САМ обрабатывает OAuth callback - отдельная callback страница НЕ НУЖНА!</em></p>

    <h3>2️⃣ Страница благодарности (Thank You Page)</h3>
    <p><strong>Создайте страницу с URL slug:</strong> <code>/registr/</code></p>
    <p>Эта страница может содержать любой контент - приветствие, форму регистрации профиля, или редирект дальше.</p>
    <p><em>💡 Новые пользователи (зарегистрированы < 60 сек назад) попадут сюда после авторизации.</em></p>
    <p><em>💡 Существующие пользователи вернутся на страницу откуда пришли.</em></p>

    <hr style="margin: 40px 0;">

    <h2>🔧 Шаг 3: Настройка Supabase Dashboard</h2>
    <ol>
      <li>Откройте <a href="https://app.supabase.com" target="_blank">https://app.supabase.com</a></li>
      <li>Выберите ваш проект: <code><?php echo esc_html(sb_cfg('SUPABASE_PROJECT_REF', 'your-project-ref')); ?></code></li>
      <li>Перейдите в <strong>Authentication → URL Configuration</strong></li>
      <li>Добавьте в <strong>Redirect URLs</strong> URL вашей страницы логина (например: <code><?php echo esc_url(home_url('/login/')); ?></code>)</li>
      <li>Перейдите в <strong>Authentication → Providers</strong></li>
      <li>Включите <strong>Google OAuth</strong> (настройте Client ID и Secret)</li>
      <li>Включите <strong>Facebook OAuth</strong> (настройте App ID и Secret, запросите Advanced access для email)</li>
      <li>Включите <strong>Email Auth</strong> для Magic Link (Passwordless)</li>
    </ol>

    <hr style="margin: 40px 0;">

    <h2>✅ Шаг 4: Проверка работы</h2>
    <ol>
      <li>Откройте страницу входа в браузере</li>
      <li>Протестируйте <strong>Google OAuth</strong> → должны залогиниться</li>
      <li>Протестируйте <strong>Facebook OAuth</strong> → должны залогиниться</li>
      <li>Протестируйте <strong>Magic Link</strong> → введите email → получите код → должны залогиниться</li>
      <li>Новые пользователи попадут на <code>/registr/</code>, существующие вернутся назад</li>
      <li>Проверьте админку: WordPress → Users → должен быть создан новый пользователь</li>
    </ol>

    <hr style="margin: 40px 0;">

    <h2>🐛 Диагностика проблем</h2>
    <p><strong>Если не работает:</strong></p>
    <ul>
      <li>Откройте любую страницу сайта, нажмите F12 (консоль браузера)</li>
      <li>Выполните: <code>console.log(window.SUPABASE_CFG)</code></li>
      <li>Должен вывести объект с <code>url</code> и <code>anon</code></li>
      <li>Если <code>undefined</code> — проверьте wp-config.php конфигурацию</li>
    </ul>

    <div class="notice notice-success" style="margin-top: 30px;">
      <p><strong>🎉 Готово!</strong> Если всё настроено правильно, OAuth авторизация через Google будет работать.</p>
    </div>
  </div>
  <?php
}