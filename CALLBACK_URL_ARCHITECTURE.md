# Callback URL Architecture — Supabase Bridge

**Дата:** 2025-12-19
**Версия плагина:** 0.9.8+
**Автор:** Документация по архитектуре callback URL

---

## 📌 Краткое резюме

В плагине Supabase Bridge есть **два URL**:

| URL | Тип | Где используется | Можно менять? |
|-----|-----|------------------|---------------|
| **Форма (Login)** | Гибкий | Любая страница с `[supabase_auth_form]` | ✅ Да, любой URL |
| **Callback** | Критичный | Страница с `[supabase_auth_callback]` | ⚠️ Да, но требует изменения конфига |

**ВАЖНО:** Callback URL захардкожен в конфигурации (с v0.9.9 — через константу), потому что:
1. OAuth провайдеры редиректят сюда после авторизации
2. Magic Link в email ведет сюда
3. Здесь происходит обработка токена и создание WordPress сессии

---

## 🏗️ Архитектура двух страниц

### Страница 1: Форма входа (ГИБКИЙ URL)

```
URL: https://site.com/ЛЮБОЙ-URL/
Шорткод: [supabase_auth_form]
```

**Что здесь:**
- Кнопки "Login with Google", "Login with Facebook"
- Поле для email (Magic Link)
- Сохранение referrer в localStorage (для return-to-origin)

**Можно создать сколько угодно таких страниц:**
- `/login/`
- `/auth/`
- `/register/`
- `/premium-login/`

Плагин не ограничивает количество или URL.

---

### Страница 2: Callback Handler (КРИТИЧНЫЙ URL)

```
URL: https://site.com/test-no-elem-2/ (настраиваемый)
Шорткод: [supabase_auth_callback]
Константа: SB_CALLBACK_PATH = '/test-no-elem-2/'
```

**Что здесь:**
- Обработка OAuth hash (`#access_token=...`)
- Отправка токена на WordPress REST API `/callback`
- Создание WordPress сессии
- Редирект пользователя обратно (return-to-origin)

**КРИТИЧНО:**
- Должна быть РОВНО ОДНА такая страница
- URL должен совпадать с `SB_CALLBACK_PATH` константой
- Должен быть добавлен в Supabase → Redirect URLs

---

## 🔄 Поток аутентификации (полный цикл)

### Google OAuth пример:

```
┌─────────────────────────────────────────────────────────┐
│ 1. Пользователь на странице /premium-course/            │
│    Кликает кнопку "Login"                               │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│ 2. Редирект на /login/ (форма входа)                    │
│    Сохраняет document.referrer в localStorage:          │
│    login_return_url = '/premium-course/'                │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│ 3. Пользователь кликает "Login with Google"            │
│    auth-form.html, строка 896:                          │
│    redirectTo: 'https://site.com/test-no-elem-2/'       │
│                                                         │
│    Supabase получает инструкцию:                        │
│    "После OAuth вернуть на /test-no-elem-2/"            │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│ 4. Google OAuth (окно авторизации)                     │
│    Пользователь разрешает доступ                        │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│ 5. Google → Supabase                                    │
│    https://PROJECT.supabase.co/auth/v1/callback         │
│                                                         │
│    Supabase обрабатывает OAuth код                      │
│    Создает access_token                                 │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│ 6. Supabase → Callback страница                         │
│    https://site.com/test-no-elem-2/#access_token=...    │
│                                                         │
│    JavaScript читает hash:                              │
│    - access_token                                       │
│    - refresh_token                                      │
│    - expires_in                                         │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│ 7. Callback → WordPress REST API                        │
│    POST /wp-json/supabase-bridge/v1/callback            │
│    Body: { access_token, refresh_token }                │
│                                                         │
│    WordPress:                                           │
│    ✅ Верифицирует JWT через JWKS                       │
│    ✅ Создает/находит пользователя                      │
│    ✅ Создает WordPress сессию (wp_set_auth_cookie)     │
│    ✅ Возвращает redirect URL                           │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│ 8. Callback → Редирект обратно                          │
│    Читает localStorage: login_return_url                │
│    = '/premium-course/'                                 │
│                                                         │
│    window.location.href = '/premium-course/'            │
│    Пользователь возвращается откуда пришел              │
│    ✅ Залогинен в WordPress                             │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 Где callback URL захардкожен

### До версии 0.9.9 (старая архитектура):

**Проблема:** Callback URL был захардкожен в 4 местах в `auth-form.html`:

```javascript
// 1. Magic Link (строка 865)
emailRedirectTo: 'https://alexeykrol.com/test-no-elem-2/'

// 2. Google OAuth (строка 896)
redirectTo: window.location.origin + '/test-no-elem-2/'

// 3. Facebook OAuth (строка 922)
redirectTo: window.location.origin + '/test-no-elem-2/',

// 4. Resend Magic Link (строка 988)
emailRedirectTo: 'https://alexeykrol.com/test-no-elem-2/'
```

**Недостатки:**
- ❌ Строки 865 и 988 — хардкод домена `alexeykrol.com`
- ❌ Не работает на других доменах без правки кода
- ❌ При изменении callback URL — нужно менять в 4 местах
- ❌ Легко забыть обновить где-то

---

### С версии 0.9.9 (новая архитектура):

**Решение:** Конфигурационная константа + placeholder injection

#### 1. Константа в `supabase-bridge.php` (строка 20):

```php
// === Configuration Constants ===
// Callback page path - used for OAuth redirects and Magic Link
// IMPORTANT: This must match the WordPress page where [supabase_auth_callback] shortcode is placed
define('SB_CALLBACK_PATH', '/test-no-elem-2/');
```

#### 2. Injection в shortcode (строка 871):

```php
// Inject callback path constant into HTML
$callback_url = home_url(SB_CALLBACK_PATH);
$sb_auth_form_content = str_replace('{{CALLBACK_URL}}', $callback_url, $sb_auth_form_content);
```

#### 3. Placeholder в `auth-form.html`:

```javascript
// Все 4 места теперь используют placeholder
emailRedirectTo: '{{CALLBACK_URL}}'
redirectTo: '{{CALLBACK_URL}}'
```

**Преимущества:**
- ✅ Одно место конфигурации (константа `SB_CALLBACK_PATH`)
- ✅ Автоматическая подстановка домена (`home_url()`)
- ✅ Работает на любом домене без правок
- ✅ Легко изменить (одна строка в конфиге)
- ✅ Отображается в WordPress Admin для справки

---

## ⚙️ Как изменить callback URL

### Шаг 1: Изменить константу

**Файл:** `supabase-bridge.php`
**Строка:** ~20

```php
// Было:
define('SB_CALLBACK_PATH', '/test-no-elem-2/');

// Стало (например):
define('SB_CALLBACK_PATH', '/auth-callback/');
```

### Шаг 2: Создать WordPress страницу

1. WordPress Admin → Pages → Add New
2. Title: "Auth Callback" (любое)
3. Permalink: `/auth-callback/` (должен совпадать с константой!)
4. Content: Shortcode block → `[supabase_auth_callback]`
5. Publish

### Шаг 3: Обновить Supabase Redirect URLs

1. Supabase Dashboard → Authentication → URL Configuration
2. Redirect URLs → Добавить:
   ```
   https://your-site.com/auth-callback/
   ```
3. Удалить старый URL `/test-no-elem-2/` (если не нужен)
4. Save

### Шаг 4: Удалить старую callback страницу (опционально)

WordPress Admin → Pages → Найти старую страницу `/test-no-elem-2/` → Trash

---

## 📋 Чеклист настройки (для нового сайта)

### ✅ Callback URL Setup

- [ ] **1. Выбрать path для callback**
  - Рекомендуется: `/auth-callback/`, `/login-callback/`, `/supabase-callback/`
  - Избегать: слишком короткие (`/cb/`), слишком длинные

- [ ] **2. Обновить константу в коде**
  - Файл: `supabase-bridge.php`, строка ~20
  - `define('SB_CALLBACK_PATH', '/auth-callback/');`

- [ ] **3. Создать WordPress страницу**
  - URL должен точно совпадать с `SB_CALLBACK_PATH`
  - Добавить шорткод `[supabase_auth_callback]`

- [ ] **4. Добавить в Supabase Redirect URLs**
  - Supabase Dashboard → Authentication → URL Configuration
  - Добавить: `https://your-site.com/auth-callback/`

- [ ] **5. Создать страницу входа (опционально)**
  - Любой URL (например, `/login/`)
  - Добавить шорткод `[supabase_auth_form]`

- [ ] **6. Тест**
  - Открыть страницу входа в incognito
  - Попробовать Google OAuth
  - Попробовать Magic Link
  - Проверить что редирект работает

---

## 🔍 Отладка проблем

### Проблема: OAuth не работает

**Симптом:** После клика на "Login with Google" — ошибка или зависание

**Проверить:**
1. ✅ Callback страница существует? (WordPress Admin → Pages)
2. ✅ URL страницы = `SB_CALLBACK_PATH`? (проверь permalink)
3. ✅ Shortcode `[supabase_auth_callback]` добавлен?
4. ✅ Callback URL добавлен в Supabase Redirect URLs?

**Debug команда:**
```bash
# Проверить что константа определена
grep -n "define('SB_CALLBACK_PATH'" supabase-bridge.php

# Проверить что placeholder заменяется
grep -n "{{CALLBACK_URL}}" auth-form.html
# Должно быть 4 строки (865, 896, 922, 988)
```

---

### Проблема: Magic Link не приходит на email

**Симптом:** Email не приходит или приходит с неправильным URL

**Проверить:**
1. ✅ Supabase Email Templates настроены?
2. ✅ Email provider работает? (Supabase → Settings → Auth → Email)
3. ✅ Callback URL в константе правильный?

**Test:**
```javascript
// Открыть браузер console на странице формы
console.log(document.querySelector('script').textContent.match(/emailRedirectTo.*$/m));
// Должно показать: emailRedirectTo: 'https://your-site.com/auth-callback/'
```

---

### Проблема: После логина редирект на 404

**Симптом:** Успешный логин, но попадаешь на страницу 404

**Причина:** Callback страница не существует или неправильный permalink

**Решение:**
1. WordPress Admin → Pages → Найти callback страницу
2. Проверить permalink = `SB_CALLBACK_PATH`
3. Если не совпадает — обновить
4. Settings → Permalinks → Save Changes (flush permalinks)

---

## 📊 Сравнение подходов

### Вариант 1: Hardcode (старый подход)

```javascript
// auth-form.html
emailRedirectTo: 'https://alexeykrol.com/test-no-elem-2/'
redirectTo: window.location.origin + '/test-no-elem-2/'
```

**Плюсы:**
- ✅ Простой подход
- ✅ Нет дополнительных зависимостей

**Минусы:**
- ❌ Хардкод домена в 2 местах
- ❌ Не работает на других доменах
- ❌ Нужно править 4 строки при изменении URL
- ❌ Легко забыть обновить где-то

---

### Вариант 2: Константа + Injection (новый подход)

```php
// supabase-bridge.php
define('SB_CALLBACK_PATH', '/test-no-elem-2/');
$callback_url = home_url(SB_CALLBACK_PATH);
$sb_auth_form_content = str_replace('{{CALLBACK_URL}}', $callback_url, $sb_auth_form_content);
```

```javascript
// auth-form.html
emailRedirectTo: '{{CALLBACK_URL}}'
redirectTo: '{{CALLBACK_URL}}'
```

**Плюсы:**
- ✅ Одно место конфигурации
- ✅ Автоматический домен (`home_url()`)
- ✅ Работает на любом домене
- ✅ Легко менять (одна строка)
- ✅ Видно в админке WordPress

**Минусы:**
- ⚠️ Немного сложнее (требует PHP injection)
- ⚠️ Placeholder может запутать при чтении HTML

**Вывод:** Новый подход лучше для production и масштабирования.

---

## 🎓 Лучшие практики

### 1. Выбор callback URL path

**Хорошие примеры:**
- ✅ `/auth-callback/`
- ✅ `/login-callback/`
- ✅ `/supabase-callback/`

**Плохие примеры:**
- ❌ `/callback/` (слишком общее, может конфликтовать)
- ❌ `/test-no-elem-2/` (непонятное название)
- ❌ `/cb/` (слишком короткое, непонятно что это)

### 2. Документирование

**ОБЯЗАТЕЛЬНО документируй:**
- ✅ Callback URL в README проекта
- ✅ Callback URL в комментарии константы
- ✅ Callback URL в Supabase dashboard notes

**Пример комментария:**
```php
// === Configuration Constants ===
// Callback page path - used for OAuth redirects and Magic Link
// IMPORTANT: This must match the WordPress page where [supabase_auth_callback] shortcode is placed
// Current page: https://alexeykrol.com/test-no-elem-2/
define('SB_CALLBACK_PATH', '/test-no-elem-2/');
```

### 3. Версионирование изменений

При изменении callback URL:
1. Создать новую страницу СНАЧАЛА
2. Добавить новый URL в Supabase СНАЧАЛА
3. Обновить константу
4. Тестировать
5. Только потом удалить старую страницу
6. Удалить старый URL из Supabase

**Почему:** Zero-downtime deployment — во время обновления оба URL работают.

---

## 🔐 Безопасность

### ВАЖНО: Callback URL не содержит секретов!

Callback URL — **публичная** страница:
- ✅ Может быть индексирована поисковиками (хотя это бессмысленно)
- ✅ Доступна без авторизации
- ✅ Не содержит access_token (он в hash, не виден серверу)

**Но:**
- ⚠️ НЕ создавай очевидные URL типа `/admin-callback/`
- ⚠️ НЕ используй callback для других целей (только auth!)
- ⚠️ НЕ добавляй на callback страницу контент (только shortcode!)

### Обфускация (необязательно, но можно)

Если параноишь:
```php
// Вместо понятного URL
define('SB_CALLBACK_PATH', '/auth-callback/');

// Можно использовать случайную строку
define('SB_CALLBACK_PATH', '/cb-9a7f3e2d/');
```

Не дает реальной безопасности (security through obscurity), но снижает вероятность скрипт-киддиз автоматических атак.

---

## 📚 Дополнительная информация

### Связанные файлы

| Файл | Что там |
|------|---------|
| `supabase-bridge.php` | Константа `SB_CALLBACK_PATH` (строка ~20) |
| | Injection в shortcode (строка ~871) |
| | Admin UI с отображением callback URL (строка ~1810) |
| `auth-form.html` | Placeholder `{{CALLBACK_URL}}` (строки 865, 896, 922, 988) |
| `test-no-elem-2-wordpress-paste.html` | Callback handler JavaScript (обработка hash) |
| `PRODUCTION_SETUP.md` | Инструкции по настройке для production |
| `QUICK_SETUP_CHECKLIST.md` | Быстрый чеклист установки |

### Связанные концепции

- **OAuth Flow:** OAuth 2.0 Authorization Code Flow
- **JWT Verification:** RS256 asymmetric cryptography via JWKS
- **Return-to-Origin:** localStorage-based redirect tracking
- **Registration Pairs:** Page-specific thank you redirects

---

## 🎯 Итоги

### Что мы исправили (v0.9.9)

1. ✅ **Убрали hardcode домена** из auth-form.html (строки 865, 988)
2. ✅ **Создали конфигурационную константу** `SB_CALLBACK_PATH`
3. ✅ **Реализовали injection через PHP** — placeholder `{{CALLBACK_URL}}`
4. ✅ **Добавили отображение в админке** — Step 3 в WordPress Admin
5. ✅ **Документировали архитектуру** — этот файл

### Как это работает сейчас

```
1. Константа:    SB_CALLBACK_PATH = '/test-no-elem-2/'
                           ↓
2. PHP Injection: {{CALLBACK_URL}} → https://site.com/test-no-elem-2/
                           ↓
3. JavaScript:    Supabase.auth.signInWithOAuth({ redirectTo: 'https://site.com/test-no-elem-2/' })
                           ↓
4. OAuth Flow:    Google → Supabase → Callback → WordPress → Login Success
```

### Что дальше

- ✅ **Протестировать** на production (alexeykrol.com)
- ✅ **Обновить CHANGELOG.md** с версией 0.9.9
- ✅ **Создать release** с новой архитектурой
- ⏳ **Опционально:** Добавить UI для изменения callback URL через админку (будущая фича)

---

**Версия документа:** 1.0
**Дата:** 2025-12-19
**Автор:** Claude Code + Alexey Krol
