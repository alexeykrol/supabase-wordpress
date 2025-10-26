# Production Setup - AIOS / Cloudflare / LiteSpeed

**Version:** 0.7.0-production
**Date:** 2025-10-26
**Plugin:** WordPress Supabase Bridge

---

## 🎯 Цель документа

Настроить production окружение для безопасной работы плагина без конфликтов:
- **All-In-One Security (AIOS)** - WordPress security plugin
- **Cloudflare** - CDN, DDoS protection, Turnstile
- **LiteSpeed Cache** - WordPress caching plugin

---

## 🛡️ All-In-One Security (AIOS) - Рекомендации

### ✅ ЧТО ВКЛЮЧАТЬ (безопасно для плагина):

#### 1. User Security

**AIOS → User Security → WP Username:**
- ✅ **Change Admin Username:** Рекомендуется (если не "admin")
- ✅ **Display Name:** Изменить на что-то кроме username

**AIOS → User Security → User Enumeration:**
- ✅ **Prevent User Enumeration:** ON
  - Зачем: Скрывает список пользователей от сканеров
  - Безопасно: Не влияет на плагин

#### 2. Brute Force Protection

**AIOS → Brute Force → Login Lockdown:**
- ✅ **Enable Login Lockdown:** ON
- ✅ **Max Login Attempts:** 3-5
- ✅ **Lockout Time:** 60 minutes
- ✅ **Lockout Length:** 24 hours after 3 lockouts

**Зачем:** Защита от brute force на wp-login.php

**Безопасно для плагина:** ✅ Не влияет на frontend регистрацию

#### 3. Database Security

**AIOS → Database Security → DB Prefix:**
- ✅ **Change Database Prefix:** Рекомендуется (если не `wp_`)
  - Зачем: Усложняет SQL injection
  - Безопасно: Делается один раз при установке

**AIOS → Database Security → Backup:**
- ✅ **Automated Scheduled Backups:** ON (рекомендуется)

#### 4. File Security

**AIOS → File Security → File Permissions:**
- ✅ **Set Recommended Permissions:** Применить рекомендуемые
  - `wp-config.php`: 640 или 600
  - `.htaccess`: 644
  - Директории: 755
  - Файлы: 644

**AIOS → File Security → PHP File Editing:**
- ✅ **Disable PHP File Editing:** ON
  - Зачем: Блокирует редактирование через админку
  - Безопасно: Плагин не редактирует себя через админку

#### 5. Firewall → .htaccess Rules (ОСНОВНАЯ ЗАЩИТА)

**AIOS → Firewall → .htaccess Rules → Basic Firewall Rules:**
- ✅ **Enable Basic Firewall Protection:** ON
- ✅ **Block Access to debug.log:** ON
- ✅ **Disable Index Views:** ON
- ✅ **Disable Trace and Track:** ON

**AIOS → Firewall → .htaccess Rules → WordPress Pingback:**
- ✅ **Disable Pingback Functionality:** ON (уже видел на скрине - отлично!)

**AIOS → Firewall → .htaccess Rules → Block Access to WP Install Files:**
- ✅ **Block Access to readme.html, license.txt:** ON

**Зачем:** Стандартная защита WordPress без влияния на плагин

**Безопасно для плагина:** ✅ Только блокирует стандартные векторы атак

#### 6. Internet Bots

**AIOS → Firewall → Internet Bots → Block Fake Googlebots:**
- ✅ **Block Fake Googlebots:** ON (обычно безопасно)

**Примечание:** Если будут проблемы с регистрацией → выключить и проверить логи

---

### ❌ ЧТО НЕ ВКЛЮЧАТЬ (может сломать плагин):

#### 1. PHP Firewall ❌ НЕ ВКЛЮЧАТЬ!

**AIOS → Firewall → PHP Rules → Set up now:**
- ❌ **НЕ НАЖИМАТЬ "Set up now"!**

**Почему:**
- PHP firewall запускается **перед** WordPress
- Блокирует **admin-ajax.php** (плагин использует AJAX)
- Блокирует **wp_remote_post()** (плагин синхронизирует с Supabase)
- Может заблокировать Supabase API calls

**Последствия если включить:**
```
❌ Ошибка: "Pair not synced to Supabase"
❌ Ошибка: "Registration logging failed"
❌ HTTP 403 Forbidden на AJAX запросы
```

**Что использовать вместо:**
- ✅ .htaccess Firewall (уже включен)
- ✅ Cloudflare WAF (еще лучше)

#### 2. XML-RPC Blocking (осторожно!)

**AIOS → Firewall → PHP Rules → Completely block access to XMLRPC:**
- ⚠️ **Включать ТОЛЬКО если не используете:**
  - Jetpack
  - WP Mobile Apps
  - Pingbacks/Trackbacks

**Для плагина:** Не критично, можно включить

**Рекомендация:** Лучше включить "Disable Pingback Functionality" (.htaccess rules) вместо полной блокировки

#### 3. 6G Firewall Rules (осторожно!)

**AIOS → Firewall → 6G Firewall Rules:**
- ⚠️ **Очень агрессивные правила!**
- Может заблокировать легитимные запросы
- Рекомендуется тестировать на staging сначала

**Для плагина:** Может заблокировать Supabase API responses

**Рекомендация:** Использовать Cloudflare WAF вместо 6G

#### 4. Advanced Character String Filter

**AIOS → Firewall → Advanced settings → Advanced Character String Filter:**
- ⚠️ **Может блокировать JSON payloads!**
- Плагин отправляет JSON в Supabase

**Рекомендация:** НЕ включать или добавить исключения:
```
Exclude: /wp-admin/admin-ajax.php?action=sb_*
```

---

### 📋 AIOS Quick Checklist

**Включить (✅ безопасно):**
- ✅ User Enumeration Prevention
- ✅ Login Lockdown (Brute Force)
- ✅ .htaccess Firewall Rules
- ✅ Disable Pingback
- ✅ File Permissions
- ✅ Database Prefix (если новый сайт)

**НЕ включать (❌ сломает плагин):**
- ❌ PHP-based Firewall
- ❌ 6G Firewall (без тестирования)
- ❌ Advanced Character String Filter
- ⚠️ XML-RPC (смотря что используете)

---

## ☁️ Cloudflare - Рекомендации

### ✅ Основные настройки (Security)

#### 1. SSL/TLS

**Cloudflare → SSL/TLS → Overview:**
- ✅ **Encryption mode:** Full (strict)
  - Зачем: Шифрование между Cloudflare и сервером
  - Требует: Valid SSL на сервере (Let's Encrypt)

**Cloudflare → SSL/TLS → Edge Certificates:**
- ✅ **Always Use HTTPS:** ON
- ✅ **HTTP Strict Transport Security (HSTS):** ON
  - Max Age: 12 months
  - Include subdomains: ON (если все поддомены HTTPS)
  - Preload: ON

#### 2. Security Level

**Cloudflare → Security → Settings:**
- ✅ **Security Level:** Medium (или High если много атак)
  - Low: Пропускает больше ботов
  - Medium: Баланс (рекомендуется)
  - High: Агрессивная проверка (может блокировать VPN)

#### 3. Bot Fight Mode

**Cloudflare → Security → Bots:**
- ✅ **Bot Fight Mode:** ON (Free plan)
- ✅ **Super Bot Fight Mode:** ON (если Pro plan)

**Зачем:** Автоматически блокирует ботов

**Безопасно для плагина:** ✅ Не влияет на легитимных пользователей

#### 4. Challenge Passage

**Cloudflare → Security → Settings:**
- ✅ **Challenge Passage:** 30 minutes
  - Зачем: Пользователь не получает капчу повторно 30 минут
  - Улучшает UX

---

### 🔥 Cloudflare Firewall (WAF)

#### 1. Managed Rules (если доступно)

**Cloudflare → Security → WAF → Managed rules:**
- ✅ **Cloudflare Managed Ruleset:** ON
- ✅ **Cloudflare OWASP Core Ruleset:** ON
  - Защита от: SQL injection, XSS, RCE, LFI

**Для плагина:** Безопасно, защита дублирует WordPress валидацию (defense in depth!)

#### 2. Rate Limiting Rules

**Cloudflare → Security → WAF → Rate limiting rules:**

**Правило 1: Лимит на регистрацию**
```
Rule name: Registration Rate Limit
When incoming requests match:
  - Field: URI Path
  - Operator: contains
  - Value: /wp-admin/admin-ajax.php
  AND
  - Field: URI Query
  - Operator: contains
  - Value: action=sb_create_user

Then:
  - Rate: 5 requests per 5 minutes
  - Characteristics: IP Address
  - Action: Block
  - Duration: 10 minutes
```

**Правило 2: Лимит на login**
```
Rule name: Login Rate Limit
When incoming requests match:
  - Field: URI Path
  - Operator: equals
  - Value: /wp-login.php

Then:
  - Rate: 5 requests per 5 minutes
  - Characteristics: IP Address
  - Action: Challenge (Managed)
  - Duration: 30 minutes
```

**Зачем:** Защита от brute force и DoS

**Важно:** Если legitimate users жалуются → увеличить лимит до 10 per 5 min

#### 3. Custom Firewall Rules (опционально)

**Пример: Блокировка известных плохих User-Agents**
```
Rule name: Block Bad Bots
When incoming requests match:
  - Field: User Agent
  - Operator: contains
  - Value: (regex) (sqlmap|nikto|masscan|nmap|zgrab)

Then:
  - Action: Block
```

---

### 🤖 Cloudflare Turnstile (замена reCAPTCHA)

#### Шаг 1: Создать Site Key

**Cloudflare → Turnstile → Add site:**
1. **Domain:** yourdomain.com
2. **Widget mode:** Managed (рекомендуется)
3. **Pre-clearance:** Disabled (для начала)
4. Скопировать **Site Key** и **Secret Key**

#### Шаг 2: Интеграция в форму регистрации

**В auth-form.html добавить:**

```html
<!-- В <head> секцию -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<!-- В форму регистрации, перед кнопкой Submit -->
<div class="cf-turnstile"
     data-sitekey="YOUR_SITE_KEY"
     data-callback="onTurnstileSuccess"></div>

<script>
function onTurnstileSuccess(token) {
  // Token готов, можно отправлять форму
  console.log('Turnstile passed:', token);
}
</script>
```

#### Шаг 3: Проверка на сервере (WordPress)

**В supabase-bridge.php добавить функцию:**

```php
// Verify Cloudflare Turnstile token
function sb_verify_turnstile($token) {
  if (empty($token)) {
    return false;
  }

  $secret_key = 'YOUR_SECRET_KEY'; // Хранить в wp-config.php!

  $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
    'body' => [
      'secret' => $secret_key,
      'response' => $token,
    ],
  ]);

  if (is_wp_error($response)) {
    error_log('Turnstile verification failed: ' . $response->get_error_message());
    return false;
  }

  $body = json_decode(wp_remote_retrieve_body($response), true);
  return isset($body['success']) && $body['success'] === true;
}

// В функции создания пользователя (перед sb_create_supabase_user):
$turnstile_token = isset($_POST['cf-turnstile-response']) ? sanitize_text_field($_POST['cf-turnstile-response']) : '';

if (!sb_verify_turnstile($turnstile_token)) {
  wp_send_json_error(['message' => 'Bot verification failed. Please try again.']);
  return;
}
```

**Важно:** Secret Key хранить в `wp-config.php`, не в коде!

```php
// В wp-config.php добавить:
define('CLOUDFLARE_TURNSTILE_SECRET', 'your-secret-key-here');

// В коде использовать:
$secret_key = defined('CLOUDFLARE_TURNSTILE_SECRET') ? CLOUDFLARE_TURNSTILE_SECRET : '';
```

---

### 🌍 Cloudflare - Дополнительные настройки

#### Geo-blocking (если нужно)

**Cloudflare → Security → WAF → Tools → IP Access Rules:**

**Пример: Разрешить только Россию + СНГ**
```
Country: Russia → Allow
Country: Ukraine → Allow
Country: Belarus → Allow
Country: Kazakhstan → Allow
[All others] → Challenge (или Block если очень строго)
```

**Осторожно:** Может заблокировать VPN пользователей!

#### Page Rules (кеширование)

**Cloudflare → Rules → Page Rules:**

**Правило 1: НЕ кешировать админку**
```
URL: *yourdomain.com/wp-admin/*
Settings:
  - Cache Level: Bypass
```

**Правило 2: НЕ кешировать AJAX**
```
URL: *yourdomain.com/wp-admin/admin-ajax.php*
Settings:
  - Cache Level: Bypass
```

**Правило 3: НЕ кешировать страницы с формой (если динамические)**
```
URL: *yourdomain.com/registration-page/*
Settings:
  - Cache Level: Bypass (или Standard если статичная)
```

---

## ⚡ LiteSpeed Cache - Рекомендации

### ❌ ЧТО ИСКЛЮЧИТЬ ИЗ КЕША (критично для плагина):

#### 1. Exclude URIs

**LiteSpeed Cache → Cache → Excludes → Do Not Cache URIs:**

Добавить:
```
/wp-admin/admin-ajax.php
/wp-login.php
/wp-cron.php
```

**Зачем:** Плагин использует AJAX для синхронизации с Supabase

#### 2. Exclude Query Strings

**LiteSpeed Cache → Cache → Excludes → Do Not Cache Query Strings:**

Добавить:
```
action=sb_create_user
action=sb_handle_auth
action=sb_add_pair
action=sb_delete_pair
thank_you
```

**Зачем:** Динамические параметры не должны кешироваться

#### 3. Exclude Cookies

**LiteSpeed Cache → Cache → Excludes → Do Not Cache Cookies:**

Добавить (если плагин использует cookies):
```
wordpress_logged_in_
wp-settings-
comment_author_
```

**Примечание:** Обычно уже есть по умолчанию, проверить!

#### 4. Exclude User Agents

**LiteSpeed Cache → Cache → Excludes → Do Not Cache User Agents:**

Обычно не требуется, оставить пустым или стандартные:
```
Mobile
Android
iPhone
```

---

### ✅ Рекомендуемые настройки LiteSpeed

#### 1. Cache Settings

**LiteSpeed Cache → Cache → Cache:**
- ✅ **Enable Cache:** ON
- ✅ **Cache Logged-in Users:** OFF (важно!)
  - Зачем: Зарегистрированные пользователи видят динамический контент

**LiteSpeed Cache → Cache → TTL:**
- ✅ **Default Public Cache TTL:** 604800 (1 week) - для статики
- ✅ **Default Private Cache TTL:** 1800 (30 min) - если включен private cache
- ✅ **Default Front Page TTL:** 604800

#### 2. Purge Settings

**LiteSpeed Cache → Cache → Purge:**
- ✅ **Purge All On Upgrade:** ON
- ✅ **Auto Purge Rules For Publish/Update:** ON

#### 3. ESI (Edge Side Includes) - осторожно!

**LiteSpeed Cache → Cache → ESI:**
- ⚠️ **Enable ESI:** OFF (для начала)

**Почему:** ESI может конфликтовать с динамическими формами

**Когда включать:** Только если разбираетесь и тестируете на staging

#### 4. Object Cache

**LiteSpeed Cache → Cache → Object:**
- ✅ **Object Cache:** ON (если есть Redis/Memcached)
- ✅ **Method:** Redis (рекомендуется)

**Зачем:** Ускоряет WordPress, не влияет на плагин

#### 5. Browser Cache

**LiteSpeed Cache → Cache → Browser:**
- ✅ **Browser Cache:** ON
- ✅ **Browser Cache TTL:** 31557600 (1 year) - для статики

---

### 🧪 Тестирование LiteSpeed после настройки

#### Тест 1: Регистрация работает
1. Зайти на страницу с формой регистрации
2. Зарегистрировать пользователя
3. Проверить Supabase - должен создаться пользователь

**Ожидается:** ✅ Регистрация успешна

**Если ошибка:** Проверить Excludes (URIs, Query Strings)

#### Тест 2: Пары синхронизируются
1. WordPress Admin → Supabase Bridge → Registration Pairs
2. Создать новый pair
3. Проверить Supabase - должна быть запись

**Ожидается:** ✅ Sync успешен

**Если ошибка:** Проверить что `/wp-admin/admin-ajax.php` в Excludes

#### Тест 3: Кеш не ломает редиректы
1. Зарегистрировать пользователя на `/test/`
2. Проверить редирект на `/test-ty/` (page-specific)
3. НЕ на global `/thank-you/`

**Ожидается:** ✅ Редирект на правильную страницу

**Если ошибка:** Очистить кеш: LiteSpeed Cache → Toolbox → Purge → Purge All

---

## 🔧 Troubleshooting - Решение проблем

### Проблема 1: "Pair not synced to Supabase"

**Возможные причины:**
1. ❌ AIOS PHP Firewall блокирует `admin-ajax.php`
2. ❌ LiteSpeed кеширует AJAX запросы
3. ❌ Cloudflare Rate Limiting слишком агрессивный

**Решение:**
1. Проверить AIOS Firewall Logs: AIOS → Firewall → Firewall Log
2. Добавить `/wp-admin/admin-ajax.php` в LiteSpeed Excludes
3. Увеличить Cloudflare Rate Limit до 10 per 5 min

**Проверить логи:**
```bash
docker compose logs wordpress | grep 'Supabase Bridge'
```

---

### Проблема 2: "Registration failed - HTTP 403"

**Возможные причины:**
1. ❌ Cloudflare заблокировал как бота
2. ❌ AIOS заблокировал IP (Login Lockdown)
3. ❌ LiteSpeed firewall (если включен mod_security)

**Решение:**
1. Проверить Cloudflare Security Events: Cloudflare → Security → Events
2. Проверить AIOS Lockout List: AIOS → Brute Force → Lockout List → Unlock IP
3. Временно снизить Cloudflare Security Level до Low для теста

---

### Проблема 3: "Turnstile widget не показывается"

**Возможные причины:**
1. ❌ Скрипт заблокирован AdBlock
2. ❌ Content Security Policy (CSP) блокирует
3. ❌ JavaScript error на странице

**Решение:**
1. Отключить AdBlock для теста
2. Добавить в CSP (если используется):
   ```
   script-src 'self' https://challenges.cloudflare.com;
   frame-src https://challenges.cloudflare.com;
   ```
3. Проверить Console в браузере (F12) на ошибки JS

---

### Проблема 4: "Redirect не работает после регистрации"

**Возможные причины:**
1. ❌ LiteSpeed кеширует `window.SUPABASE_CFG.registrationPairs`
2. ❌ JavaScript кеш браузера
3. ❌ Cloudflare кеширует HTML страницы

**Решение:**
1. LiteSpeed → Purge All
2. Hard refresh в браузере (Ctrl+Shift+R)
3. Cloudflare → Caching → Configuration → Purge Everything
4. Добавить `?thank_you=/custom/` в URL для override

---

### Проблема 5: "Слишком много капч Cloudflare"

**Возможные причины:**
1. ⚠️ Security Level = High (слишком агрессивный)
2. ⚠️ Challenge Passage = 5 minutes (слишком короткий)
3. ⚠️ Пользователь на VPN

**Решение:**
1. Cloudflare → Security Level: Medium (вместо High)
2. Cloudflare → Challenge Passage: 30 minutes
3. Добавить IP в Whitelist (если trusted VPN): Cloudflare → Security → WAF → Tools → IP Access Rules

---

## 📋 Production Deployment Checklist

### Pre-deployment:

- [ ] **Backup WordPress** (база данных + файлы)
- [ ] **Backup Supabase** (экспорт schema)
- [ ] **Тестирование на staging** (если есть)

### AIOS Setup:

- [ ] ✅ Enable .htaccess Firewall Rules
- [ ] ✅ Enable Brute Force Protection
- [ ] ✅ Disable User Enumeration
- [ ] ✅ Set File Permissions
- [ ] ❌ НЕ включать PHP Firewall
- [ ] ❌ НЕ включать 6G Firewall (без тестирования)

### Cloudflare Setup:

- [ ] ✅ SSL/TLS = Full (strict)
- [ ] ✅ Always Use HTTPS = ON
- [ ] ✅ Bot Fight Mode = ON
- [ ] ✅ Security Level = Medium
- [ ] ✅ Turnstile интегрирован в форму (опционально)
- [ ] ✅ Rate Limiting Rules созданы
- [ ] ✅ Page Rules: Bypass cache для /wp-admin/*, /wp-admin/admin-ajax.php*

### LiteSpeed Cache Setup:

- [ ] ✅ Exclude URIs: `/wp-admin/admin-ajax.php`
- [ ] ✅ Exclude Query Strings: `action=sb_*`, `thank_you`
- [ ] ✅ Cache Logged-in Users = OFF
- [ ] ✅ Browser Cache = ON
- [ ] ✅ Object Cache = ON (если Redis)

### Testing:

- [ ] ✅ Тест создания pair → sync в Supabase
- [ ] ✅ Тест регистрации пользователя
- [ ] ✅ Тест page-specific редиректа
- [ ] ✅ Тест логирования в `wp_user_registrations`
- [ ] ✅ Проверить логи WordPress (без ошибок)
- [ ] ✅ Проверить Cloudflare Security Events (нет блокировок легитимных)

### Monitoring:

- [ ] ✅ Настроить email alerts в Cloudflare (при атаках)
- [ ] ✅ Настроить uptime monitoring (UptimeRobot или Pingdom)
- [ ] ✅ Проверять логи WordPress раз в неделю

---

## 📞 Support

**Если что-то не работает:**

1. **Проверить логи:**
   - WordPress: `wp-content/debug.log`
   - Cloudflare: Security → Events
   - AIOS: Firewall Log

2. **Временно отключить для диагностики:**
   - AIOS Firewall → Basic Rules = OFF
   - LiteSpeed Cache → Disable (для теста)
   - Cloudflare → Development Mode = ON (5 минут)

3. **Rollback план:**
   - Восстановить backup WordPress
   - Вернуть Cloudflare к default настройкам
   - Отключить AIOS

---

**Last Updated:** 2025-10-26
**Version:** 0.7.0-production
**Status:** ✅ Production Ready

🚀 **Максимальная безопасность + Zero конфликтов!**
