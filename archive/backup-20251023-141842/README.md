# Supabase Bridge (Auth) for WordPress

![Version](https://img.shields.io/badge/version-0.3.3-blue.svg)
![PHP](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-21759B.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Security](https://img.shields.io/badge/security-hardened-brightgreen.svg)
![Dependencies](https://img.shields.io/badge/dependencies-0%20vulnerabilities-success.svg)

## 📌 Описание

**Supabase Bridge** — это минимальный плагин для WordPress, который позволяет использовать [Supabase Auth](https://supabase.com/docs/guides/auth) как единую систему аутентификации и авторизации на сайте WordPress.

С помощью плагина:

- Пользователи могут логиниться и регистрироваться через **Supabase** (email/password, Google, Apple, Facebook и другие провайдеры, которые поддерживает Supabase).
- После успешной аутентификации в Supabase создаётся или обновляется зеркальная учётная запись в WordPress.
- Пользователь автоматически залогинен в WordPress (сессия и куки выставлены).
- Логин/регистрация доступны как через кастомные кнопки на сайте, так и через отдельные callback-страницы.
- Плагин обеспечивает синхронизацию между Supabase и WordPress без сторонних «рандомных» плагинов и полной зависимости от WP User API.

---

## 🎯 Зачем это нужно

WordPress из коробки умеет работать только со своей системой `wp_users`.  
Supabase же предоставляет мощную и удобную систему аутентификации:
- Поддержка десятков социальных провайдеров (Google, GitHub, Apple и т.п.).
- Готовая база пользователей с токенами (JWT).
- Механизмы безопасности: подтверждение email, RLS (Row Level Security), refresh tokens.
- Централизованная система, которую можно использовать не только для WP, но и для всех кастомных сервисов (React-приложения, нативные клиенты, боты и т.д.).

**Supabase Bridge** решает задачу:  
> «Мне нужна единая учётка для пользователей, удобная регистрация, но при этом WordPress должен узнавать этих людей и пускать их в плагины, админку и доступ к контенту.»

---

## ⚙️ Как работает

1. Пользователь кликает «Войти через Google» (или любой другой провайдер).  
   → Запускается метод `supabase.auth.signInWithOAuth()` с `redirectTo` на страницу в WordPress.

2. После авторизации Supabase редиректит пользователя на callback-страницу WP.  
   На этой странице через `supabase-js` мы получаем `access_token` (JWT).

3. Браузер отправляет этот `access_token` в REST-эндпоинт `/wp-json/supabase-auth/callback`.

4. Плагин:
   - Проверяет подпись JWT по **JWKS Supabase** (`.well-known/jwks.json`).
   - Валидирует клеймы (`iss`, `aud`, `exp`, `email_verified`).
   - Находит пользователя в `wp_users` по email. Если нет — создаёт.
   - Сохраняет `supabase_user_id` (`auth.uid()`) в `usermeta`.
   - Устанавливает сессию WordPress (`wp_set_auth_cookie`).

5. Пользователь теперь одновременно:
   - Аутентифицирован в Supabase (может работать с кастомными сервисами, RLS и Edge Functions).
   - Аутентифицирован в WordPress (может работать с WP-плагинами, ролями и контентом).

---

## 🔑 Основные возможности

- ✅ **3 метода авторизации протестированы:**
  - 🔵 **Google OAuth** - работает
  - 🔷 **Facebook OAuth** - работает (с Advanced access для email)
  - ✉️ **Magic Link (Passwordless)** - Email + 6-digit код
- ✅ **Умные редиректы** - 3 режима настройки (стандартный, парный, гибкий)
- ✅ Автоопределение referrer для существующих пользователей
- ✅ Поддержка любых других провайдеров Supabase (Apple, GitHub и др.).
- ✅ Прозрачная синхронизация пользователя между Supabase и WP.
- ✅ JWT проверяется сервером (никакого доверия фронту).
- ✅ Безопасное хранение конфигурации (через переменные окружения, а не хардкод).
- ✅ Возможность расширять: роли, кастомные поля, интеграция с WP-плагинами.
- ✅ **Готовая форма авторизации:** `auth-form.html` с Google + Facebook + Magic Link

---

## 📂 Структура плагина

```
supabase-bridge/
├── supabase-bridge.php              # Основной код плагина
├── composer.json                    # Зависимости (firebase/php-jwt)
├── vendor/                          # Автозагруженные библиотеки
├── auth-form.html                   # Готовая форма: Google + Facebook + Magic Link
├── wp-config-supabase-example.php   # Пример конфигурации
├── LICENSE                          # MIT License
├── README.md                        # Эта документация
└── docs/                            # 📚 Документация и гайды
    ├── QUICKSTART.md                # Быстрый старт
    ├── DEPLOYMENT.md                # Инструкция по деплою
    ├── INSTALL.md                   # Подробная установка
    ├── DEBUG.md                     # Шпаргалка по отладке
    ├── AUTH-FORM-REDIRECT-GUIDE.md  # Гайд по редиректам
    ├── STATUS.md                    # Статус проекта
    ├── CHANGELOG-v0.3.2.md          # История изменений
    └── ...                          # Другие вспомогательные файлы
```

---

## 📚 Документация

### Быстрый старт
- **[QUICKSTART.md](docs/QUICKSTART.md)** - Краткое руководство (5 минут)
- **[DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Установка ZIP через WordPress Admin

### Подробные гайды
- **[INSTALL.md](docs/INSTALL.md)** - Полная инструкция по установке
- **[AUTH-FORM-REDIRECT-GUIDE.md](docs/AUTH-FORM-REDIRECT-GUIDE.md)** - Настройка умных редиректов
- **[DEBUG.md](docs/DEBUG.md)** - Отладка и частые проблемы

### Информация о проекте
- **[STATUS.md](docs/STATUS.md)** - Текущий статус и roadmap
- **[CHANGELOG-v0.3.2.md](docs/CHANGELOG-v0.3.2.md)** - История изменений

---

## 🔧 Установка

1. Склонируй папку `supabase-bridge` в `wp-content/plugins/`.
2. Внутри папки выполни:
   ```bash
   composer install

(создаст vendor/ с библиотекой firebase/php-jwt).
3. Активируй плагин в админке WordPress.

⸻

## 🛠 Настройка окружения

### Шаг 1: Найди свои Supabase данные

В панели Supabase (https://app.supabase.com):
1. Открой свой проект
2. Иди в **Settings** → **API**
3. Найди:
   - **Project URL** (например: `https://abcdefghijk.supabase.co`)
   - **Project API keys** → **anon public** (длинный токен начинающийся с `eyJhbGci...`)

### Шаг 2: Добавь в wp-config.php

Открой файл `wp-config.php` в корне WordPress и добавь эти строки **ПЕРЕД** строкой `/* That's all, stop editing! Happy publishing. */`:

```php
// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=your-project-ref-here');  // Замени на свой project ref
putenv('SUPABASE_URL=https://your-project-ref-here.supabase.co');  // Полный URL проекта
putenv('SUPABASE_ANON_KEY=your-supabase-anon-key-here');
```

📄 **Пример:** Смотри файл `wp-config-supabase-example.php` для полного примера конфигурации.

⚠️ **ВАЖНО:**
- Используй ТОЛЬКО **anon public** ключ (безопасен для фронтенда)
- **НИКОГДА** не используй **service_role** ключ (это админский ключ!)

⸻

🖥 Использование

1. Callback-страница

Создай в WordPress страницу, например /supabase-callback/.
Вставь HTML-блок (или шорткод):

<div id="sb-status">Finishing sign-in…</div>
<script>
(async () => {
  const { createClient } = window.supabase;
  const cfg = window.SUPABASE_CFG;
  const sb = createClient(cfg.url, cfg.anon);

  const { data: { session }, error } = await sb.auth.getSession();
  if (error || !session) { document.getElementById("sb-status").textContent = "No session"; return; }

  const r = await fetch("/wp-json/supabase-auth/callback", {
    method:"POST", credentials:"include",
    headers:{ "Content-Type":"application/json" },
    body: JSON.stringify({ access_token: session.access_token, user: session.user })
  });

  if (r.ok) {
    // Редирект на страницу благодарности или личный кабинет
    window.location.href = "/account/"; // или "/thank-you/", "/registr/" и т.д.
  } else {
    document.getElementById("sb-status").textContent = "Server verify failed";
  }
})();
</script>

💡 **Совет:** Вы можете редиректить на любую страницу после успешной авторизации:
- `/account/` — личный кабинет
- `/thank-you/` — страница благодарности
- `/registr/` — страница регистрации с инструкциями
- Или даже внешний URL: `https://example.com/welcome/`

2. Кнопка входа

На любой странице (Elementor/HTML-блок/шорткод):

<button id="login-google">Войти через Google</button>
<script>
document.getElementById('login-google').onclick = async () => {
  const { createClient } = window.supabase;
  const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);
  await sb.auth.signInWithOAuth({
    provider: 'google',
    options: { redirectTo: 'https://questtales.com/supabase-callback/' }
  });
};
</script>

3. Выход

const { createClient } = window.supabase;
const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);
await sb.auth.signOut();  // Supabase logout
await fetch('/wp-json/supabase-auth/logout', { method:'POST', credentials:'include' }); // WP logout
location.href = '/';


---

## 🔒 Безопасность

### Реализованные меры защиты (v0.3.3)

#### Аутентификация и авторизация
- ✅ **JWT верификация** - RS256 с JWKS (публичные ключи)
- ✅ **Проверка всех JWT claims** - iss, aud, exp, email_verified
- ✅ **Кеширование JWKS** - 1 час, SSL verification
- ✅ **Обязательная верификация email** - предотвращает поддельные аккаунты

#### Защита от атак
- ✅ **CSRF Protection** - строгая валидация Origin/Referer (v0.3.2 hotfix)
- ✅ **Rate Limiting** - 10 попыток за 60 секунд по IP
- ✅ **Open Redirect Protection** - валидация redirect URLs (same-origin)
- ✅ **Brute Force Protection** - автоматическая блокировка по IP

#### HTTP Security Headers
- ✅ **X-Frame-Options: SAMEORIGIN** - защита от clickjacking
- ✅ **X-Content-Type-Options: nosniff** - защита от MIME sniffing
- ✅ **X-XSS-Protection** - дополнительная XSS защита
- ✅ **Content-Security-Policy** - строгая CSP для фронтенда
- ✅ **Referrer-Policy** - контроль утечки информации

#### Обработка ошибок и аудит
- ✅ **Общие сообщения об ошибках** - не раскрывают внутренние детали
- ✅ **Детальное логирование** - все события аутентификации
- ✅ **Audit trail** - успешные входы, выходы, ошибки с IP
- ✅ **Error handling** - перехват всех исключений

#### Дополнительные улучшения
- ✅ **Сильные пароли** - 32 символа, высокая сложность
- ✅ **Валидация email** - sanitize_email() + is_email()
- ✅ **Санитизация данных** - все пользовательские данные очищаются
- ✅ **Безопасная конфигурация** - переменные окружения, .gitignore

### Зависимости
- ✅ **firebase/php-jwt:** ^6.11.1 (последняя стабильная версия)
- ✅ **Composer audit:** Пройден без уязвимостей (2025-10-07)
- ✅ **Нет транзитивных уязвимостей**

### Документация по безопасности
Полная информация по безопасности: **[SECURITY.md](SECURITY.md)**

**Важно:**
- ❌ **Никогда** не используй service_role key в фронтенде
- ✅ Используй **только anon public key** (безопасен для клиента)
- ✅ Все JWT проверяются **сервером** через JWKS
- ✅ Операции с данными защищены **RLS-политиками Supabase**

---

## 🚀 Что реализовано (v0.3.0)

- ✅ **Google OAuth** - протестировано и работает
- ✅ **Facebook OAuth** - протестировано и работает (с Advanced access)
- ✅ **Magic Link (Passwordless)** - Email + 6-digit код
- ✅ **Умные редиректы** - новый vs существующий пользователь
- ✅ **3 режима thank-you pages** - стандартный, парный, гибкий
- ✅ **Готовая форма** - auth-form.html с полной документацией

## 🚧 Дальнейшее развитие

- ⏳ Поддержка маппинга Supabase roles → WP roles
- ⏳ Дополнительные провайдеры (Apple, GitHub, Twitter и т.д.)
- ⏳ Интеграция с WP-плагинами для доступа к закрытому контенту
- ⏳ Edge Functions для бизнес-логики с service_role

⸻

## ✅ Итог

Supabase Bridge превращает Supabase в полноценный Identity Provider (IdP) для WordPress:
- ✅ Пользователи могут регистрироваться через соцсети или email
- ✅ WordPress остаётся центральной системой для плагинов, ролей и контента
- ✅ Кастомные сервисы тоже используют ту же базу (Supabase)
- ✅ **Протестировано в продакшене:** https://questtales.com

**Удобно, безопасно, масштабируемо.** 🚀

---

## 📦 Installation from GitHub

### Prerequisites
- PHP >=8.0
- WordPress 5.0+
- Composer installed
- Supabase account

### Step 1: Clone or Download
```bash
git clone https://github.com/yourusername/supabase-bridge.git
cd supabase-bridge
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Upload to WordPress
Upload the entire `supabase-bridge` folder to `/wp-content/plugins/`

### Step 4: Activate Plugin
1. Go to WordPress Admin → Plugins
2. Find "Supabase Bridge (Auth)"
3. Click "Activate"
4. You'll be redirected to setup instructions

### Step 5: Configure
1. Add credentials to `wp-config.php` (see `wp-config-supabase-example.php`)
2. Create login page with `auth-form.html` code
3. Configure Supabase Dashboard redirect URLs
4. Test authentication

**Full documentation:** See [QUICKSTART.md](docs/QUICKSTART.md)

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 📜 Changelog

### v0.3.3 (2025-10-07) 🛡️ Enhanced Security & Hardening
- ✅ **HTTP Security Headers** - Added CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy
- ✅ **Enhanced Error Handling** - Generic user messages, detailed server logs, no information leakage
- ✅ **Audit Logging** - Complete audit trail for authentication events (success/failure/logout with IP)
- ✅ **Improved JWT Validation** - Better error messages, SSL verification for JWKS, status code checks
- ✅ **Stronger Passwords** - 32 characters with high complexity
- ✅ **Enhanced Email Validation** - Added is_email() check in addition to sanitize_email()
- ✅ **Default User Roles** - Automatically assign 'subscriber' role to new users
- ✅ **Rate Limit Clearing** - Clear rate limit transient on successful authentication
- ✅ **Composer Metadata** - Added MIT license and author information
- ✅ **SECURITY.md** - Comprehensive security documentation
- ✅ **Dependencies Updated** - Composer audit passed (0 vulnerabilities)

### v0.3.2 (2025-10-05) 🚨 Security Hotfix
- ✅ **CRITICAL:** Fixed Origin/Referer bypass vulnerability (strict host comparison)
- ✅ **MEDIUM:** Added CSRF protection for logout endpoint
- ⚠️ **Update immediately if using v0.3.1**

### v0.3.1 (2025-10-05) 🔐 Security Update
- ✅ **CSRF Protection** - валидация Origin/Referer headers
- ✅ **JWT aud validation** - проверка audience claim
- ✅ **Mandatory email verification** - строгая проверка email_verified
- ✅ **Open redirect protection** - валидация redirect URLs (same-origin)
- ✅ **JWKS caching** - кеширование публичных ключей (1 час)
- ✅ **Rate limiting** - 10 попыток/60 сек по IP
- ✅ **PHP >=8.0** - обновлено требование версии
- ✅ **.gitignore** - защита wp-config файлов
- ✅ **Hardcoded domains removed** - универсальность для любого домена
- ❌ Удалены устаревшие файлы: button.html, htmlblock.html

### v0.3.0 (2025-10-05)
- ✅ Добавлен Facebook OAuth с Advanced access
- ✅ Добавлен Magic Link (Passwordless) с 6-digit кодом
- ✅ Реализованы умные редиректы (новый vs существующий)
- ✅ 3 режима настройки thank-you pages
- ✅ Создана готовая форма auth-form.html
- ✅ Добавлена документация AUTH-FORM-REDIRECT-GUIDE.md
- ✅ Протестировано на продакшене (questtales.com)

### v0.2.0 (2025-10-01)
- ✅ Google OAuth реализован
- ✅ WordPress ZIP создан
- ✅ Admin UI с инструкциями

### v0.1.0 (начальная версия)
- ✅ Базовая JWT верификация
- ✅ REST API endpoint
- ✅ Синхронизация Supabase ↔ WordPress

---

**Версия:** 0.3.3
**Статус:** Production Ready 🛡️ Hardened
**Дата:** 2025-10-07
**Security Audit:** Passed (0 vulnerabilities)