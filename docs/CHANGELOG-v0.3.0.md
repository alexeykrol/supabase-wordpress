# 📜 Changelog v0.3.0 - Facebook OAuth + Passwordless

**Дата релиза:** 2025-10-05
**Статус:** Production Ready ✅
**Протестировано на:** https://questtales.com/test_login_supa/

---

## 🎉 Основные изменения

### ✅ Добавлен Facebook OAuth
- Реализована полная поддержка Facebook OAuth
- Настроен Advanced access для `email` и `public_profile`
- Добавлен `scopes: 'email public_profile'` в JavaScript handler
- Протестировано на продакшене

### ✅ Добавлен Magic Link (Passwordless)
- Email авторизация с автоматической отправкой кода
- 6-digit verification code
- Resend functionality
- "I already have a code" опция

### ✅ Реализованы умные редиректы
- **Новый пользователь** (< 60 сек) → thank-you page
- **Существующий пользователь** → возврат на referrer
- Автоматическое определение referrer (откуда пришёл)
- Защита от redirect loops

### ✅ 3 режима настройки thank-you pages
1. **Стандартный** - одна страница для всех
2. **Парный** - разные страницы для разных лендингов
3. **Гибкий** - переопределение через URL параметры

### ✅ Готовая форма авторизации
- Файл: `auth-form.html` (40KB)
- Включает: Google + Facebook + Magic Link
- Полная документация в комментариях
- Готова к использованию в Elementor

---

## 📝 Изменённые файлы

### Код:
- **auth-form.html** - добавлен Facebook OAuth, обновлён заголовок до v2.2

### Документация:
- **STATUS.md** - обновлён до v0.3.0, добавлена таблица протестированных провайдеров
- **README.md** - добавлен changelog, обновлён список возможностей
- **DEBUG.md** - добавлена секция Facebook OAuth troubleshooting
- **QUICKSTART.md** - обновлены инструкции для 3 методов авторизации
- **AUTH-FORM-REDIRECT-GUIDE.md** - обновлена версия до 2.2, добавлено примечание об универсальности

---

## 🔧 Технические детали

### Facebook OAuth изменения:

**Код (auth-form.html):**
```javascript
// Добавлена кнопка Facebook (линии 587-593)
<button class="sb-oauth-btn" id="sb-facebook-btn">
  <svg>...</svg>
  Continue with Facebook
</button>

// Добавлен DOM элемент (линия 741)
const facebookBtn = document.getElementById('sb-facebook-btn');

// Добавлен обработчик (линии 1020-1045)
facebookBtn.addEventListener('click', async () => {
  await supabaseClient.auth.signInWithOAuth({
    provider: 'facebook',
    options: {
      redirectTo: window.location.origin + window.location.pathname,
      scopes: 'email public_profile'  // ← КРИТИЧНО!
    }
  });
});
```

**Facebook Developer Console настройки:**
- App Review → Permissions → Advanced access для `email`: ✅ Granted
- App Review → Permissions → Advanced access для `public_profile`: ✅ Granted

**Supabase Dashboard:**
- Authentication → Providers → Facebook: ✅ Enabled
- URL Configuration → Redirect URLs: `https://questtales.com/test_login_supa/`

---

## 🧪 Что было протестировано

| Метод авторизации | Статус | Комментарий |
|-------------------|--------|-------------|
| Google OAuth | ✅ Работает | Протестировано на questtales.com |
| Facebook OAuth | ✅ Работает | Advanced access granted, email возвращается |
| Magic Link | ✅ Работает | Email + 6-digit код |
| Умные редиректы | ✅ Работают | Новый → /registr/, существующий → referrer |

---

## 🐛 Исправленные проблемы

### Проблема 1: Facebook не возвращал email
**Симптом:**
```
error_description=Error getting user email from external provider
```

**Решение:**
1. Добавлен `scopes: 'email public_profile'` в JavaScript
2. Включён Advanced access в Facebook Developer Console

### Проблема 2: Turnstile warnings в консоли
**Симптом:**
```
TurnstileError: [Cloudflare Turnstile] Error: 400020
```

**Анализ:** Это нормальные предупреждения Cloudflare, не блокируют OAuth flow. Google и Facebook работают корректно.

---

## 📚 Новая документация

### Добавлено:
- **DEBUG.md** - секция "Facebook OAuth - Частые проблемы"
- **CHANGELOG-v0.3.0.md** - этот файл
- **README.md** - секция "Что реализовано (v0.3.0)"

### Обновлено:
- **STATUS.md** - новая таблица с OAuth провайдерами
- **QUICKSTART.md** - инструкции для всех 3 методов
- **AUTH-FORM-REDIRECT-GUIDE.md** - примечание об универсальности

---

## 🚀 Migration Guide (если обновляешься с v0.2.0)

### Если используешь старый код:
1. Скачай новый `auth-form.html`
2. Скопируй свои настройки `AUTH_CONFIG` из старого файла
3. Вставь новый код в Elementor
4. Готово! Facebook кнопка появится автоматически

### Если настраиваешь Facebook впервые:
1. Facebook Developer Console:
   - App Review → Permissions → Advanced access для `email` ✅
   - App Review → Permissions → Advanced access для `public_profile` ✅
2. Supabase Dashboard:
   - Authentication → Providers → Facebook → Enable
3. Используй `auth-form.html` (Facebook уже включён)

---

## 🎯 Следующие версии (Roadmap)

### v0.4.0 (планируется):
- ⏳ Apple Sign In
- ⏳ Twitter/X OAuth
- ⏳ GitHub OAuth
- ⏳ Discord OAuth

### v0.5.0 (планируется):
- ⏳ Поддержка маппинга Supabase roles → WP roles
- ⏳ Admin UI для управления редиректами
- ⏳ Analytics dashboard (кто откуда зарегистрировался)

---

## 📊 Статистика

- **Файлов изменено:** 6
- **Строк кода добавлено:** ~150
- **Документации добавлено:** ~300 строк
- **OAuth провайдеров:** 3 (было 1)
- **Методов авторизации:** 3 (Google + Facebook + Magic Link)
- **Время тестирования:** ~2 часа
- **Статус:** Production Ready ✅

---

## ✅ Что работает в v0.3.0

```
┌─────────────────────────────────────────┐
│  Supabase Bridge v0.3.0                 │
├─────────────────────────────────────────┤
│  ✅ Google OAuth                         │
│  ✅ Facebook OAuth (Advanced access)    │
│  ✅ Magic Link (Passwordless)           │
│  ✅ Умные редиректы                     │
│  ✅ 3 режима thank-you pages            │
│  ✅ Защита от redirect loops            │
│  ✅ Автоопределение referrer            │
│  ✅ WordPress синхронизация             │
│  ✅ JWT верификация                     │
└─────────────────────────────────────────┘
```

---

**Версия:** 0.3.0
**Дата:** 2025-10-05
**Авторы:** Alexey Krol + Claude Code
**Протестировано:** https://questtales.com ✅
