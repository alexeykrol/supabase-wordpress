# Project Architecture

**Project:** WordPress-Supabase Bridge
**Version:** 0.7.0
**Last Updated:** 2025-10-26

---

> **🏗️ Authoritative Source:** This is the SINGLE SOURCE OF TRUTH for:
> - WHY we chose specific technologies (technology choices, design principles)
> - HOW the system is structured (modules, layers, components)
> - Modularity philosophy and patterns
> - Design principles and architecture patterns
>
> **⚠️ NOT for operational checklists:**
> ❌ Don't store detailed implementation tasks here (→ BACKLOG.md)
> ❌ Don't store sprint checklists here (→ BACKLOG.md)
> ❌ Don't store "Phase 1: do X, Y, Z" task lists here (→ BACKLOG.md)
>
> **This file = Reference (WHY & HOW)**
> **BACKLOG.md = Action Plan (WHAT to do now)**
>
> Other files (CLAUDE.md, PROJECT_INTAKE.md) link here, don't duplicate.

<!-- MIGRATED FROM: README.md -->

## 📊 Technology Stack

### Frontend
```
- Framework: WordPress (PHP-based CMS)
- JavaScript: Vanilla JS + Supabase JS SDK (v2.x)
- CDN: Supabase JS via jsdelivr.net
- Forms: Custom HTML/JS (auth-form.html)
- Language: PHP 8.0+ (WordPress), JavaScript (Supabase SDK)
- Build Tool: None (WordPress plugin architecture)
- State Management: WordPress session + Supabase JS SDK
- UI/CSS: WordPress native + custom CSS
- Icons: None (optional via WordPress theme)
- Routing: WordPress REST API endpoints
```

### Backend & Infrastructure
```
- Database:
  - WordPress: `wp_users`, `wp_usermeta`, `wp_options` (settings storage)
  - Supabase PostgreSQL:
    - auth.users (Supabase Auth)
    - wp_registration_pairs (registration analytics)
    - wp_user_registrations (event logging)
- Authentication: Supabase Auth (JWT-based) + WordPress session
- API Type: WordPress REST API + Supabase REST API
- Security: Supabase Row-Level Security (RLS) with site_url filtering
- File Storage: WordPress Media Library (plugin doesn't handle files)
- Hosting: Any WordPress hosting (production: questtales.com)
- Dependencies: Composer (firebase/php-jwt ^6.11.1)
```

### Key Dependencies
```json
{
  "PHP (Composer)": "",
  "firebase/php-jwt": "^6.11.1 - JWT signature verification (RS256)",

  "JavaScript (CDN)": "",
  "@supabase/supabase-js": "^2.x.x - Supabase client SDK"
}
```

---

## 🗂️ Project Structure

```
supabase-bridge/
├── supabase-bridge.php              # Main plugin file (v0.7.0)
│   ├── REST API endpoints (callback, logout)
│   ├── JWT verification logic (JWKS)
│   ├── WordPress user sync (with distributed lock v0.4.1)
│   ├── Security headers & rate limiting
│   ├── Settings page (v0.4.0 - encrypted credentials, thank you page selector)
│   ├── Registration Pairs UI (v0.7.0 - CRUD for pairs)
│   ├── Input validation functions (v0.7.0 - sb_validate_*)
│   └── Supabase sync functions (v0.7.0 - sb_sync_*, sb_log_*)
│
├── composer.json                    # PHP dependencies
├── composer.lock                    # Locked versions
├── vendor/                          # Composer autoload
│   └── firebase/php-jwt/            # JWT library
│
├── auth-form.html                   # Auth form with [supabase_auth_form] shortcode (v0.4.0)
│   ├── Google OAuth button
│   ├── Facebook OAuth button
│   ├── Magic Link (6-digit code)
│   └── Dynamic pair injection (v0.7.0)
│
├── supabase-tables.sql              # Supabase database schema (v0.7.0)
│   ├── wp_registration_pairs table
│   └── wp_user_registrations table
│
├── SECURITY_RLS_POLICIES_FINAL.sql  # RLS policies with site_url filtering (v0.7.0)
│
├── build-release.sh                 # Release automation script (v0.7.0)
│
├── PRODUCTION_SETUP.md              # Cloudflare/AIOS/LiteSpeed config guide (v0.7.0)
├── QUICK_SETUP_CHECKLIST.md         # 1-page deployment guide (v0.7.0)
├── SECURITY_ROLLBACK_SUMMARY.md     # Security architecture explanation (v0.7.0)
├── CLAUDE.md                        # Project context for Claude Code
│
├── Init/                            # Claude Code Starter framework
│   ├── PROJECT_INTAKE.md
│   ├── ARCHITECTURE.md (this file)
│   ├── SECURITY.md
│   ├── BACKLOG.md
│   ├── PROJECT_SNAPSHOT.md
│   ├── WORKFLOW.md
│   └── AGENTS.md
│
├── .gitignore                       # Git ignore rules
├── LICENSE                          # MIT License
└── README.md                        # Production documentation
```

---

## 🏗️ Core Architecture Decisions

### 1. JWT Verification on Server-Side (не доверять фронту)

**Decision:** Все JWT токены проверяются сервером через JWKS (публичные ключи Supabase)
**Reasoning:**
- ✅ Безопасность: Клиент не может подделать токен без приватного ключа Supabase
- ✅ RS256 asymmetric cryptography: публичный ключ для верификации, приватный хранится в Supabase
- ✅ JWKS caching (1 hour): снижает нагрузку на Supabase endpoint
- ✅ Zero trust: фронтенд может быть скомпрометирован, но сервер всегда проверяет

**Alternatives considered:**
- ❌ Доверять токену без проверки - небезопасно, легко подделать
- ❌ HS256 (symmetric key) - требует shared secret на клиенте (утечка)

**Implementation:**
```php
// Fetch JWKS from Supabase
$jwks = wp_cache_get($cache_key);
if (!$jwks) {
    $jwks = file_get_contents($jwks_url);
    wp_cache_set($cache_key, $jwks, '', 3600); // 1 hour
}
// Verify JWT signature
$decoded = JWT::decode($access_token, JWK::parseKeySet($jwks), ['RS256']);
```

---

### 2. Mirror WordPress Users (синхронизация Supabase ↔ WP)

**Decision:** Создаем зеркальные учетные записи в `wp_users` после Supabase auth
**Reasoning:**
- ✅ WordPress плагины работают с `wp_users`: членство, роли, контент
- ✅ Supabase - source of truth для аутентификации
- ✅ WordPress - source of truth для ролей и пермишнов
- ✅ `supabase_user_id` в `wp_usermeta` для связи

**Alternatives considered:**
- ❌ Только Supabase users - WP плагины не видят пользователей
- ❌ Только WP users - теряем преимущества Supabase Auth (OAuth, RLS)

**Implementation:**
```php
$user = get_user_by('email', $email);
if (!$user) {
    $user_id = wp_insert_user([
        'user_email' => $email,
        'user_login' => $email,
        'role' => 'subscriber',
    ]);
    update_user_meta($user_id, 'supabase_user_id', $sub);
}
wp_set_auth_cookie($user_id);
```

---

### 3. Environment Variables в wp-config.php (не .env)

**Decision:** Конфигурация через `putenv()` в `wp-config.php`
**Reasoning:**
- ✅ WordPress convention: все секреты в `wp-config.php`
- ✅ Нет зависимостей от `.env` парсеров (vlucas/phpdotenv)
- ✅ `.gitignore` уже защищает `wp-config.php`
- ✅ Простота для пользователей плагина

**Alternatives considered:**
- ❌ `.env` файл - дополнительная зависимость, не WordPress-way
- ❌ Hardcode в плагине - небезопасно, нельзя коммитить

**Implementation:**
```php
// wp-config.php
putenv('SUPABASE_URL=https://xxx.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGci...');
```

---

### 4. Input Validation Functions (v0.7.0 - Defense Layer 1)

**Decision:** Централизованные функции валидации для всех пользовательских вводов
**Reasoning:**
- ✅ Предотвращает SQL injection, XSS, path traversal attacks
- ✅ Единообразная валидация во всём проекте
- ✅ Fail-safe design - invalid data rejected с логированием атак
- ✅ Defense in depth - первый уровень защиты перед Supabase RLS

**Alternatives considered:**
- ❌ Валидация только на клиенте - легко обойти
- ❌ Без валидации - критическая уязвимость безопасности

**Implementation:**
```php
// Email validation (RFC 5322 + length limits)
function sb_validate_email($email) {
  if (!is_email($email)) return false;
  if (strlen($email) > 254) return false;
  return sanitize_email($email);
}

// URL path validation (prevents path traversal)
function sb_validate_url_path($path) {
  if (strpos($path, '..') !== false) return false;  // No ../../etc/passwd
  if (preg_match('/^[a-z]+:/i', $path)) return false; // No file://
  return esc_url_raw($path);
}

// UUID v4 validation (format checking)
function sb_validate_uuid($uuid) {
  $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
  if (!preg_match($pattern, $uuid)) return false;
  return $uuid;
}

// Site URL validation (protocol enforcement)
function sb_validate_site_url($url) {
  if (!preg_match('/^https?:\/\//', $url)) return false; // Only http/https
  return esc_url_raw($url);
}
```

---

### 5. Supabase RLS Policies (v0.7.0 - Defense Layer 2)

**Decision:** Row-Level Security с фильтрацией по site_url для мульти-сайтовой изоляции
**Reasoning:**
- ✅ PostgreSQL native security - невозможно обойти через API
- ✅ Cross-site isolation - Site A не видит данные Site B
- ✅ Работает с Anon Key - не нужен Service Role Key
- ✅ Automatic enforcement - разработчик не может забыть проверку

**Alternatives considered:**
- ❌ Service Role Key - критическая уязвимость безопасности (если WordPress скомпрометирован, полный доступ к Supabase)
- ❌ Без RLS - любой site может читать/писать чужие данные

**Implementation:**
```sql
-- RLS Policy for wp_registration_pairs
CREATE POLICY "Allow operations only for matching site_url"
ON wp_registration_pairs
FOR ALL
USING (
  site_url = current_setting('request.headers', true)::json->>'x-site-url'
)
WITH CHECK (
  site_url = current_setting('request.headers', true)::json->>'x-site-url'
);

-- WordPress sends x-site-url header on every request
wp_remote_post($endpoint, [
  'headers' => [
    'apikey' => $anon_key,
    'x-site-url' => get_site_url(), // RLS filter
  ]
]);
```

---

### 6. Multi-Site Architecture (v0.7.0)

**Decision:** site_url column для отслеживания владельца записи
**Reasoning:**
- ✅ Один Supabase проект для нескольких WordPress сайтов пользователя
- ✅ Простая архитектура - один столбец вместо сложной схемы tenancy
- ✅ Совместима с RLS policies - прямая фильтрация
- ✅ Не коммерческий SaaS - только собственные сайты пользователя

**Alternatives considered:**
- ❌ Отдельный Supabase проект на каждый сайт - дорого и неудобно
- ❌ Shared table без site_url - уязвимость безопасности

**Data structure:**
```sql
CREATE TABLE wp_registration_pairs (
  id UUID PRIMARY KEY,
  site_url TEXT NOT NULL,              -- https://site1.com
  registration_page_url TEXT NOT NULL, -- /register/
  thankyou_page_url TEXT NOT NULL,     -- /thanks/
  created_at TIMESTAMP DEFAULT NOW()
);

-- RLS policy automatically filters by site_url
```

---

### 7. Registration Pairs Analytics (v0.7.0)

**Decision:** Отслеживание какая страница регистрации ведет на какую thank you page
**Reasoning:**
- ✅ Аналитика конверсии - какие страницы регистрации эффективнее
- ✅ A/B testing support - разные thank you pages для разных лендингов
- ✅ Динамические редиректы - JavaScript читает пары из базы
- ✅ Централизованное управление - изменения через WordPress Admin

**Alternatives considered:**
- ❌ Hardcode redirects в JavaScript - нужно редактировать код для каждого изменения
- ❌ Без аналитики - нет данных для оптимизации

**Data flow:**
```
1. WordPress Admin создает pair: /register-a/ → /thanks-a/
2. Pair синхронизируется в Supabase (sb_sync_pair_to_supabase)
3. auth-form.html загружает пары через REST API (/wp-json/supabase-bridge/v1/registration-pairs)
4. JavaScript инжектит пары в AUTH_CONFIG.thankYouPages
5. После регистрации: редирект на /thanks-a/ (page-specific)
6. Event логируется в wp_user_registrations
```

---

## 🔧 Key Services & Components

### [Сервис/Компонент #1]
**Purpose:** [Назначение]
**Location:** `[путь к файлу]`

**Key methods/features:**
```typescript
- method1() → описание
- method2() → описание
- feature1 → описание
```

**Architectural features:**
- [Особенность 1]
- [Особенность 2]

**Example usage:**
```typescript
// Пример использования
```

---

### Template для документирования сервисов:

```markdown
### [Service Name]
**Purpose:** [Что делает]
**Location:** `[file path]`

**Key methods:**
- method() → [описание]

**Features:**
- [Особенность]

**Example:**
[код]
```

---

## 📡 Data Flow & Integration Patterns

### 1. [User Flow #1 - например "User Login"]
```
User Action →
├── Step 1
├── Step 2
├── Step 3
└── Final Result
```

**Detailed flow:**
1. [Шаг 1 детально]
2. [Шаг 2 детально]
3. [Шаг 3 детально]

### 2. [User Flow #2]
```
[Диаграмма потока]
```

---

### Template для документирования потоков:

```markdown
### N. [Flow Name]
[ASCII диаграмма]

**Detailed:**
1. [Шаг]
2. [Шаг]
```

---

## 🎯 Development Standards

### Code Organization
- [ЗАПОЛНИТЬ: стандарты организации кода]
- **1 component = 1 file** (если применимо)
- **Services in lib/** for reusability
- **TypeScript strict mode** - no `any` (except justified exceptions)
- **Naming:** [соглашения по именованию]

### Database Patterns
[ЗАПОЛНИТЬ: если есть база данных]
- **Primary Keys:** [UUID/Auto-increment/etc]
- **Relationships:** [как организованы связи]
- **Migrations:** [как применяются миграции]
- **Security:** [RLS/Permissions/etc]

### Error Handling
- **Try/catch** in async functions
- **User-friendly** error messages (на русском/английском)
- **Console logging** for debugging
- **Fallback states** in UI

### Performance Optimizations
- [ЗАПОЛНИТЬ: специфичные для проекта оптимизации]
- **[Оптимизация 1]**
- **[Оптимизация 2]**
- **[Оптимизация 3]**

---

## 🧩 Module Architecture

> **Философия:** Модульная архитектура - основа эффективной разработки с ИИ-агентами

### Зачем нужна модульность?

**Критические преимущества для работы с ИИ:**

1. **Экономия токенов и денег**
   - ИИ загружает только нужный модуль (100-200 строк)
   - Вместо всего проекта (1000+ строк)
   - Запросы выполняются быстрее и дешевле

2. **Простота разработки и тестирования**
   - Каждый модуль = отдельная задача
   - Легко проверить работу модуля изолированно
   - ИИ лучше понимает узкие задачи

3. **Параллельная работа**
   - Можно разрабатывать разные модули одновременно
   - Ускоряет итерацию

4. **Управляемость проекта**
   - Легко найти и исправить ошибки
   - Понятная структура для команды
   - Простое добавление новых функций

### Принцип модульности

**Приложение = Набор маленьких кубиков (LEGO)**

```
┌─────────────────────────────────────────────┐
│           Приложение                        │
├─────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐ │
│  │   Auth   │  │ Database │  │   API    │ │
│  │  Module  │  │  Module  │  │  Module  │ │
│  └──────────┘  └──────────┘  └──────────┘ │
│                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐ │
│  │  Screen  │  │  Screen  │  │  Screen  │ │
│  │    1     │  │    2     │  │    3     │ │
│  └──────────┘  └──────────┘  └──────────┘ │
│                                             │
│  ┌──────────┐  ┌──────────┐                │
│  │ Business │  │ Business │                │
│  │  Logic 1 │  │  Logic 2 │                │
│  └──────────┘  └──────────┘                │
└─────────────────────────────────────────────┘
```

Каждый модуль:
- Решает **одну узкую задачу**
- Имеет **чёткий вход и выход**
- Работает как **"черный ящик"** для других модулей
- Может быть **протестирован отдельно**

---

### Типичные модули проекта

[ЗАПОЛНИТЬ по мере разработки, но вот типичная структура:]

#### 1. Модуль аутентификации
**Purpose:** Регистрация, вход, восстановление пароля
**Location:** `src/lib/auth/` или `src/features/auth/`
**Независимость:** Полностью самостоятельный, не зависит от бизнес-логики
**Интеграция:** Через Auth Provider или Context

**Компоненты:**
- LoginForm
- RegisterForm
- PasswordResetForm
- AuthProvider

---

#### 2. Модуль базы данных
**Purpose:** Работа с базой данных
**Location:** `src/lib/db/` или `src/lib/supabase/`
**Независимость:** Изолированная работа с БД
**Интеграция:** Через клиент (Supabase/Firebase/Prisma)

**Функции:**
- Подключение к БД
- CRUD операции
- Queries и mutations

---

#### 3. Модули экранов/страниц
**Purpose:** Отдельный экран = отдельный модуль
**Location:** `src/pages/` или `src/app/`
**Независимость:** Каждая страница независима

**Примеры:**
- HomePage
- DashboardPage
- SettingsPage
- ProfilePage

---

#### 4. Модули бизнес-логики
**Purpose:** Уникальная логика вашего приложения
**Location:** `src/features/` или `src/lib/business/`

**Примеры:**
- PaymentProcessor
- BookingSystem
- RatingCalculator
- NotificationManager

---

#### 5. Backend/API модуль
**Purpose:** Связь между фронтендом и базой данных
**Location:** `src/app/api/` или `src/lib/api/`
**Независимость:** Самостоятельный слой между UI и DB

**Функции:**
- API routes/endpoints
- Business logic на сервере
- Валидация данных

---

### Процесс разработки по модулям

**Последовательность (рекомендуется):**

1. **База данных** → Схема, таблицы, связи
2. **Аутентификация** → Регистрация, вход
3. **Backend/API** → Эндпоинты для работы с данными
4. **Экраны по одному** → HomePage → Dashboard → Settings...
5. **Бизнес-логика** → Уникальные функции вашего приложения

**Правило:** Один модуль → Тестирование → Следующий модуль

---

### Пример модуля (Документация)

### [Module Name - например "User Authentication"]
**Purpose:** [Что делает модуль]

**Location:** `[путь к файлам модуля]`

**Components:**
- `Component1.tsx` - [описание]
- `Component2.tsx` - [описание]
- `service.ts` - [логика модуля]

**Dependencies:**
- [Внешние зависимости: библиотеки, сервисы]

**Integration with other modules:**
- [Как этот модуль взаимодействует с другими]

**Input/Output:**
```typescript
// Вход
interface ModuleInput {
  // ...
}

// Выход
interface ModuleOutput {
  // ...
}
```

**Example usage:**
```typescript
// Пример использования модуля
import { useAuth } from './auth-module';

const { user, login, logout } = useAuth();
```

**Testing:**
- [Как тестируется модуль]

---

### Ваши модули проекта

[ЗАПОЛНИТЬ по мере разработки - добавляйте каждый модуль сюда]

#### Module 1: [Name]
[Документация]

#### Module 2: [Name]
[Документация]

---

## 🗄️ Database Schema

[ЗАПОЛНИТЬ: структура базы данных]

### Tables Overview
```
[table_name_1]
├── id: uuid (PK)
├── field1: type
└── field2: type

[table_name_2]
├── id: uuid (PK)
└── foreign_key: uuid (FK → table_name_1)
```

### Relationships
- [Описание связей между таблицами]

### Indexes
- [Какие индексы созданы и зачем]

### Security
- [RLS policies или другие меры безопасности]

---

## 🔐 Security Architecture

[ЗАПОЛНИТЬ: меры безопасности]

### Authentication
- **Method:** [OAuth/JWT/Session/etc]
- **Provider:** [Auth0/Supabase/Custom/etc]
- **Flow:** [Описание процесса аутентификации]

### Authorization
- **Model:** [RBAC/ABAC/Custom/etc]
- **Implementation:** [Как проверяются права доступа]

### Data Protection
- **At Rest:** [Шифрование данных]
- **In Transit:** [HTTPS/TLS]
- **API Keys:** [Как хранятся]
- **Sensitive Data:** [Как обрабатываются]

### Security Headers
```javascript
// Пример настройки security headers
```

---

## 🚀 Deployment Architecture

[ЗАПОЛНИТЬ: архитектура деплоя]

### Environments
- **Development:** [localhost/dev server]
- **Staging:** [URL/описание]
- **Production:** [URL/описание]

### CI/CD Pipeline
```
[Описание процесса деплоя]
Code → Tests → Build → Deploy
```

### Environment Variables
```env
# Required
VAR_NAME=description

# Optional
OPTIONAL_VAR=description
```

---

## 📊 State Management Architecture

[ЗАПОЛНИТЬ: как организовано управление состоянием]

### Global State
```typescript
// Структура глобального состояния
interface AppState {
  [ЗАПОЛНИТЬ]
}
```

### Local State
[Когда использовать локальное состояние]

### State Update Patterns
```typescript
// Примеры паттернов обновления состояния
```

---

## 🔄 Evolution & Migration Strategy

### Approach to Changes
1. **Document decision** in this file
2. **Database changes** → Create migration script
3. **Backward compatibility** when possible
4. **Feature flags** for experimental functionality

### Migration Pattern
```
Planning → Implementation → Testing → Documentation → Deployment
    ↓           ↓              ↓           ↓            ↓
ARCHITECTURE  Code+Tests    Manual QA   Update docs   Git push
```

### Version History
- **[VERSION]** - [DATE] - [Changes summary]
- [Добавляйте по мере развития]

---

## 🧪 Module Testing - Изолированное тестирование

> **Зачем:** Каждый модуль должен работать независимо от остальных. Это экономит время и токены при разработке с AI.

### Принцип модульного тестирования:

**❌ Плохо:**
```
Тестирую весь проект сразу →
Непонятно где ошибка →
AI загружает весь код →
Долго, дорого
```

**✅ Хорошо:**
```
Тестирую один модуль →
Ошибка локализована →
AI видит только 1 модуль →
Быстро, дёшево
```

### Как тестировать модуль изолированно:

#### Шаг 1: Создать тестовую страницу

```typescript
// src/test/[ModuleName]Test.tsx
import { [ModuleName] } from '../modules/[module-name]/[ModuleName]';

function [ModuleName]Test() {
  return (
    <div className="p-8">
      <h1>Testing: [ModuleName]</h1>
      <[ModuleName] />
    </div>
  );
}

export default [ModuleName]Test;
```

#### Шаг 2: Временно подключить в App

```typescript
// src/App.tsx (временно)
import [ModuleName]Test from './test/[ModuleName]Test';

function App() {
  return <[ModuleName]Test />;
}
```

#### Шаг 3: Проверить функциональность

**Чеклист для тестирования модуля:**
- [ ] Модуль отображается без ошибок
- [ ] Основной функционал работает
- [ ] Edge cases обработаны
- [ ] Error states показываются правильно
- [ ] Loading states работают
- [ ] UI responsive (если применимо)

#### Шаг 4: Вернуть App к исходному виду

После тестирования:
- Восстановить `App.tsx`
- Удалить test файл или оставить для документации
- Сделать commit с результатами

### Критерии готовности модуля:

Модуль считается **готовым** когда:

#### Базовые критерии:
- [ ] Все файлы модуля созданы (component, hook, types)
- [ ] Код компилируется без ошибок TypeScript
- [ ] Нет ESLint warnings (или обоснованы)
- [ ] Модуль протестирован изолированно

#### Функциональные критерии:
- [ ] Основной функционал реализован
- [ ] Edge cases обработаны
- [ ] Error handling добавлен
- [ ] Loading states реализованы
- [ ] Валидация данных работает

#### Документация:
- [ ] Интерфейс модуля задокументирован
- [ ] Зависимости указаны
- [ ] Примеры использования есть (если нужно)

#### Мета-файлы:
- [ ] BACKLOG.md — задачи отмечены ✅
- [ ] PROJECT_SNAPSHOT.md — модуль добавлен
- [ ] PROCESS.md — чеклист выполнен

### Граф зависимостей модулей:

**Важно:** Разрабатывай модули в правильном порядке!

```
Независимые модули (сначала):
├─ UI Components (Button, Input, etc.)
├─ Utility Modules (encryption, validation)
└─ API Clients (без UI)

Зависимые модули (потом):
├─ Feature Modules
│   └─ depends on: UI Components, Utilities
└─ Integration Modules
    └─ depends on: Feature Modules
```

**Как определить порядок:**
1. Нарисуй граф зависимостей
2. Начни с модулей без входящих стрелок
3. Переходи к следующему уровню только после готовности предыдущего

### Экономия токенов через модульное тестирование:

**Пример:** Проект с 5 модулями

**Без изоляции:**
```
Тестируешь весь проект:
→ AI читает все 5 модулей (2000 строк)
→ ~8000 токенов × 3 итерации = 24k токенов
→ Стоимость: ~$0.24
```

**С изоляцией:**
```
Тестируешь каждый модуль отдельно:
→ AI читает 1 модуль (400 строк)
→ ~1500 токенов × 3 итерации × 5 модулей = 22.5k токенов
→ НО! Меньше итераций (быстрее находишь баги)
→ Реально: ~1500 × 2 × 5 = 15k токенов
→ Стоимость: ~$0.15

Экономия: ~40%! + Быстрее разработка!
```

### Template для документирования тестов:

```markdown
## Тестирование [Module Name]

### Тест 1: [Название функциональности]
- **Действие:** [что делаем]
- **Ожидаемый результат:** [что должно произойти]
- **Статус:** [x] Passed / [ ] Failed
- **Баги:** [если найдены]

### Тест 2: [Edge case]
- **Действие:** [что делаем]
- **Ожидаемый результат:** [что должно произойти]
- **Статус:** [x] Passed / [ ] Failed

### Итог:
- ✅ Модуль готов к интеграции
- ⏸️ Требуются доработки: [список]
```

---

## 📚 Related Documentation

- **BACKLOG.md** - Current implementation status and roadmap
- **PROJECT_SNAPSHOT.md** - Current project state snapshot
- **PROCESS.md** - Documentation update process after each phase
- **DEVELOPMENT_PLAN_TEMPLATE.md** - Planning methodology
- **AGENTS.md** - AI assistant working instructions
- **WORKFLOW.md** - Development processes and sprint workflow
- **README.md** - User-facing project information

---

## 📝 Architecture Decision Records (ADR)

[Опционально: для документирования важных архитектурных решений]

### ADR-001: [Decision Title]
**Date:** [DATE]
**Status:** [Accepted/Deprecated/Superseded]
**Context:** [Почему нужно было принять решение]
**Decision:** [Что решили]
**Consequences:** [К чему это привело]

---

## 🎨 Design Patterns Used

[ЗАПОЛНИТЬ: какие паттерны проектирования используются]

- **[Pattern Name]** - [Где используется и зачем]
- Примеры:
  - **Repository Pattern** - в `lib/repositories/`
  - **Factory Pattern** - в `lib/factories/`
  - **Observer Pattern** - в state management

---

## 📝 Notes for Customization

Когда заполняете этот файл для конкретного проекта:

1. **Замените все [ЗАПОЛНИТЬ]** на актуальную информацию
2. **Удалите секции** которые не применимы к вашему проекту
3. **Добавьте новые секции** специфичные для вашего проекта
4. **Обновляйте документ** при каждом архитектурном изменении
5. **Используйте диаграммы** где нужно (Mermaid/ASCII)
6. **Удалите эту секцию** после первичного заполнения

---

*This document maintained in current state for effective development*
*Last updated: [DATE]*
