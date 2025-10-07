# 🔍 Шпаргалка по отладке Supabase Bridge

**Дата создания:** 2025-10-01
**Обновлено:** 2025-10-06
**Версия:** 0.3.2 - Security Hotfix

---

## 🔍 Стратегия поиска ошибок (по порядку):

### 1️⃣ Проверка конфигурации (ПЕРВЫМ ДЕЛОМ)

**Открой любую страницу своего сайта → F12 → Console:**
```javascript
console.log(window.SUPABASE_CFG);
```

**Ожидаемый результат:**
```javascript
{
  url: "https://your-project-ref.supabase.co",
  anon: "eyJhbGci..."
}
```

**Если `undefined`:**
- ❌ wp-config.php не настроен или плагин не активирован
- **Решение:** Проверь wp-config.php, убедись что плагин активен

---

### 2️⃣ Проверка REST API endpoint

**Открой в браузере:**
```
https://yoursite.com/wp-json/supabase-auth/callback
```

**Ожидаемый результат (ошибка - это нормально!):**
```json
{"code":"rest_no_route",...}
```

**Если другая ошибка:**
- ❌ Плагин не активирован или ошибка в PHP
- **Решение:** Проверь WordPress Admin → Plugins, включи PHP error log

---

### 3️⃣ Тест OAuth flow (клик по кнопке)

**Что проверяем:**

a) **Нажал кнопку "Войти через Google":**
- Должен открыться popup/редирект на Google OAuth
- **Если нет:** F12 → Console → смотрим JavaScript ошибки

b) **После авторизации в Google:**
- URL должен быть: `yoursite.com/supabase-callback/#access_token=eyJ...`
- **Если редирект не туда:** проверь Supabase Dashboard → Redirect URLs

c) **На странице callback:**
- Должна быть надпись "Авторизация..."
- Потом редирект на `/registr/`
- **Если ошибка:** F12 → Console → Network → смотрим запрос к `/wp-json/supabase-auth/callback`

---

### 4️⃣ Логи ошибок WordPress

**Включи WordPress debug mode** (в wp-config.php):
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Логи будут в:**
```
/wp-content/debug.log
```

---

### 5️⃣ Частые ошибки и решения

| Проблема | Где смотреть | Что делать |
|----------|--------------|------------|
| Кнопка не работает | F12 → Console | Проверь `window.SUPABASE_CFG` |
| Редирект не на callback | Browser URL | Проверь код кнопки, redirectTo URL |
| "Missing access_token" | F12 → Network | Проверь что токен в URL hash (#access_token=...) |
| "JWKS fetch failed" | wp-content/debug.log | Проверь что сервер может обращаться к Supabase |
| "Bad iss" | wp-content/debug.log | Проверь SUPABASE_PROJECT_REF в wp-config.php |
| Не логинит в WP | wp-content/debug.log | Проверь ошибки wp_create_user |

---

## 🧪 Локальное тестирование (возможно, но сложно)

**Что нужно для локального теста:**

1. **Локальный WordPress:**
   - MAMP/XAMPP/Local by Flywheel
   - Установить плагин
   - Настроить wp-config.php

2. **Проблема с OAuth:**
   - Google OAuth требует HTTPS
   - Localhost не подойдёт
   - Нужен ngrok/cloudflared для туннеля

3. **Настройка Supabase:**
   - Добавить ngrok URL в Redirect URLs
   - Например: `https://abc123.ngrok.io/supabase-callback/`

**Вердикт:** Локально очень геморно, проще сразу тестировать на production сайте

---

## 📋 Пошаговый план тестирования на production

**Шаг 1: Установка**
```
1. Upload ZIP
2. Activate
3. Проверь: window.SUPABASE_CFG в консоли
```

**Шаг 2: Настройка wp-config.php**
```
1. Добавь 3 строки putenv()
2. Перезагрузи страницу
3. Проверь: window.SUPABASE_CFG снова (должны появиться данные)
```

**Шаг 3: Создание страниц**
```
1. Создай 3 страницы
2. Вставь код из Admin UI
3. Проверь: кнопка отображается
```

**Шаг 4: Первый тест**
```
1. F12 → Console → Network (открыть вкладку)
2. Клик по кнопке
3. Смотрим что происходит:
   - Запрос к Supabase?
   - Редирект на Google?
   - Редирект обратно?
```

**Шаг 5: Отладка**
```
Если ошибка - смотрим:
1. F12 → Console (JavaScript ошибки)
2. F12 → Network (HTTP запросы)
3. wp-content/debug.log (PHP ошибки)
```

---

## 🎯 Готовые команды для отладки

**В консоли браузера (F12):**
```javascript
// Проверка конфига
console.log(window.SUPABASE_CFG);

// Проверка Supabase JS загружен
console.log(window.supabase);

// Ручной тест создания клиента
const {createClient} = window.supabase;
const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);
console.log(sb);
```

**На сервере (SSH/cPanel Terminal):**
```bash
# Смотрим последние ошибки WordPress
tail -f /path/to/wp-content/debug.log

# Проверяем что vendor/ существует
ls -la /path/to/wp-content/plugins/supabase-bridge/vendor/
```

---

## ✅ Что скорее всего пойдёт не так (топ-3):

1. **wp-config.php не обновился** → window.SUPABASE_CFG = undefined
2. **URL callback страницы не `/supabase-callback/`** → редирект 404
3. **SUPABASE_PROJECT_REF неправильный** → "Bad iss" ошибка

---

## 🆘 Что показать для диагностики:

Если что-то сломалось - покажи:
- ✅ Скриншот F12 → Console
- ✅ Скриншот F12 → Network (если есть запросы)
- ✅ Текст ошибки из debug.log (если включил)
- ✅ URL на котором произошла ошибка

---

## 📊 Диагностическая таблица

### Симптом: Кнопка не реагирует на клик

| Проверка | Команда | Ожидаемый результат |
|----------|---------|---------------------|
| Supabase JS загружен? | `console.log(window.supabase)` | Объект, не undefined |
| Конфиг доступен? | `console.log(window.SUPABASE_CFG)` | {url, anon} |
| Есть JS ошибки? | F12 → Console | Нет красных ошибок |

### Симптом: Редирект на неправильную страницу

| Проверка | Что смотреть | Как исправить |
|----------|--------------|---------------|
| URL callback страницы | Должен быть `/supabase-callback/` или любой другой | Исправь slug страницы |
| Код кнопки | `redirectTo: 'https://yoursite.com/your-callback-page/'` | Обнови код на странице |
| Supabase Dashboard | Redirect URLs содержит URL твоей callback страницы | Добавь в Supabase |

### Симптом: "Missing access_token"

| Проверка | Что делать |
|----------|------------|
| Посмотри URL после редиректа | Должен быть `#access_token=eyJ...` в URL |
| Если нет токена в URL | Проверь Supabase Dashboard → Authentication → Providers → Google OAuth включен |
| Если токен есть, но ошибка | Проверь код callback страницы (правильно парсит hash?) |

### Симптом: "JWKS fetch failed"

| Проверка | Что делать |
|----------|------------|
| Сервер может обращаться к Supabase? | Проверь firewall/proxy сервера |
| SUPABASE_PROJECT_REF правильный? | Должен быть твой project ref из Supabase Dashboard |
| URL правильный? | https://your-project-ref.supabase.co |

### Симптом: "Bad iss" или "Expired"

| Проверка | Что делать |
|----------|------------|
| SUPABASE_PROJECT_REF | Проверь в wp-config.php - должен совпадать с твоим project ref |
| Время сервера | Убедись что время на сервере правильное (для exp проверки) |

### Симптом: Не создаётся WordPress пользователь

| Проверка | Что делать |
|----------|------------|
| Посмотри debug.log | Ищи строку "WP create failed" |
| Email уже существует? | Проверь WordPress → Users |
| Права на создание пользователей? | Проверь что плагин может создавать пользователей |

---

## 🔧 Полезные ссылки для отладки

- **WordPress REST API Test:** `https://yoursite.com/wp-json/`
- **Supabase Auth JWKS:** `https://your-project-ref.supabase.co/auth/v1/.well-known/jwks.json`
- **Supabase Dashboard:** `https://app.supabase.com/project/your-project-ref`
- **WordPress Debug Log:** `wp-content/debug.log`

---

## 🔷 Facebook OAuth - Частые проблемы

### Симптом: "Error getting user email from external provider"

**Причина:** Facebook не возвращает email (не запрошен в scopes или нет Advanced access)

| Проверка | Решение |
|----------|---------|
| В коде есть `scopes: 'email public_profile'`? | Добавь в auth-form.html в Facebook OAuth handler |
| Facebook Developer Console → App Review → Permissions | Включи **Advanced access** для `email` и `public_profile` |
| Удалил старое разрешение? | Facebook.com → Settings → Apps → удали своё приложение → попробуй снова |

**Быстрая проверка:**
```javascript
// В консоли браузера после клика Facebook:
// URL должен содержать:
#access_token=eyJ...
// НЕ должно быть:
#error=server_error&error_description=Error+getting+user+email
```

### Симптом: "App not active"

**Причина:** Facebook приложение в Development Mode

| Решение | Что делать |
|---------|------------|
| **Для тестирования:** | Добавь себя в Roles → Developers (Facebook Developer Console) |
| **Для продакшена:** | Переведи App Mode в **Live** (требует Privacy Policy + App Icon) |

### Симптом: Facebook запрашивает только "Name and profile picture"

**Причина:** email permission не включен в Advanced access

| Решение | Где настроить |
|---------|---------------|
| Facebook Developer Console | App Review → Permissions and Features → email → включить Advanced access |
| Supabase Dashboard | Authentication → Providers → Facebook → проверь что scopes пустые (или `email,public_profile`) |

---

## 📝 Чеклист перед тестированием

### WordPress:
- [ ] ZIP установлен и плагин активирован
- [ ] wp-config.php содержит 3 строки putenv()
- [ ] Страница `/registr/` существует (для редиректа)
- [ ] Страница входа создана с auth-form.html
- [ ] WP_DEBUG включен для логирования
- [ ] F12 Console открыт для мониторинга

### Supabase Dashboard:
- [ ] Google OAuth включен и настроен
- [ ] Facebook OAuth включен и настроен
- [ ] Redirect URLs содержит URL страницы логина
- [ ] Email templates настроены (для Magic Link)

### Facebook Developer Console:
- [ ] App создано
- [ ] Valid OAuth Redirect URIs настроены
- [ ] **Advanced access** для `email` включен
- [ ] **Advanced access** для `public_profile` включен
- [ ] App Mode = Development (для теста) или Live (для продакшена)

---

*Последнее обновление: 2025-10-06*
*Версия: 0.3.2 - Security Hotfix*
