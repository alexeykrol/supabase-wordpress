# 🚀 Quick Start - Установка за 5 минут

**Файл для установки:** `/Users/alexeykrolmini/Downloads/Code/supabase-bridge.zip` (43KB)

---

## Шаг 1: Загрузи ZIP в WordPress Admin (2 минуты)

1. Открой: `https://yoursite.com/wp-admin/`
2. **Plugins** → **Add New** → **Upload Plugin**
3. Выбери файл: `supabase-bridge.zip`
4. **Install Now** → **Activate Plugin**

✅ **Результат:** Автоматически откроется страница с инструкциями

---

## Шаг 2: Настрой wp-config.php (1 минута)

Добавь в `wp-config.php` ПЕРЕД строкой `/* That's all, stop editing! */`:

```php
// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=your-project-ref');
putenv('SUPABASE_URL=https://your-project-ref.supabase.co');
putenv('SUPABASE_ANON_KEY=your-supabase-anon-key-here');
```

---

## Шаг 3: Создай страницу логина в WordPress (2 минуты)

**Используй готовую форму auth-form.html!**

### Страница 1: Вход (любой URL, например `/test_login_supa/`)
- Создай новую страницу
- Вставь код из `auth-form.html` (полная форма с Google + Facebook + Magic Link)
- Или используй простые кнопки из Admin UI плагина

### Страница 2: Регистрация (URL: `/registr/`)
- Создай страницу с любым контентом
- Сюда попадут новые пользователи после авторизации

**Что включает auth-form.html:**
- ✅ Google OAuth
- ✅ Facebook OAuth
- ✅ Magic Link (Passwordless)
- ✅ Умные редиректы

---

## Шаг 4: Настрой Supabase Dashboard (1 минута)

1. https://app.supabase.com → проект `your-project-ref`
2. **Authentication** → **URL Configuration**
3. Добавь Redirect URL: `https://yoursite.com/test_login_supa/` (или твоя страница)
4. **Authentication** → **Providers:**
   - ✅ Включи **Google OAuth**
   - ✅ Включи **Facebook OAuth** (если нужен)
   - ✅ Настрой **Email Auth** (для Magic Link)

**Для Facebook OAuth дополнительно:**
- Facebook Developer Console → App Review → Permissions
- Включи **Advanced access** для `email` и `public_profile`

---

## Шаг 5: Тестируй! (1 минута)

### Google OAuth:
1. Открой страницу входа
2. Нажми "Continue with Google"
3. Пройди авторизацию
4. Проверь редирект на `/registr/`
5. Проверь что ты залогинен (admin bar в WordPress)

### Facebook OAuth:
1. Нажми "Continue with Facebook"
2. Разреши доступ (email + public profile)
3. Должен залогиниться

### Magic Link:
1. Введи email → "Continue with email"
2. Проверь почту → открой письмо
3. Введи 6-digit код
4. Должен залогиниться

---

## ✅ Готово!

**Если что-то не работает:**
- Открой любую страницу → F12 → Console
- Выполни: `console.log(window.SUPABASE_CFG)`
- Должен вывести объект с `url` и `anon`

**Подробные инструкции:**
- См. Admin UI плагина: **Supabase Bridge** в левой панели
- См. файл: `DEPLOYMENT.md`
- Гайд по редиректам: `AUTH-FORM-REDIRECT-GUIDE.md`
- Troubleshooting: `DEBUG.md`

**Рабочий пример:**
`https://yoursite.com/test_login_supa/`

---

*Последнее обновление: 2025-10-05 23:58*
*Версия: 0.3.2 - Security Hotfix*
