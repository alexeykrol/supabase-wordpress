# 🎯 Шпаргалка: Настройка редиректов в auth-form.html

**Версия:** 2.2
**Файл:** auth-form.html
**Дата:** 2025-10-05

**Поддерживаемые методы авторизации:**
- ✅ Google OAuth
- ✅ Facebook OAuth
- ✅ Magic Link (Passwordless)

---

## 🚀 Быстрый старт

### Где настраивать?

Открой `auth-form.html` → найди `AUTH_CONFIG` → отредактируй `thankYouPages`

**Важно:** Настройки редиректов **универсальные** - работают одинаково для всех методов авторизации (Google, Facebook, Magic Link)!

---

## 📋 Три режима работы

### **Режим 1: Стандартный** (одна страница для всех)

**Когда использовать:**
- Простой сайт
- Одна воронка
- Не нужна кастомизация

**Настройка:**
```javascript
const AUTH_CONFIG = {
  thankYouPages: {
    'default': '/thank-you/'
  },
  defaultRedirect: '/',
  newUserThreshold: 60000
};
```

**Результат:**
- ✅ Новый пользователь → `/thank-you/`
- ✅ Существующий → возврат на страницу откуда пришел

---

### **Режим 2: Парный** (разные thank-you для разных лендингов)

**Когда использовать:**
- Несколько воронок продаж
- Разные лендинги
- Каждый лендинг → своя страница благодарности

**Настройка:**
```javascript
const AUTH_CONFIG = {
  thankYouPages: {
    '/landing-premium/': '/thank-you-premium/',
    '/landing-basic/': '/thank-you-basic/',
    '/webinar/': '/thank-you-webinar/',
    '/promo/': '/promo-success/',
    'default': '/thank-you/'  // Fallback
  },
  defaultRedirect: '/',
  newUserThreshold: 60000
};
```

**Как работает:**
1. Пользователь на `/landing-premium/` → нажимает кнопку регистрации → переходит на `/auth/`
2. Система автоматически сохраняет referrer: `/landing-premium/`
3. После регистрации ищет соответствие в `thankYouPages`
4. Находит `/landing-premium/` → редиректит на `/thank-you-premium/`

**Результат:**
- ✅ Новый с `/landing-premium/` → `/thank-you-premium/`
- ✅ Новый с `/webinar/` → `/thank-you-webinar/`
- ✅ Новый с `/другой-страницы/` → `/thank-you/` (default)
- ✅ Существующий → возврат на страницу откуда пришел

---

### **Режим 3: Гибкий** (URL параметры)

**Когда использовать:**
- Одноразовые акции
- A/B тесты
- Динамические воронки
- Переопределение стандартного mapping

**Настройка на лендинге:**
```html
<!-- Кнопка/ссылка регистрации -->
<a href="/auth/?thank_you=/special-promo-2025/">Зарегистрироваться</a>

<!-- Кнопка входа с кастомным редиректом -->
<a href="/auth/?redirect_to=/dashboard/">Войти</a>

<!-- Комбинация обоих -->
<a href="/auth/?thank_you=/promo-ty/&redirect_to=/continue-shopping/">
  Регистрация
</a>
```

**Приоритет (сверху вниз):**
1. **URL параметр** `?thank_you=` или `?redirect_to=` (высший)
2. **Mapping** в `AUTH_CONFIG.thankYouPages`
3. **Default** в `AUTH_CONFIG.thankYouPages.default`

**Результат:**
- ✅ Новый пользователь → URL из `?thank_you=` (переопределяет всё!)
- ✅ Существующий → URL из `?redirect_to=` (переопределяет referrer)
- ✅ Если параметров нет → работает как Режим 2 (mapping)

---

## 🔍 Примеры использования

### Пример 1: Простой блог

```javascript
const AUTH_CONFIG = {
  thankYouPages: {
    'default': '/welcome/'
  }
};
```

**Путь пользователя:**
- Читает статью `/article-123/` → нажимает "Подписаться" → `/auth/`
- Регистрируется → `/welcome/`

---

### Пример 2: Интернет-магазин

```javascript
const AUTH_CONFIG = {
  thankYouPages: {
    '/shop/premium-plan/': '/onboarding-premium/',
    '/shop/basic-plan/': '/onboarding-basic/',
    '/shop/trial/': '/trial-started/',
    'default': '/shop/'
  }
};
```

**Путь пользователя:**
- Смотрит `/shop/premium-plan/` → "Купить" → `/auth/`
- Регистрируется → `/onboarding-premium/`

---

### Пример 3: Вебинарная воронка

```javascript
const AUTH_CONFIG = {
  thankYouPages: {
    '/webinar-landing/': '/webinar-registration-success/',
    'default': '/thank-you/'
  }
};
```

**Путь пользователя:**
- Заходит на `/webinar-landing/` → "Зарегистрироваться" → `/auth/`
- Регистрируется → `/webinar-registration-success/`

---

### Пример 4: Временная акция (Режим 3)

**На специальной странице акции:**
```html
<a href="/auth/?thank_you=/black-friday-success/">
  Получить скидку 50%
</a>
```

**Результат:**
- Новый пользователь → `/black-friday-success/` (независимо от mapping)
- Существующий → возврат на страницу акции

---

## 🛠️ Настройка порога "новый пользователь"

```javascript
const AUTH_CONFIG = {
  // ...
  newUserThreshold: 60000  // 60 секунд (1 минута)
};
```

**Варианты:**
- `30000` - 30 секунд
- `60000` - 1 минута (по умолчанию)
- `120000` - 2 минуты
- `300000` - 5 минут

**Логика:**
- Пользователь зарегистрировался **< порога** назад → считается новым → `/thank-you/`
- Пользователь зарегистрировался **> порога** назад → существующий → возврат назад

---

## 🔍 Отладка

### Открой консоль браузера (F12)

**Что смотреть:**

```
🎯 ORIGIN_PAGE: from referrer = /landing-premium/
```
↑ Откуда пришел пользователь (автоматически определено)

```
Auth event: SIGNED_IN
```
↑ Успешная авторизация

```
Redirecting to: /thank-you-premium/ (new user: true)
```
↑ Куда редиректим и почему

---

### Проверка конфигурации

```javascript
// В консоли браузера
console.log(window.SUPABASE_CFG);
// Должно вывести: {url: "...", anon: "..."}
```

Если `undefined` → плагин не активирован или wp-config.php не настроен.

---

## 📊 Сравнение режимов

| Режим | Сложность настройки | Гибкость | Когда использовать |
|-------|---------------------|----------|-------------------|
| **Стандартный** | ⭐ Легко | ⭐ Низкая | Простой сайт, 1 воронка |
| **Парный** | ⭐⭐ Средне | ⭐⭐⭐ Средняя | Несколько лендингов, постоянные воронки |
| **Гибкий** | ⭐⭐⭐ Сложно | ⭐⭐⭐⭐⭐ Высокая | A/B тесты, временные акции, динамика |

---

## ⚠️ Частые ошибки

### ❌ Ошибка 1: Неправильный путь в mapping

```javascript
thankYouPages: {
  'landing-premium': '/thank-you/'  // ❌ Без слэшей!
}
```

**Правильно:**
```javascript
thankYouPages: {
  '/landing-premium/': '/thank-you/'  // ✅ Со слэшами
}
```

---

### ❌ Ошибка 2: Забыл fallback

```javascript
thankYouPages: {
  '/landing-premium/': '/thank-you-premium/'
  // ❌ Нет 'default'!
}
```

**Правильно:**
```javascript
thankYouPages: {
  '/landing-premium/': '/thank-you-premium/',
  'default': '/thank-you/'  // ✅ Всегда добавляй default
}
```

---

### ❌ Ошибка 3: Referrer не работает

**Проблема:** Пользователь переходит на страницу логина **напрямую** (вводит URL в браузере)

**Решение:** Всегда используй ссылки/кнопки для перехода на `/auth/`:

```html
<!-- ✅ Правильно -->
<a href="/auth/">Регистрация</a>

<!-- ❌ Неправильно -->
Скажи пользователю: "Зайди на example.com/auth/"
```

---

## 🎓 Рекомендации

### Для простого сайта:
→ Используй **Режим 1** (стандартный)

### Для сайта с несколькими воронками:
→ Используй **Режим 2** (парный)

### Для временных акций:
→ Используй **Режим 3** (гибкий) поверх Режима 2

### Универсальная настройка:
```javascript
const AUTH_CONFIG = {
  thankYouPages: {
    // Основные воронки (Режим 2)
    '/landing-1/': '/thank-you-1/',
    '/landing-2/': '/thank-you-2/',

    // Fallback (Режим 1)
    'default': '/thank-you/'
  },
  defaultRedirect: '/',
  newUserThreshold: 60000
};

// + На особых страницах используй ?thank_you= (Режим 3)
```

---

## 📝 Чеклист настройки

- [ ] Открыл `auth-form.html`
- [ ] Нашел `AUTH_CONFIG`
- [ ] Выбрал режим (1, 2 или 3)
- [ ] Настроил `thankYouPages`
- [ ] Добавил `'default': '/thank-you/'`
- [ ] Проверил что все пути начинаются и заканчиваются слэшем `/`
- [ ] Сохранил файл
- [ ] Вставил в Elementor HTML виджет
- [ ] Протестировал через F12 консоль

---

*Последнее обновление: 2025-10-05*
*Версия файла: 2.2 - Google + Facebook + Magic Link*
