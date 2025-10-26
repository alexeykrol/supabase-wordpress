# Quick Setup Checklist - 5 минут

**Краткая шпаргалка для production deployment**

---

## ✅ AIOS - Что включить

```
✅ User Enumeration Prevention
✅ Login Lockdown (Max 5 attempts)
✅ .htaccess Firewall Rules
✅ Disable Pingback
✅ File Permissions (рекомендуемые)
```

## ❌ AIOS - Что НЕ включать

```
❌ PHP Firewall (Set up now) - сломает AJAX!
❌ 6G Firewall - слишком агрессивный
❌ Advanced Character String Filter - блокирует JSON
```

---

## ☁️ Cloudflare - Минимальные настройки

```
✅ SSL/TLS = Full (strict)
✅ Always Use HTTPS = ON
✅ Bot Fight Mode = ON
✅ Security Level = Medium
✅ Challenge Passage = 30 minutes

Page Rules:
  - /wp-admin/* → Cache: Bypass
  - /wp-admin/admin-ajax.php* → Cache: Bypass
```

### Cloudflare Rate Limiting (опционально)

```
Rule: Registration Limit
Path contains: /wp-admin/admin-ajax.php
Query contains: action=sb_create_user
Limit: 5 per 5 minutes
Action: Block for 10 minutes
```

---

## ⚡ LiteSpeed Cache - Исключения

### Cache → Excludes:

**Do Not Cache URIs:**
```
/wp-admin/admin-ajax.php
/wp-login.php
```

**Do Not Cache Query Strings:**
```
action=sb_create_user
action=sb_handle_auth
action=sb_add_pair
action=sb_delete_pair
thank_you
```

**Cache Logged-in Users:**
```
❌ OFF (важно!)
```

---

## 🧪 Тест после настройки

```bash
# 1. Создать pair в WordPress
WordPress Admin → Supabase Bridge → Add pair
Проверить: Sync в Supabase ✅

# 2. Тест регистрации
Зарегистрировать пользователя
Проверить: Redirect на правильную thank you page ✅
Проверить: Запись в wp_user_registrations ✅

# 3. Проверить логи
docker compose logs wordpress | grep 'Supabase Bridge'
Ожидается: Нет ошибок ✅
```

---

## 🚨 Если что-то не работает

### "Pair not synced"
1. Проверить AIOS → Firewall Log
2. Убедиться PHP Firewall = OFF
3. LiteSpeed Excludes: `/wp-admin/admin-ajax.php` ✅

### "HTTP 403"
1. Cloudflare → Security Events → посмотреть что заблокировано
2. Временно: Security Level = Low (для теста)
3. AIOS → Lockout List → разблокировать IP

### "Слишком много капч"
1. Cloudflare → Security Level = Medium (вместо High)
2. Challenge Passage = 30 minutes

---

**Полная документация:** PRODUCTION_SETUP.md
