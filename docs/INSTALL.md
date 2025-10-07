# 🚀 Инструкция по установке Supabase Bridge

**Проект:** yoursite.com
**Версия:** 0.3.2 - Security Hotfix
**Статус:** ✅ PRODUCTION READY 🔐

---

## ✅ Зависимости уже установлены!

**Хорошая новость:** `composer install` уже выполнен, директория `vendor/` создана!

```
✅ vendor/autoload.php - создан
✅ firebase/php-jwt v6.11.1 - установлен
✅ Все зависимости готовы
```

**Вам НЕ НУЖНО выполнять `composer install`** - просто загружайте проект на сервер как есть.

---

## 📦 Шаг 1: Загрузка на сервер yoursite.com

### Что загружать:

Скопируй **всю папку** `supabase-bridge/` на сервер в `wp-content/plugins/`:

```
wp-content/plugins/supabase-bridge/
├── supabase-bridge.php       ✅ Основной файл плагина
├── composer.json              ✅ Конфигурация
├── composer.lock              ✅ Lock-файл
├── composer.phar              ✅ Composer (опционально)
└── vendor/                    ✅✅✅ ОБЯЗАТЕЛЬНО!!!
    ├── autoload.php           ← Критически важно!
    ├── composer/
    └── firebase/
        └── php-jwt/
```

### ⚠️ КРИТИЧНО:

**Директория `vendor/` ОБЯЗАТЕЛЬНА!**

Без неё плагин выдаст ошибку:
```
Fatal error: require(): Failed opening 'vendor/autoload.php'
```

### Способы загрузки:

#### Вариант 1: FTP/SFTP (FileZilla, Cyberduck)
1. Подключись к серверу yoursite.com
2. Перейди в `wp-content/plugins/`
3. Загрузи всю папку `supabase-bridge/` (включая `vendor/`)
4. Убедись что все файлы загружены (особенно `vendor/autoload.php`)

#### Вариант 2: cPanel File Manager
1. Войди в cPanel yoursite.com
2. **File Manager** → `public_html/wp-content/plugins/`
3. **Upload** → загрузи ZIP архив проекта
4. **Extract** архив
5. Проверь что `vendor/` директория есть

#### Вариант 3: SSH (если доступен)
```bash
# На локальной машине (сначала создай архив)
cd /Users/alexeykrolmini/Downloads/Code/
tar -czf supabase-bridge.tar.gz supabase-bridge/

# Загрузи на сервер
scp supabase-bridge.tar.gz user@yoursite.com:/home/user/

# На сервере
ssh user@yoursite.com
cd /path/to/wp-content/plugins/
tar -xzf ~/supabase-bridge.tar.gz
```

---

## 🛠 Шаг 2: Настройка wp-config.php

### Вариант A: Полная замена (если wp-config.php еще не настроен)

Скопируй файл `wp-config_questtales.php` как `wp-config.php` в корень WordPress.

**Важно:** Этот файл уже содержит:
- ✅ Настройки базы данных для yoursite.com
- ✅ Секретные ключи WordPress
- ✅ Конфигурацию Supabase (строки 92-97)

---

### Вариант Б: Добавление в существующий wp-config.php (рекомендуется)

Если у тебя уже есть рабочий `wp-config.php` на сервере:

1. Открой файл `wp-config.php` в редакторе
2. Найди строку:
   ```php
   /* That's all, stop editing! Happy publishing. */
   ```
3. **ПЕРЕД** этой строкой добавь:

```php
// Supabase Bridge Configuration
// Project: yoursite.com
// Supabase Project ID: your-project-ref
putenv('SUPABASE_PROJECT_REF=your-project-ref');
putenv('SUPABASE_URL=https://your-project-ref.supabase.co');
putenv('SUPABASE_ANON_KEY=your-supabase-anon-key-here');
```

**Результат должен выглядеть так:**

```php
/* Add any custom values between this line and the "stop editing" line. */

// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=your-project-ref');
putenv('SUPABASE_URL=https://your-project-ref.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGci...');

define( 'FS_METHOD', 'direct' );
/* That's all, stop editing! Happy publishing. */
```

---

## ✅ Шаг 3: Активация плагина

1. Войди в админку WordPress:
   ```
   https://yoursite.com/wp-admin/
   ```

2. Перейди в **Plugins** → **Installed Plugins**

3. Найди **"Supabase Bridge (Auth)"** в списке

4. Нажми **"Activate"**

### Ожидаемый результат:

✅ **Плагин активируется БЕЗ ошибок**

Если видишь ошибку:
```
Fatal error: require(): Failed opening 'vendor/autoload.php'
```
→ Директория `vendor/` не загружена на сервер! Вернись к Шагу 1.

---

## 🧪 Шаг 4: Проверка работы плагина

### Тест 1: Проверка конфигурации

1. Открой любую страницу yoursite.com (НЕ в админке)
2. Нажми **F12** (Developer Console)
3. В консоли выполни:

```javascript
console.log(window.SUPABASE_CFG);
```

**Должно вывести:**
```javascript
{
  url: "https://your-project-ref.supabase.co",
  anon: "eyJhbGci..."
}
```

✅ Если видишь объект с url и anon → **плагин работает!**

❌ Если `undefined` → проверь wp-config.php (строки putenv должны быть добавлены)

---

### Тест 2: Проверка REST API

Открой в браузере:
```
https://yoursite.com/wp-json/supabase-auth/callback
```

**Должно вывести:**
```json
{
  "code": "rest_no_route",
  "message": "No route was found matching the URL and request method",
  "data": {"status": 404}
}
```

✅ Это **правильно**! Endpoint работает только для POST запросов.

❌ Если 500 ошибка → проверь логи сервера, возможно проблема с vendor/

---

## 📄 Шаг 5: Создание страниц WordPress

### 5.1 Callback страница (обязательно!)

**URL:** `https://yoursite.com/supabase-callback/`

1. WordPress Admin → **Pages** → **Add New**
2. **Title:** `Supabase Callback`
3. **Permalink:** Измени на `/supabase-callback/`
4. Добавь **HTML Block** (или Custom HTML в Elementor)
5. Вставь код из файла `htmlblock.html`:

```html
<div id="sb-status">Finishing sign-in…</div>
<script>
(async () => {
  const { createClient } = window.supabase;
  const cfg = window.SUPABASE_CFG;
  const sb = createClient(cfg.url, cfg.anon);

  const { data: { session }, error } = await sb.auth.getSession();
  if (error || !session) {
    document.getElementById("sb-status").textContent = "No session";
    return;
  }

  // Шлём токен на WP backend
  const r = await fetch("/wp-json/supabase-auth/callback", {
    method:"POST",
    credentials:"include",
    headers:{ "Content-Type":"application/json" },
    body: JSON.stringify({ access_token: session.access_token, user: session.user })
  });

  if (r.ok) {
    // ✅ редиректим на страницу благодарности
    window.location.href = "https://yoursite.com/registr/";
  } else {
    document.getElementById("sb-status").textContent = "Server verify failed";
  }
})();
</script>
```

6. **Publish** страницу

---

### 5.2 Страница благодарности (должна существовать)

**URL:** `https://yoursite.com/registr/`

Эта страница должна уже существовать (по коду она упоминается в редиректе).

Если её нет:
1. Создай страницу с permalink `/registr/`
2. Добавь контент: "Добро пожаловать! Вы успешно зарегистрированы."

---

### 5.3 Страница с кнопкой входа

**Где добавить:** Главная страница, header, или отдельная страница логина

Добавь **HTML Block** с кодом из файла `button.html`:

```html
<button id="login-google" style="padding: 12px 24px; background: #4285F4; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
  Войти через Google
</button>
<script>
document.getElementById('login-google').onclick = async () => {
  const { createClient } = window.supabase;
  const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);
  await sb.auth.signInWithOAuth({
    provider: 'google',
    options: { redirectTo: 'https://yoursite.com/supabase-callback/' }
  });
};
</script>
```

**Стилизация:** Можешь изменить стили кнопки под дизайн сайта.

---

## 🔧 Шаг 6: Настройка Supabase Dashboard

### 6.1 Включение Google OAuth

1. Открой https://app.supabase.com
2. Выбери проект с ID: `your-project-ref`
3. Перейди в **Authentication** → **Providers**
4. Найди **Google** в списке
5. Нажми **Enable**
6. Настрой Google OAuth:
   - **Client ID** и **Client Secret** - получи из Google Cloud Console
   - Инструкция: https://supabase.com/docs/guides/auth/social-login/auth-google

---

### 6.2 Добавление Redirect URL

1. В том же проекте Supabase
2. Перейди в **Authentication** → **URL Configuration**
3. В поле **Redirect URLs** добавь:
   ```
   https://yoursite.com/supabase-callback/
   ```
4. Нажми **Save**

⚠️ **Важно:** URL должен точно совпадать с созданной страницей WordPress!

---

## 🧪 Шаг 7: Финальное тестирование

### Тест полного flow:

1. **Открой страницу с кнопкой** "Войти через Google" (например, главную)

2. **Нажми кнопку**

3. **Ожидаемый flow:**
   - ✅ Откроется окно Google OAuth (выбор аккаунта)
   - ✅ После выбора аккаунта → редирект на `/supabase-callback/`
   - ✅ Быстрая обработка (1-2 секунды)
   - ✅ Автоматический редирект на `/registr/`
   - ✅ В админке WordPress видно залогиненного пользователя

4. **Проверка логина:**
   - Открой https://yoursite.com/wp-admin/
   - Должен быть залогинен БЕЗ ввода пароля
   - В меню админки видно твой email (из Google)

---

## ❌ Troubleshooting (Решение проблем)

### Проблема 1: Fatal error: vendor/autoload.php

**Симптом:**
```
Fatal error: require(): Failed opening 'vendor/autoload.php'
```

**Решение:**
1. Проверь что директория `vendor/` загружена на сервер
2. Проверь путь: `wp-content/plugins/supabase-bridge/vendor/autoload.php` должен существовать
3. Если файла нет:
   - Скачай проект заново
   - Убедись что `vendor/` включен в загрузку
   - Или выполни `composer install` на сервере (если доступен SSH)

---

### Проблема 2: window.SUPABASE_CFG is undefined

**Симптом:** В консоли браузера `window.SUPABASE_CFG` возвращает `undefined`

**Решение:**
1. Проверь что плагин активирован (WordPress Admin → Plugins)
2. Проверь wp-config.php - должны быть строки с `putenv()`
3. Проверь что открываешь страницу НЕ в админке (плагин работает только на фронтенде)
4. Очисти кеш браузера и перезагрузи страницу

---

### Проблема 3: "No session" на callback странице

**Симптом:** После редиректа с Google видишь "No session"

**Решение:**
1. **Проверь Google OAuth настройку:**
   - Supabase Dashboard → Authentication → Providers → Google должен быть Enabled
   - Client ID и Client Secret должны быть заполнены

2. **Проверь Redirect URL:**
   - Supabase Dashboard → URL Configuration
   - Должен быть добавлен: `https://yoursite.com/supabase-callback/`
   - Без слеша в конце будет ошибка!

3. **Проверь Supabase JS загрузку:**
   - В консоли браузера выполни: `console.log(window.supabase)`
   - Должен быть объект, а не `undefined`

---

### Проблема 4: "JWT verify failed"

**Симптом:** После callback видно "Server verify failed"

**Причины:**
1. **Неправильный anon key** - проверь в wp-config.php
2. **Email не подтвержден** - в Supabase требуется подтверждение email

**Решение:**
1. Проверь что SUPABASE_ANON_KEY правильный (скопирован из Supabase Dashboard)
2. Отключи проверку email_verified (временно для тестов):
   - Открой `supabase-bridge.php`
   - Закомментируй строки 72-74:
   ```php
   // if (isset($claims['email_verified']) && $claims['email_verified'] !== true) {
   //   throw new Exception('Email not verified');
   // }
   ```
3. Или настрой Supabase на автоподтверждение email для тестов

---

### Проблема 5: Redirect на неправильную страницу

**Симптом:** После логина редирект не на `/registr/`, а на другую страницу

**Решение:**
1. Проверь `htmlblock.html` на callback странице
2. Найди строку:
   ```javascript
   window.location.href = "https://yoursite.com/registr/";
   ```
3. Измени URL на нужный тебе

---

## ✅ Чеклист готовности к запуску

Перед тем как тестировать, убедись:

- [ ] ✅ Плагин загружен в `wp-content/plugins/supabase-bridge/`
- [ ] ✅ Директория `vendor/` присутствует на сервере
- [ ] ✅ wp-config.php содержит 3 строки putenv() для Supabase
- [ ] ✅ Плагин активирован в WordPress Admin
- [ ] ✅ `window.SUPABASE_CFG` доступен в консоли браузера
- [ ] ✅ Callback страница создана: `/supabase-callback/`
- [ ] ✅ Страница благодарности существует: `/registr/`
- [ ] ✅ Google OAuth включен в Supabase Dashboard
- [ ] ✅ Redirect URL добавлен в Supabase: `https://yoursite.com/supabase-callback/`
- [ ] ✅ Кнопка входа добавлена на сайт

---

## 🎉 Готово к запуску!

После выполнения всех шагов:

✅ Плагин установлен
✅ Конфигурация настроена
✅ Страницы созданы
✅ Supabase OAuth настроен

**Можно тестировать авторизацию через Google!** 🚀

---

## 📚 Дополнительные файлы

- **STATUS.md** - Текущий статус проекта
- **README.md** - Полная документация плагина
- **ARCHITECTURE.md** - Техническая архитектура
- **FLOW.md** - Визуализация потока авторизации
- **AGENTS.md** - Инструкции для AI ассистентов

---

*Последнее обновление: 2025-10-01 19:25*
*Статус: READY FOR DEPLOYMENT (vendor/ installed)*
