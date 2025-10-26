# Project Intake Form

**Purpose:** Fill this document BEFORE starting development to provide AI agents with essential context
**Status:** ✅ FILLED (Migrated from legacy docs)
**Migration Status:** ✅ COMPLETED (2025-10-23)
**Last Updated:** 2025-10-23

---

<!-- MIGRATED FROM: README.md, SECURITY.md -->

## 🎯 Project Overview

> **Важно:** Проектирование начинается с вопроса "**ЗАЧЕМ?**". Ответьте на три ключевых вопроса:

### 1. Ключевая идея (Elevator Pitch)
**Опишите суть приложения в ОДНОМ предложении:**

**Supabase Bridge** — минимальный WordPress плагин, который позволяет использовать Supabase Auth как единую систему аутентификации на сайте WordPress, синхронизируя пользователей между Supabase и WordPress.

---

### 2. Проблема (The Problem)
**Какую конкретную проблему пользователя вы решаете?**

WordPress из коробки умеет работать только со своей системой `wp_users`. Разработчикам нужна централизованная система аутентификации для:
- Поддержки десятков социальных провайдеров (Google, Facebook, Apple, GitHub и т.п.)
- Работы с токенами (JWT) и современными механизмами безопасности (RLS, refresh tokens)
- Использования единой базы пользователей для WordPress + кастомных сервисов (React-приложения, боты, нативные клиенты)

**Почему существующие решения не работают?**

Существующие решения либо требуют "рандомных" плагинов с полной зависимостью от WP User API, либо не обеспечивают прозрачную синхронизацию между external auth provider и WordPress.

---

### 3. Решение (The Solution)
**Как ИМЕННО ваше приложение решает эту проблему?**

Supabase Bridge создает "мост" между Supabase Auth и WordPress:
- Пользователи логинятся через Supabase (Google, Facebook, Magic Link, etc.)
- После успешной аутентификации создается зеркальная учетная запись в WordPress
- Пользователь автоматически залогинен в WordPress (сессия и куки выставлены)
- JWT проверяется сервером через JWKS (безопасная верификация)

**Какую уникальную ценность вы даете?**

- ✅ Единая учетная запись для пользователей
- ✅ Безопасная JWT верификация (RS256 с JWKS)
- ✅ Прозрачная синхронизация Supabase ↔ WordPress
- ✅ Поддержка любых провайдеров Supabase
- ✅ Production-ready с полным security hardening

---

### 4. Целевая аудитория (Target Audience)
**Для КОГО вы создаете этот продукт?**

WordPress разработчики и владельцы сайтов, которые хотят:
- Использовать modern auth solutions (Supabase) вместо legacy WP auth
- Централизовать аутентификацию для нескольких сервисов
- Предоставить пользователям удобную регистрацию через соцсети
- Сохранить совместимость с WordPress плагинами и ролями

**Их характеристики:**
- Возраст: 25-50 лет
- Профессия/род деятельности: WordPress разработчики, веб-студии, SaaS founders
- Технологическая грамотность: [низкая/средняя/высокая]
- Готовность платить: [бесплатно/freemium/подписка/разовые платежи]

---

## 👥 User Personas (Портреты пользователей)

> **Цель:** "Очеловечить" пользователей для ИИ. Создайте 1-3 вымышленных персонажа - ваших идеальных пользователей.

### Persona 1: [Имя и возраст]

**Пример:**
```
Имя: Анна, 32 года
Роль: Маркетолог, владелица активного бигля
Локация: Москва, живет в центре города
```

**Ваша Persona 1:**
```
Имя: [ОТВЕТИТЬ]
Роль: [ОТВЕТИТЬ]
Локация: [ОТВЕТИТЬ]
```

**Цели:**
[ОТВЕТИТЬ: Например, "Найти безопасную площадку рядом с работой, чтобы поиграть с собакой в обеденный перерыв"]

**Проблемы (Pain Points):**
[ОТВЕТИТЬ: Например, "Публичные парки часто грязные, другие собаки могут быть агрессивными. Нужно уединенное и чистое место"]

**Поведение с технологиями:**
[ОТВЕТИТЬ: Например, "Активный пользователь мобильных приложений, любит удобные сервисы, готова платить за качество"]

---

### Persona 2: [Имя и возраст] (опционально)

[ОТВЕТИТЬ: Повторить структуру для второй персоны, если нужно]

---

### Persona 3: [Имя и возраст] (опционально)

[ОТВЕТИТЬ: Повторить структуру для третьей персоны, если нужно]

---

## 🗺️ User Flows (Сценарии взаимодействия)

> **Цель:** Описать ПО ШАГАМ, как пользователь будет взаимодействовать с приложением для достижения своих целей.

### Ключевой сценарий 1: [Название, например "Первое бронирование"]

**Пример:**
```
1. Пользователь открывает приложение и видит карту со своим местоположением
2. Он использует фильтр, чтобы найти площадки с высоким рейтингом
3. Нажимает на одну из площадок, переходит на её страницу
4. Выбирает свободную дату и время, нажимает "Забронировать"
5. Переходит на экран оплаты, вводит данные карты
6. Подтверждает бронирование
7. Получает push-уведомление с подтверждением
```

**Ваш сценарий 1:**
```
1. [ОТВЕТИТЬ: Первый шаг]
2. [ОТВЕТИТЬ: Второй шаг]
3. [ОТВЕТИТЬ: Третий шаг]
...
```

---

### Ключевой сценарий 2: [Название] (опционально)

[ОТВЕТИТЬ: Опишите второй важный сценарий использования]

---

### Ключевой сценарий 3: [Название] (опционально)

[ОТВЕТИТЬ: Опишите третий важный сценарий использования]

---

## 🛠️ Technology Stack

> **Важно:** Если вы НЕ разбираетесь в технологиях - не заполняйте этот раздел. Напишите в секции 5: "Предложи оптимальный стек сам" - и ИИ предложит лучшие варианты с обоснованием.

### 4. Frontend Framework
**Choose one and specify version if important:**

- [ ] React (with Next.js / Vite / CRA)
- [ ] Vue.js (with Nuxt / Vite)
- [ ] Angular
- [ ] Svelte / SvelteKit
- [ ] Other: [УКАЗАТЬ]

**Selected:** [ОТВЕТИТЬ: Например, "React 18 + Next.js 14 (App Router)"]

---

### 5. Language
**TypeScript or JavaScript?**

- [ ] TypeScript (recommended)
- [ ] JavaScript

**Selected:** [ОТВЕТИТЬ]

---

### 6. Styling Solution

- [ ] Tailwind CSS (recommended for speed)
- [ ] CSS Modules
- [ ] Styled Components / Emotion
- [ ] Plain CSS / SCSS
- [ ] Other: [УКАЗАТЬ]

**Selected:** [ОТВЕТИТЬ]

---

### 7. Backend / Database

**Choose backend approach:**

- [ ] Supabase (recommended - auth + DB + real-time)
- [ ] Firebase
- [ ] Custom backend (Node.js / Python / Go)
- [ ] Serverless (AWS Lambda / Vercel Functions)
- [ ] Other: [УКАЗАТЬ]

**Selected:** [ОТВЕТИТЬ]

**Database type:**
- [ ] PostgreSQL (Supabase)
- [ ] MongoDB
- [ ] MySQL
- [ ] SQLite
- [ ] Other: [УКАЗАТЬ]

**Selected:** WordPress internal (`wp_users` table) + Supabase PostgreSQL for user data

---

### 8. Authentication

**How will users log in?**

- [x] Email/Password
- [x] OAuth (Google, GitHub, etc.)
- [x] Magic Link (passwordless)
- [ ] Phone (SMS)
- [ ] No auth needed
- [ ] Other: [УКАЗАТЬ]

**Selected:** Supabase Auth with Google OAuth, Facebook OAuth, Magic Link (6-digit code)

**Auth provider:**
- [x] Supabase Auth
- [ ] Firebase Auth
- [ ] Auth0
- [ ] NextAuth.js
- [ ] Custom
- [ ] Other: [УКАЗАТЬ]

**Selected:** Supabase Auth (JWT verification via JWKS, RS256 signature)

---

### 9. Hosting / Deployment

**Where will this be deployed?**

- [ ] Vercel (recommended for Next.js)
- [ ] Netlify
- [ ] AWS
- [ ] Google Cloud
- [ ] Self-hosted
- [ ] Other: [УКАЗАТЬ]

**Selected:** [ОТВЕТИТЬ]

---

## ✨ Core Features (MVP)

> **Важно:** Разделяйте функции на **уникальные** (ваша ценность) и **стандартные** (готовые сервисы).
>
> **Правило 99%:** Стандартные функции НИКОГДА не пишутся с нуля - используются готовые сервисы/библиотеки!

### 10a. Уникальные функции (Ваша ценность)

**Это то, что отличает ваше приложение от других. То, ради чего пользователь придет к вам.**

**Priority order (most important first):**

1. [ОТВЕТИТЬ: Например, "Интерактивная карта с проверенными площадками и фильтрами"]
2. [ОТВЕТИТЬ: Например, "Система рейтингов и отзывов владельцев собак"]
3. [ОТВЕТИТЬ: Например, "Онлайн-бронирование площадок с календарем"]
4. [ОТВЕТИТЬ: Например, "Профили площадок с фото, описанием, правилами"]
5. [ОТВЕТИТЬ: Например, "Push-уведомления о подтверждении бронирования"]

**Какую уникальную ценность дают эти функции?**

[ОТВЕТИТЬ: Например, "Гарантия безопасности через проверку и рейтинги, удобство бронирования, экономия времени на поиск"]

---

### 10b. Стандартные функции (Ready-to-use)

**Эти функции НЕ дают прямую ценность, но без них неудобно. Используем готовые сервисы!**

**Выберите нужные:**

#### Аутентификация и пользователи:
- [ ] Регистрация / Логин (email/password)
- [ ] OAuth (Google, Facebook, Apple)
- [ ] Профиль пользователя
- [ ] Восстановление пароля
- [ ] Сервис: [Supabase Auth / Firebase Auth / Clerk / Auth0]

#### Платежи:
- [ ] Прием платежей (карты)
- [ ] Подписки
- [ ] Выставление счетов
- [ ] Сервис: [Stripe / Lemon Squeezy / PayPal]

#### Уведомления:
- [ ] Email (транзакционные письма)
- [ ] SMS
- [ ] Push-уведомления
- [ ] Сервис: [Resend / SendGrid / Firebase Cloud Messaging]

#### Хранилище:
- [ ] Загрузка файлов (фото, документы)
- [ ] Хранилище видео
- [ ] Сервис: [Supabase Storage / Cloudinary / AWS S3]

#### Аналитика:
- [ ] Трекинг пользователей
- [ ] Аналитика поведения
- [ ] Сервис: [Plausible / Mixpanel / Google Analytics]

#### Коммуникация:
- [ ] Чат с поддержкой
- [ ] Система тикетов
- [ ] Сервис: [Zendesk / Intercom / Crisp]

#### Другое:
- [ ] Геолокация / Карты
- [ ] Поиск
- [ ] AI/ML функции
- [ ] Сервис: [УКАЗАТЬ]

**Выбранные стандартные функции:**

[ОТВЕТИТЬ: Список с указанием сервиса, например:
- Аутентификация - Supabase Auth
- Платежи - Stripe
- Email - Resend
- Файлы - Cloudinary
- Аналитика - Plausible]

---

### 11. User Roles

**Does the app have different user types?**

- [ ] No roles (all users equal)
- [ ] Yes, multiple roles

**If yes, list roles:**
- [ОТВЕТИТЬ: Например, "Admin - full access"]
- [ОТВЕТИТЬ: Например, "Team Member - can view and edit tasks"]
- [ОТВЕТИТЬ: Например, "Viewer - read-only access"]

---

## 📊 Data Structure

### 12. Main Entities (Database Tables)

**List main data models and their key fields:**

**Example:**
```
Users
  - id, email, name, avatar, role, created_at

Projects
  - id, title, description, owner_id, created_at

Tasks
  - id, title, description, project_id, assignee_id, due_date, status
```

**Your entities:**
```
[ОТВЕТИТЬ: Перечислить основные таблицы и их поля]

Entity 1: [Name]
  - [field1], [field2], [field3]...

Entity 2: [Name]
  - [field1], [field2], [field3]...

Entity 3: [Name]
  - [field1], [field2], [field3]...
```

---

### 13. Relationships

**How are entities connected?**

[ОТВЕТИТЬ: Например,
- "One User can own many Projects (1:N)"
- "One Project can have many Tasks (1:N)"
- "One Task belongs to one User (assignee) (N:1)"
- "Users and Projects - many-to-many (team members)"]

---

## 🔌 External Integrations

### 14. Third-Party Services

**Will the app integrate with external services?**

- [ ] Email service (SendGrid, Resend, Mailgun)
- [ ] Payment processing (Stripe, PayPal)
- [ ] File storage (AWS S3, Cloudinary)
- [ ] Analytics (Google Analytics, Plausible)
- [ ] AI/ML services (OpenAI, Anthropic)
- [ ] Other: [УКАЗАТЬ]

**Selected integrations:**
[ОТВЕТИТЬ: Список сервисов с кратким описанием зачем]

---

### 15. API Requirements

**Does the app need to expose an API?**

- [ ] No, frontend only
- [ ] Yes, REST API
- [ ] Yes, GraphQL
- [ ] Yes, WebSocket / Real-time

**If yes, describe:**
[ОТВЕТИТЬ: Например, "REST API для мобильного приложения в будущем"]

---

## 🎨 UI/UX Requirements

### 16. Design Reference

**Are there existing apps with similar UI?**

[ОТВЕТИТЬ: Например,
- "Linear.app - минималистичный, быстрый"
- "Notion - гибкий, модульный"
- "Slack - современный, дружелюбный"]

---

### 17. Design Assets Available?

**Do you have designs ready?**

- [ ] Yes, Figma/Sketch designs available
  - Link: [ВСТАВИТЬ ССЫЛКУ]
- [ ] Yes, screenshots/wireframes
  - Location: [УКАЗАТЬ ГДЕ]
- [ ] No, AI should propose basic UI
- [ ] No, will design as we go

**Selected:** [ОТВЕТИТЬ]

---

### 18. Responsive Requirements

**Mobile support needed?**

- [ ] Desktop only
- [ ] Responsive (mobile + desktop)
- [ ] Mobile-first
- [ ] Native mobile app planned later

**Selected:** [ОТВЕТИТЬ]

---

## 🔐 Security & Compliance

### 19. Security Requirements

**Any special security needs?**

- [ ] Standard web security (XSS, CSRF protection)
- [ ] GDPR compliance required
- [ ] HIPAA compliance (healthcare data)
- [ ] PCI compliance (payment data)
- [ ] Two-factor authentication (2FA)
- [ ] Other: [УКАЗАТЬ]

**Selected:** [ОТВЕТИТЬ]

---

### 20. Data Privacy

**Where should data be stored?**

- [ ] Any region (no restrictions)
- [ ] EU only (GDPR)
- [ ] US only
- [ ] Specific region: [УКАЗАТЬ]

**Selected:** [ОТВЕТИТЬ]

---

## 📈 Scale & Performance

### 21. Expected Scale

**How many users expected in first year?**

- [ ] < 100 users (prototype/MVP)
- [ ] 100-1,000 users (small app)
- [ ] 1,000-10,000 users (growing app)
- [ ] 10,000+ users (production scale)

**Selected:** [ОТВЕТИТЬ]

---

### 22. Performance Requirements

**Any critical performance needs?**

[ОТВЕТИТЬ: Например,
- "Page load < 2 seconds"
- "Real-time updates < 500ms delay"
- "Support 1000 concurrent users"]

---

## 💰 Budget & Timeline

### 23. Development Timeline

**Expected timeline for MVP?**

- [ ] 1-2 weeks (simple MVP)
- [ ] 1 month (standard MVP)
- [ ] 2-3 months (complex MVP)
- [ ] Other: [УКАЗАТЬ]

**Selected:** [ОТВЕТИТЬ]

---

### 24. Budget Constraints

**Any constraints on external services cost?**

[ОТВЕТИТЬ: Например, "Стараться использовать free tier сервисов, платные только если критично"]

---

## 🔄 Development Approach

> **Философия:** Модульная архитектура - ключ к быстрой и дешевой разработке с ИИ

### 25a. Модульная структура (ОБЯЗАТЕЛЬНО!)

**Почему модульная архитектура критична для работы с ИИ:**

📖 **ПОЛНАЯ ФИЛОСОФИЯ:** ARCHITECTURE.md → "Module Architecture" section

**Краткое объяснение:**
1. **Экономия токенов:** ИИ загружает только нужный модуль (100-200 строк), а не весь проект (1000+ строк)
2. **Простота:** Каждый модуль = отдельная задача = легко проверить
3. **Скорость:** Можно делать разные модули параллельно

**Принцип:** Приложение = набор маленьких LEGO-кубиков

📖 **Детали, примеры, диаграммы:** См. ARCHITECTURE.md → "Module Architecture"

**Ваш подход к модульности:**

- [ ] Да, хочу модульную структуру (рекомендовано!)
- [ ] Нет, хочу монолитную структуру (НЕ рекомендуется для работы с ИИ)

**Selected:** [ОТВЕТИТЬ: Модульная (recommended)]

**Как будем разрабатывать модули:**
[ОТВЕТИТЬ: Например, "По одному модулю за раз. См. ARCHITECTURE.md для типичной последовательности"]

---

### 25b. Development Style

**Preferred approach:**

- [ ] Start with complete architecture planning, then code
- [ ] Iterative - build feature by feature (рекомендовано для модульной структуры)
- [ ] Rapid prototyping - get working version fast, refine later

**Selected:** [ОТВЕТИТЬ]

**Как будем разрабатывать модули:**

[ОТВЕТИТЬ: Например, "По одному модулю за раз: сначала аутентификация, потом база данных, потом каждый экран отдельно. Каждый модуль тестируем перед переходом к следующему"]

---

### 26. Testing Requirements

**Testing strategy:**

- [ ] Manual testing only (dev environment)
- [ ] Unit tests for critical functions
- [ ] Integration tests
- [ ] E2E tests (Playwright, Cypress)
- [ ] No tests for MVP

**Selected:** [ОТВЕТИТЬ]

---

## 📚 Reference Materials

### 27. Similar Projects

**Are there similar projects for reference?**

[ОТВЕТИТЬ: Например, "У меня есть проект MainChatMemory по адресу /path/to/project - можно взять оттуда паттерны"]

---

### 28. Existing Codebase

**Starting from scratch or existing code?**

- [ ] From scratch (new project)
- [ ] Existing codebase to extend
  - Location: [УКАЗАТЬ ПУТЬ]
  - What needs to be added: [ОПИСАТЬ]

**Selected:** [ОТВЕТИТЬ]

---

## 🎯 Success Criteria

### 29. MVP Definition of Done

**What makes this MVP complete?**

[ОТВЕТИТЬ: Список критериев, например:
- [ ] User can register and log in
- [ ] User can create projects
- [ ] User can add tasks to projects
- [ ] User can invite team members
- [ ] Real-time updates work
- [ ] App deployed to production
- [ ] No critical bugs]

---

### 30. Post-MVP Plans

**What comes after MVP?**

[ОТВЕТИТЬ: Например,
- "Add mobile app (React Native)"
- "Add advanced analytics"
- "Add integrations with Slack, Telegram"]

---

## 📝 Additional Notes

### 31. Special Requirements or Constraints

**Anything else AI should know?**

[ОТВЕТИТЬ: Любые дополнительные требования, ограничения, пожелания]

---

## ✅ Completion Checklist

**Before starting development, ensure:**

- [ ] All sections marked with [ОТВЕТИТЬ] are filled
- [ ] Technology stack is clearly defined
- [ ] MVP features are prioritized
- [ ] Data structure is outlined
- [ ] Reference materials are provided (if any)
- [ ] This file is committed to git
- [ ] BACKLOG.md is updated with initial features
- [ ] ARCHITECTURE.md is updated with tech stack

---

## 🤖 Взаимодействие с ИИ-агентом

> **Важно:** После заполнения этого файла начинается диалог с ИИ для уточнения деталей

### Процесс работы с ИИ:

**Шаг 1: Загрузка контекста**
- ИИ читает этот файл (PROJECT_INTAKE.md)
- Анализирует ваше видение проекта
- Может задать уточняющие вопросы

**Шаг 2: Уточнение и дополнение**
- ИИ задает вопросы по непонятным моментам
- Предлагает улучшения
- Помогает заполнить пропущенные части

**Вопросы, которые может задать ИИ:**
```
- "Вы хотите использовать Supabase или Firebase для базы данных?"
- "Нужна ли real-time синхронизация данных?"
- "Какой бюджет на хостинг в месяц?"
- "Планируете ли мобильное приложение в будущем?"
```

**Ваши ответы:**
- Если НЕ знаете - так и скажите: "Не знаю, предложи лучший вариант"
- Если ВАЖНО - укажите: "Обязательно Supabase, уже есть опыт"
- Будьте конкретны, но не бойтесь признаваться в незнании

**Шаг 3: Подтверждение понимания**
- После уточнений попросите ИИ: **"Объясни, как ты понял задачу"**
- Проверьте его понимание
- Скорректируйте если нужно

**Шаг 4: План разработки**
- ИИ предлагает план: модули, последовательность, технологии
- Вы утверждаете или корректируете
- Начинается разработка по модулям

**Команды для ИИ на старте:**

```
1. "Прочитай PROJECT_INTAKE.md и задай все уточняющие вопросы"

2. "Предложи 2-3 варианта технологического стека с обоснованием"

3. "Составь план разработки по модулям с приоритетами"

4. "Объясни, как ты понял задачу и какую архитектуру предлагаешь"
```

---

## 🚀 Next Steps After Filling This Form

**Immediate actions:**

1. **Сохрани и закоммить этот файл** в git
   ```bash
   git add PROJECT_INTAKE.md
   git commit -m "docs: Fill PROJECT_INTAKE for [project name]"
   ```

2. **Загрузи ИИ-агенту** (Claude Code, Cursor, и т.д.)
   - Скажи: "Прочитай PROJECT_INTAKE.md и SECURITY.md"
   - Скажи: "Задай уточняющие вопросы"

3. **Диалог с ИИ** - отвечай на вопросы, уточняй детали

4. **Получи план** - попроси ИИ составить план разработки

5. **Начни с первого модуля** - обычно это аутентификация или база данных

**What AI will do next:**

1. Анализирует PROJECT_INTAKE.md
2. Задаст уточняющие вопросы
3. Предложит технологический стек (если вы не выбрали)
4. Составит план разработки по модулям
5. Предложит начать с первого модуля
6. Будет итеративно разрабатывать модуль за модулем

**Your role:**

- ✅ Отвечать на вопросы ИИ
- ✅ Проверять результат каждого модуля
- ✅ Давать обратную связь: "работает" или "нужно исправить"
- ✅ Корректировать курс по ходу
- ❌ НЕ пытаться написать код сам (если не разработчик)
- ❌ НЕ пропускать тестирование модулей

---

## 📋 Template Version

**Version:** 2.0 (Enriched with "Процесс.md" methodology)
**Last Updated:** 2025-01-11
**Maintained by:** AI Agent + Project Lead

**Changelog:**
- v2.0: Added User Personas, User Flows, unique/standard functions split, modular architecture emphasis, AI interaction guide
- v1.0: Initial template

---

*This intake form ensures AI agents have all necessary context to start development efficiently*
*Based on proven AI-assisted development methodology*
*Fill once per project, update as requirements evolve*
