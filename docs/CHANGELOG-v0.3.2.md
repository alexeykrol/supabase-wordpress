# 🔐 Changelog v0.3.2 - Security Hotfix

**Дата релиза:** 2025-10-05
**Тип:** CRITICAL Security Hotfix
**Статус:** Production Ready ✅

---

## 🚨 КРИТИЧЕСКИЕ SECURITY ИСПРАВЛЕНИЯ

### 1. ✅ Fixed Origin/Referer bypass vulnerability

**Проблема (HIGH):**
- CSRF проверка в `/wp-json/supabase-auth/callback` использовала `strpos()` для проверки Origin/Referer
- Атакующий мог обойти защиту используя домен типа `https://site.com.attacker.io`
- `strpos()` находил подстроку `site.com` в `site.com.attacker.io` → пропускал запрос ❌

**Файл:** `supabase-bridge.php:58-74`

**Было (УЯЗВИМО):**
```php
if ($origin && strpos($origin, parse_url($site_url, PHP_URL_HOST)) === false) {
  return new \WP_Error('csrf', 'Invalid origin', ['status'=>403]);
}
```

**Стало (ЗАЩИЩЕНО):**
```php
// Точное сравнение хостов
$allowed_host = parse_url(home_url(), PHP_URL_HOST);
$request_host = $origin ? parse_url($origin, PHP_URL_HOST) : null;

if (!$request_host || $request_host !== $allowed_host) {
  return new \WP_Error('csrf', 'Invalid origin', ['status'=>403]);
}
```

**Результат:**
- ✅ Строгое сравнение хостов через `===`
- ✅ Обязательное наличие Origin или Referer
- ✅ Невозможность bypass через поддомены

---

### 2. ✅ Added CSRF protection for logout endpoint

**Проблема (MEDIUM):**
- Endpoint `/wp-json/supabase-auth/logout` проверял только `is_user_logged_in()`
- Злоумышленник мог создать скрытую форму на стороннем сайте
- При клике пользователя → принудительный logout ❌

**Файл:** `supabase-bridge.php:139-163`

**Было (УЯЗВИМО):**
```php
register_rest_route('supabase-auth', '/logout', [
  'methods'  => 'POST',
  'permission_callback' => function(){ return is_user_logged_in(); },
  'callback' => function(){
    wp_destroy_current_session();
    wp_clear_auth_cookie();
    return ['ok'=>true];
  },
]);
```

**Стало (ЗАЩИЩЕНО):**
```php
register_rest_route('supabase-auth', '/logout', [
  'methods'  => 'POST',
  'permission_callback' => function(){ return is_user_logged_in(); },
  'callback' => 'sb_handle_logout', // Отдельная функция с CSRF проверкой
]);

function sb_handle_logout(\WP_REST_Request $req) {
  // Та же строгая Origin validation как в callback
  $allowed_host = parse_url(home_url(), PHP_URL_HOST);
  $request_host = $origin ? parse_url($origin, PHP_URL_HOST) : null;

  if (!$request_host || $request_host !== $allowed_host) {
    return new \WP_Error('csrf', 'Invalid origin', ['status'=>403]);
  }

  wp_destroy_current_session();
  wp_clear_auth_cookie();
  return ['ok' => true];
}
```

**Результат:**
- ✅ CSRF защита для logout
- ✅ Невозможность принудительного logout со сторонних сайтов
- ✅ Консистентная Origin validation для всех endpoints

---

## 📊 Статистика изменений

- **Файлов изменено:** 1 (`supabase-bridge.php`)
- **Security исправлений:** 2 (HIGH + MEDIUM)
- **Строк кода изменено:** ~40
- **Версия:** 0.3.1 → 0.3.2

---

## ⚠️ BREAKING CHANGES

**НЕТ BREAKING CHANGES**

Исправления обратно совместимы. Легитимные запросы продолжают работать.

---

## 🔄 Миграция с v0.3.1

### Для деплоя:

1. **Обнови `supabase-bridge.php` на сервере**
2. **Проверь что всё работает:**
   - Login через Google ✅
   - Login через Facebook ✅
   - Login через Magic Link ✅
   - Logout работает ✅

**Изменений в auth-form.html НЕ ТРЕБУЕТСЯ!**

---

## 🙏 Credits

**Обнаружено:** Второй ИИ-анализ (благодарим за peer review!)

**Исправления:**
- Origin/Referer bypass → strict host comparison
- Logout CSRF → added Origin validation

---

## 🧪 Тестирование

### Проверь после обновления:

```bash
# 1. Login работает (callback endpoint)
curl -X POST https://yoursite.com/wp-json/supabase-auth/callback \
  -H "Origin: https://yoursite.com" \
  -H "Content-Type: application/json" \
  -d '{"access_token":"..."}'
# Ожидаем: 200 OK (если токен валидный)

# 2. Login блокируется с неправильным Origin
curl -X POST https://yoursite.com/wp-json/supabase-auth/callback \
  -H "Origin: https://yoursite.com.attacker.io" \
  -H "Content-Type: application/json" \
  -d '{"access_token":"..."}'
# Ожидаем: 403 Forbidden (CSRF error)

# 3. Logout работает с правильным Origin
curl -X POST https://yoursite.com/wp-json/supabase-auth/logout \
  -H "Origin: https://yoursite.com" \
  -H "Cookie: wordpress_logged_in_..."
# Ожидаем: 200 OK

# 4. Logout блокируется без Origin
curl -X POST https://yoursite.com/wp-json/supabase-auth/logout \
  -H "Cookie: wordpress_logged_in_..."
# Ожидаем: 403 Forbidden (CSRF error)
```

---

## 📝 Рекомендации

### После обновления:

1. ✅ **Обнови на продакшене** - критические уязвимости
2. ✅ **Протестируй все OAuth методы** - убедись что работают
3. ✅ **Проверь logout** - должен работать только с корректным Origin
4. ✅ **Мониторь логи** - смотри 403 ошибки (возможные атаки)

### Если обнаружишь проблемы:

- Проверь что auth-form.html отправляет запросы с того же домена
- Проверь что нет proxy/CDN которые меняют Origin headers
- Открой issue на GitHub с подробностями

---

**Версия:** 0.3.2
**Дата:** 2025-10-05
**Автор:** Alexey Krol + Claude Code
**Security Review:** Second AI peer review
**Готовность:** PRODUCTION READY 🔐
