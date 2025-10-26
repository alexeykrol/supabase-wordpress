# AI Agent Instructions

**Project:** [PROJECT_NAME]
**Purpose:** Meta-instructions for effective AI-assisted development
**Created:** [DATE]
**Last Updated:** [DATE]

> **Note:** This file is optimized for AI assistants (Claude Code, Cursor, Copilot, etc.) working with this codebase.

---

## 🎯 Quick Start for AI Agents

### Required Reading (in order):
1. **SECURITY.md** - Security requirements and practices (READ FIRST!)
2. **ARCHITECTURE.md** - System architecture and technical decisions
3. **BACKLOG.md** - Current implementation status and roadmap (SINGLE SOURCE OF TRUTH)
4. **README.md** - User-facing project information
5. **WORKFLOW.md** - Development processes and sprint workflow

### ⚠️ SINGLE SOURCE OF TRUTH:
**BACKLOG.md** is the ONLY authoritative source for:
- Current implementation status
- Feature priorities
- Development roadmap

### Key Files Quick Reference:
```bash
# Architecture & Planning
ARCHITECTURE.md                # System design and patterns
BACKLOG.md                     # Implementation status (SINGLE SOURCE OF TRUTH)
WORKFLOW.md                    # Development processes
README.md                      # User-facing documentation
CLAUDE.md                      # Auto-loaded context for Claude Code

# Core Application
[ЗАПОЛНИТЬ: основные файлы проекта]
# Например:
# src/store/useStore.ts        # State management
# src/lib/api.ts               # API service
# src/components/Main.tsx      # Main component

# Configuration
Makefile                       # Standard commands (make dev, make build, etc)
.env.example                   # Environment variables template
.claude/commands/              # Custom slash commands for Claude Code
.claude/settings.json          # Claude Code permissions
.claudeignore                  # Files to ignore in AI context
```

### 📦 Standard Commands (Makefile):
Проект использует Makefile для стандартизации команд:

```bash
# Development
make dev          # Запустить development сервер
make build        # Собрать для production
make start        # Запустить production сервер

# Quality Checks
make lint         # Проверить код линтером
make fix-lint     # Автоматически исправить линтер
make typecheck    # Проверить TypeScript типы
make test         # Запустить тесты
make test-watch   # Тесты в watch режиме

# Security & Dependencies
make security     # npm audit проверка
make security-fix # Автоматически исправить уязвимости
make audit        # Полная проверка (lint+typecheck+test+security)

# Database (когда будет использоваться)
make db-migrate   # Применить миграции БД
make db-reset     # Сбросить БД
make db-seed      # Заполнить тестовыми данными

# Utility
make install      # Установить зависимости
make clean        # Очистить build артефакты
make reinstall    # Переустановить зависимости
make doctor       # Диагностика окружения
make help         # Показать все команды

# Pre-commit/push checks
make pre-commit   # lint + typecheck
make pre-push     # audit + build
```

**ВАЖНО:** Всегда используй `make <command>` вместо прямого `npm run <command>`
- Makefile контролирует что именно запускается
- Проще для Claude Code автоматизировать (через .claude/settings.json)
- Единообразие команд между проектами

---

## 📚 Technology Stack

### Frontend
[ЗАПОЛНИТЬ: Frontend технологии]
```
- Framework: [React/Vue/Angular/Next.js/etc]
- Language: [TypeScript/JavaScript]
- State Management: [Redux/Zustand/Context/etc]
- Styling: [Tailwind/CSS Modules/Styled Components/etc]
- Build Tool: [Vite/Webpack/Next.js/etc]
```

### Backend & Infrastructure
[ЗАПОЛНИТЬ: Backend технологии]
```
- Database: [PostgreSQL/MySQL/MongoDB/etc]
- Authentication: [Supabase Auth/Auth0/Firebase/etc]
- API: [REST/GraphQL/tRPC/etc]
- Hosting: [Vercel/AWS/etc]
```

### Key Dependencies
```json
{
  "[ЗАПОЛНИТЬ]": "версия и назначение",
}
```

---

## 🚫 NEVER DO

### Code & Architecture
- ❌ **[ЗАПОЛНИТЬ: специфичные для проекта правила]**
- ❌ **Update database structure** without migration script
- ❌ **Use `any` type** without explicit justification (TypeScript projects)
- ❌ **Create multiple components in one file** (если используется компонентный подход)
- ❌ **Duplicate API calls** (especially in polling loops)
- ❌ **Ignore security best practices** (SQL injection, XSS, CSRF)

### Process & Documentation
- ❌ **Skip documentation updates** after sprint completion
- ❌ **Modify BACKLOG.md** without completing actual implementation
- ❌ **Create commits** without meaningful messages
- ❌ **Update dependencies** without testing
- ❌ **Push to main/master** without review (if team workflow requires it)

### 🔐 Security (CRITICAL - READ SECURITY.md FIRST!)

**📖 ПОЛНАЯ ПОЛИТИКА БЕЗОПАСНОСТИ:** SECURITY.md

**ДО начала любой задачи с кодом:**
1. Прочитай SECURITY.md → Stage 1 (Planning)
2. Следуй чеклистам для текущей стадии
3. В случае сомнений → `/security` для audit

**Ключевые принципы (все детали в SECURITY.md):**
- 🔐 Secrets management → SECURITY.md "Environment Variables"
- 🔐 Input validation → SECURITY.md Stage 3
- 🔐 SQL injection prevention → SECURITY.md "Database Security"
- 🔐 XSS prevention → SECURITY.md "Output Sanitization"

**AGENTS.md содержит только project-specific security patterns!**
См. "Project Security Patterns" section ниже для специфичных правил этого проекта.

---

## ✅ ALWAYS DO

### Before Making Changes
- ✅ **Read ARCHITECTURE.md** for architectural decisions
- ✅ **Check BACKLOG.md** for current status
- ✅ **Review related documentation** before making changes
- ✅ **Test in development** environment first

### During Development
- ✅ **Use existing patterns** from codebase
- ✅ **Follow TypeScript strict mode** (type everything properly)
- ✅ **Write migration scripts** for database changes
- ✅ **Update types** after schema changes
- ✅ **Test thoroughly** before marking tasks as complete
- ✅ **Use TodoWrite tool** to track progress

### 🔐 Security (Every Single Time)

**📖 ИСПОЛЬЗУЙ CHECKLISTS ИЗ SECURITY.md:**
- См. SECURITY.md → Stage-specific checklists
- См. SECURITY.md → "Security Requirements by Stage"

**Быстрая проверка:**
```bash
/security  # Запустить AI-guided security audit
make security  # npm audit проверка
```

### After Completion
- ✅ **Update BACKLOG.md** with implementation status
- ✅ **Update ARCHITECTURE.md** if architectural changes made
- ✅ **Update AGENTS.md** if new patterns/rules discovered
- ✅ **Update README.md** if user-facing changes
- ✅ **Create meaningful git commit** (see WORKFLOW.md for template)
- ✅ **Mark all TodoWrite tasks** as completed

---

## 🔧 Standard Workflows

### Database Changes
```
1. Analysis → Read current database schema/documentation
2. Planning → Create migration script
3. Testing → Apply migration in development
4. Type Update → Update TypeScript types (if applicable)
5. Documentation → Update ARCHITECTURE.md or database docs
6. Code → Implement feature using new schema
7. Sprint Completion → Update BACKLOG.md and AGENTS.md
```

### New Feature Development
```
1. Read ARCHITECTURE.md (understand patterns)
2. Check BACKLOG.md (current status)
3. Plan with TodoWrite tool
4. Implement following existing patterns
5. Test thoroughly
6. Update documentation (BACKLOG.md, AGENTS.md, README.md)
7. Create sprint completion commit
```

### Bug Fix
```
1. Diagnose root cause
2. Check if similar issue exists in AGENTS.md "Common Issues"
3. Fix following existing patterns
4. Test fix
5. Add to "Common Issues" section if applicable
6. Update version in README.md if necessary
```

### 🔐 Security Review (Before Every Deploy)

**📖 ИСПОЛЬЗУЙ CHECKLIST ИЗ SECURITY.md:**
- См. SECURITY.md → Stage 5 (Pre-Deployment)
- См. SECURITY.md → "Security Sign-Off Template"

**Или используй автоматизацию:**
```bash
/security  # Запустить AI-guided security audit
```

---

## 🏗️ Architectural Patterns

[ЗАПОЛНИТЬ: Специфичные для проекта архитектурные решения]

### Примеры паттернов для заполнения:

#### State Management Pattern
**Decision:** [Redux/Zustand/Context/etc]
**Reason:**
- [Причина 1]
- [Причина 2]

**Example:**
```typescript
// Пример использования
```

#### API Integration Pattern
**Decision:** [Как организованы API запросы]
**Reason:**
- [Причина 1]

#### File/Data Structure Pattern
**Decision:** [Как организованы данные]
**Reason:**
- [Причина 1]

---

## 🔐 Project Security Patterns

> **Важно:** Этот раздел для СПЕЦИФИЧНЫХ для ЭТОГО проекта security patterns.
> Общие правила безопасности см. SECURITY.md

### Pattern 1: Always Validate Before Supabase Sync (v0.7.0)
**Context:** When syncing Registration Pairs or User Registrations to Supabase
**Rule:** ALWAYS validate ALL inputs using sb_validate_* functions before wp_remote_post()
**Reason:** Defense Layer 1 - prevents injection attacks before data reaches Supabase RLS policies
**Example:**
```php
// ✅ CORRECT - Validate before sync
$validated_email = sb_validate_email($pair_data['email']);
$validated_url = sb_validate_url_path($pair_data['registration_page_url']);
if (!$validated_email || !$validated_url) {
  error_log('Validation failed - possible injection attempt');
  return false;
}
// Now safe to sync to Supabase
sb_sync_pair_to_supabase($validated_data);

// ❌ WRONG - Direct sync without validation
wp_remote_post($endpoint, ['body' => json_encode($pair_data)]); // VULNERABLE!
```

---

### Pattern 2: Always Include x-site-url Header for RLS (v0.7.0)
**Context:** When making ANY request to Supabase REST API
**Rule:** MUST include x-site-url header with validated site_url for RLS filtering
**Reason:** Supabase RLS policies require this header to filter records by site
**Example:**
```php
// ✅ CORRECT - Include x-site-url for RLS
$validated_site_url = sb_validate_site_url(get_site_url());
$response = wp_remote_post($endpoint, [
  'headers' => [
    'apikey' => $anon_key,
    'Authorization' => 'Bearer ' . $anon_key,
    'x-site-url' => $validated_site_url, // REQUIRED for RLS
  ],
  'body' => json_encode($data)
]);

// ❌ WRONG - Missing x-site-url header
$response = wp_remote_post($endpoint, [
  'headers' => ['apikey' => $anon_key],
  'body' => json_encode($data)
]); // RLS will return HTTP 401!
```

---

### Pattern 3: Never Use Service Role Key (v0.7.0 Security Decision)
**Context:** When configuring Supabase credentials
**Rule:** ONLY use Anon (Public) Key - NEVER Service Role Key
**Reason:** If WordPress is compromised, attacker gets full Supabase access with Service Key
**Example:**
```php
// ✅ CORRECT - Anon Key only (v0.7.0 architecture)
$anon_key = sb_cfg('SUPABASE_ANON_KEY');
$response = wp_remote_post($endpoint, [
  'headers' => ['apikey' => $anon_key]
]);
// Security relies on: Anon Key + RLS Policies + Input Validation

// ❌ WRONG - Service Role Key (rejected in v0.7.0)
$service_key = sb_cfg('SUPABASE_SERVICE_ROLE_KEY'); // NEVER DO THIS!
// If WordPress hacked = full Supabase access
```

---

### Pattern 4: Distributed Lock for Concurrent User Creation (v0.4.1)
**Context:** When creating WordPress users during authentication callback
**Rule:** Use WordPress Transient API distributed lock to prevent race conditions
**Reason:** Two concurrent auth requests can create duplicate users without lock
**Example:**
```php
// ✅ CORRECT - Use distributed lock
$lock_key = 'sb_user_creation_' . $supabase_user_id;
if (!wp_cache_add($lock_key, time(), '', 5)) {
  usleep(100000); // Wait 100ms for other process
  $user = get_user_by('meta', 'supabase_user_id', $supabase_user_id);
  if ($user) return $user; // Other process created user
}
// Now safe to create user
$user_id = wp_insert_user($user_data);
wp_cache_delete($lock_key);

// ❌ WRONG - No lock (can create duplicates)
$user_id = wp_insert_user($user_data); // RACE CONDITION!
```

---

## 🐛 Common Issues & Solutions

### Issue: HTTP 401 When Syncing to Supabase (v0.7.0)
**Symptom:** wp_remote_post() to Supabase returns HTTP 401 Unauthorized, sync fails
**Root Cause:** RLS policies require x-site-url header but it's missing from request
**Solution:** Add x-site-url header to ALL Supabase REST API requests
**File:** `supabase-bridge.php` (sb_sync_pair_to_supabase, sb_delete_pair_from_supabase functions)
**Fix:**
```php
$response = wp_remote_post($endpoint, [
  'headers' => [
    'apikey' => $anon_key,
    'x-site-url' => sb_validate_site_url(get_site_url()), // ADD THIS!
  ]
]);
```

---

### Issue: User Duplication During Concurrent Auth (v0.4.1)
**Symptom:** Two users created with same email during Magic Link/OAuth authentication
**Root Cause:** Race condition - two PHP processes (different PIDs) call wp_insert_user() simultaneously
**Solution:** Implement distributed lock using WordPress Transient API + UUID-first checking
**File:** `supabase-bridge.php` (sb_handle_callback function, lines 367-467)
**Fix:**
```php
// UUID-first check
$user = get_users(['meta_key' => 'supabase_user_id', 'meta_value' => $sub]);

// Distributed lock
$lock_key = 'sb_user_creation_' . $sub;
if (!wp_cache_add($lock_key, time(), '', 5)) {
  usleep(100000); // Wait for other process
}
```
**Documentation:** TROUBLESHOOTING.md, DIAGNOSTIC_CHECKLIST.md (v0.4.1)

---

### Issue: CSP Headers Breaking Elementor/Page Builders (v0.4.1)
**Symptom:** Elementor/Divi/Beaver Builder admin panel not loading, JavaScript errors
**Root Cause:** Strict Content-Security-Policy headers block inline scripts used by page builders
**Solution:** Conditionally disable CSP headers on admin pages and specific pages
**File:** `supabase-bridge.php` (send_headers hook)
**Fix:**
```php
// Don't send CSP headers in admin or on Elementor pages
if (is_admin() || isset($_GET['elementor-preview'])) {
  return; // No CSP headers
}
```

---

### Issue: WordPress Text Filters Replacing Placeholders (v0.4.1)
**Symptom:** AUTH_CONFIG placeholder "__REGISTRATION_PAIRS_JSON__" replaced by WordPress wpautop filter
**Root Cause:** WordPress text filters (wpautop, wptexturize) modify HTML content including placeholders
**Solution:** Use pattern that WordPress doesn't recognize (/* PLACEHOLDER_JSON */)
**File:** `auth-form.html` and `supabase-bridge.php` (REST endpoint)
**Fix:**
```javascript
// Before (broken by WordPress)
const pairsData = "__REGISTRATION_PAIRS_JSON__";

// After (WordPress-safe)
const pairsData = /* __PAIRS_PLACEHOLDER__ */ {};
```

---

### Issue: RLS Policies Blocking Legitimate Requests (v0.7.0)
**Symptom:** PostgreSQL error "new row violates row-level security policy" when inserting records
**Root Cause:** RLS policy USING clause too restrictive or x-site-url header mismatch
**Solution:** Verify x-site-url header matches site_url column value exactly
**File:** `SECURITY_RLS_POLICIES_FINAL.sql` and `supabase-bridge.php`
**Debugging:**
```sql
-- Check RLS policy
SELECT * FROM pg_policies WHERE tablename = 'wp_registration_pairs';

-- Test header parsing
SELECT current_setting('request.headers', true)::json->>'x-site-url';
```
**Fix:** Ensure WordPress sends exact site_url:
```php
'x-site-url' => get_site_url() // Must match Supabase site_url column
```

---

### Issue: AIOS PHP Firewall Breaking AJAX (v0.7.0)
**Symptom:** WordPress admin-ajax.php returns 403 Forbidden, Settings page CRUD doesn't work
**Root Cause:** All-In-One Security (AIOS) PHP Firewall blocks legitimate AJAX requests
**Solution:** Disable AIOS PHP Firewall (it breaks WordPress core functionality)
**File:** WordPress Admin → AIOS → Firewall → PHP Firewall
**Fix:** Set "Enable PHP Firewall" to OFF (see PRODUCTION_SETUP.md)
**Alternative:** Whitelist /wp-admin/admin-ajax.php (but may still break other requests)

---

### Issue: LiteSpeed Cache Caching AJAX Responses (v0.7.0)
**Symptom:** Registration Pairs not updating in real-time, cached JSON returned
**Root Cause:** LiteSpeed Cache caching admin-ajax.php responses and API endpoints
**Solution:** Exclude admin-ajax.php and query strings from cache
**File:** LiteSpeed Cache → Cache → Excludes
**Fix:**
```
/wp-admin/admin-ajax.php
/wp-json/*
?action=*
```
**Documentation:** PRODUCTION_SETUP.md (v0.7.0)

---

## 📋 Task Checklists

### Adding New Feature
- [ ] Read ARCHITECTURE.md and BACKLOG.md
- [ ] Create TodoWrite task list
- [ ] Implement following existing patterns
- [ ] Write/update tests
- [ ] Update TypeScript types (if applicable)
- [ ] Update UI components
- [ ] Update BACKLOG.md status
- [ ] Update ARCHITECTURE.md (if architectural changes)
- [ ] Update README.md (if user-facing changes)
- [ ] Create sprint completion commit

### Database Schema Change
- [ ] Create migration script
- [ ] Test in development environment
- [ ] Update TypeScript types/interfaces
- [ ] Update database documentation
- [ ] Update related code
- [ ] Test all affected features
- [ ] Update ARCHITECTURE.md
- [ ] Update BACKLOG.md status
- [ ] Create sprint completion commit

### Bug Fix
- [ ] Reproduce and document bug
- [ ] Identify root cause
- [ ] Implement fix
- [ ] Test fix thoroughly
- [ ] Check for similar issues elsewhere
- [ ] Add to "Common Issues" (if applicable)
- [ ] Update BACKLOG.md (if tracked)
- [ ] Create fix commit

---

## 🎯 Custom Slash Commands

Проект включает кастомные slash-команды для автоматизации типичных задач:

### Основные команды:
- `/security` - провести security audit (см. SECURITY.md)
- `/test` - написать тесты для кода
- `/feature` - спланировать и реализовать новую фичу
- `/review` - провести code review последних изменений
- `/optimize` - оптимизировать производительность кода
- `/refactor` - помочь с рефакторингом кода
- `/explain` - объяснить как работает код
- `/fix` - найти и исправить баг

### Новые команды для workflow:
- `/commit` - создать git commit с правильным сообщением
  - Анализирует изменения
  - Проверяет на секреты
  - Создает осмысленное сообщение (why, not what)
  - Добавляет Co-Authored-By: Claude

- `/pr` - создать Pull Request
  - Анализирует ВСЕ коммиты (не только последний!)
  - Создает детальное описание
  - Включает test plan и checklist
  - Использует gh CLI

- `/migrate` - создать database migration
  - Анализирует текущую схему
  - Создает безопасную миграцию
  - Обновляет TypeScript types
  - Настраивает RLS policies

**Использование:**
```bash
# В Claude Code просто набери:
/commit
/pr
/migrate
```

---

## 🚀 Release Management (для claude-code-starter проекта)

> **⚠️ ВАЖНО:** Эта секция применяется **ТОЛЬКО для проекта claude-code-starter**
> НЕ применяй эти правила к пользовательским проектам!

### Проактивный Release Checking

**Контекст:** Когда работаешь с `claude-code-starter` фреймворком (путь `/Users/alexeykrolmini/Downloads/Code/Project_init`), ты ДОЛЖЕН автоматически предлагать создание релиза после существенных изменений.

**Почему это важно:**
```
Смысл фреймворка - помогать другим проектам ничего не упускать.
Мы сами не должны забывать обновлять README и CHANGELOG!
("Сапожник без сапог" проблема)
```

### Определение "Существенные Изменения"

**Существенными считаются:**

1. **Новые фичи:**
   - Новые slash-команды добавлены в `.claude/commands/`
   - Новые секции в шаблонах (Init/, init_eng/)
   - Новые протоколы (например, Cold Start Protocol)
   - Новые файлы документации

2. **Исправления багов:**
   - Исправлены критические ошибки в командах
   - Исправлены ошибки в логике миграции
   - Исправлены ошибки в шаблонах

3. **Улучшения документации:**
   - Добавлены существенные секции в README
   - Добавлены новые best practices
   - Добавлены примеры использования

**НЕ существенными считаются:**
- Опечатки (typos)
- Форматирование без изменения содержания
- Комментарии в коде
- Мелкие правки текста

### Правила Проактивного Предложения

**После коммита существенных изменений:**

1. **Проанализируй последние коммиты:**
   ```bash
   git log --oneline -n 5
   ```

2. **Оцени существенность:**
   - IF новые файлы в .claude/commands/ → Существенно
   - IF изменения в Init/ шаблонах → Существенно
   - IF новые секции в README → Существенно
   - IF bugfixes в командах → Существенно

3. **Автоматически предложи релиз:**
   ```
   ✅ Изменения закоммичены.

   🎯 Обнаружены существенные изменения:
   - [список изменений]

   💡 Рекомендуется создать релиз для обновления CHANGELOG и README.

   Создать релиз?
   1. Patch (X.X.N) - bugfixes, documentation
   2. Minor (X.N.0) - new features
   3. Major (N.0.0) - breaking changes

   Выберите [1/2/3] или "skip":
   ```

4. **IF пользователь выбрал 1/2/3:**
   - Запусти `/release` автоматически
   - Передай выбранный тип релиза

5. **IF пользователь выбрал "skip":**
   - Не предлагай повторно в этой сессии
   - Но предложи снова в следующей сессии если релиз не создан

### Slash-команда `/release`

**Назначение:** Автоматизирует процесс создания релиза

**Что делает:**
1. Анализирует изменения с последнего релиза
2. Определяет тип релиза (patch/minor/major)
3. Обновляет версию в README.md и README_RU.md
4. Создает запись в CHANGELOG.md
5. Пересобирает zip-архивы (init-starter.zip, init-starter-en.zip)
6. Создает release commit
7. Пушит на GitHub
8. Опционально создает GitHub Release

**Использование:**
```bash
/release
```

**Когда использовать:**
- После завершения новой фичи
- После исправления критического бага
- После существенных изменений в документации
- Перед анонсированием обновлений

### Проверка Релиза В "Cold Start"

**При перезагрузке сессии (Cold Start):**

1. **Прочитай первые 20 строк README.md**
   ```bash
   head -n 20 README.md
   ```

2. **Извлеки текущую версию:**
   ```markdown
   [![Version](https://img.shields.io/badge/version-1.2.5-orange.svg)]
   ```

3. **Проверь последний коммит:**
   ```bash
   git log -1 --oneline
   ```

4. **IF последний коммит НЕ "chore: Release v..." AND есть новые коммиты:**
   ```bash
   git log --oneline --grep="chore: Release" -1  # последний релиз
   git log <last-release>..HEAD --oneline        # коммиты после релиза
   ```

5. **IF найдены существенные изменения без релиза:**
   - Предложи создать релиз (см. шаблон выше)
   - НО только один раз, не при каждом сообщении

### Интеграция с TodoWrite

**Если работаешь над фичей/багфиксом:**

1. Добавь в todo list:
   ```
   - Implement feature X
   - Test feature X
   - Update documentation
   - Create release  # ← Добавляй автоматически для существенных изменений
   ```

2. После завершения всех задач → автоматически предложи `/release`

### Примеры

**Пример 1: Новая команда добавлена**
```
✅ Создана новая slash-команда /release

🎯 Это существенное изменение:
- Добавлен Init/.claude/commands/release.md
- Добавлен init_eng/.claude/commands/release.md
- Обновлены AGENTS.md и WORKFLOW.md

💡 Рекомендуется создать Minor релиз (новая фича).

Создать релиз 1.3.0? [y/n]
```

**Пример 2: Критический багфикс**
```
✅ Исправлена критическая ошибка в /migrate команде

🎯 Это существенное изменение:
- Исправлены 8 багов в migrate.md
- Обновлена логика архивирования

💡 Рекомендуется создать Patch релиз.

Создать релиз 1.2.6? [y/n]
```

### ⚠️ КРИТИЧНО: После КАЖДОГО Коммита

**ОБЯЗАТЕЛЬНО после создания ЛЮБОГО коммита:**

1. **Проверь README.md и README_RU.md:**
   - [ ] Список фич отражает изменения
   - [ ] Количество шаблонов точное (например: "14 шаблонов" если добавили 3 файла)
   - [ ] Версия в badge актуальна (если релиз)
   - [ ] Cold Start Protocol описание актуально (% экономии токенов)
   - [ ] Таблица "What's in Init/" содержит все новые файлы

2. **Проверь CHANGELOG.md:**
   - [ ] Добавлена запись для текущей версии
   - [ ] Описаны все существенные изменения
   - [ ] Указано количество измененных строк/файлов
   - [ ] Добавлен раздел "Why This Matters"

**Почему это КРИТИЧНО:**
```
Смысл фреймворка - помогать другим проектам ничего не упускать.
Мы сами НЕ ДОЛЖНЫ забывать обновлять README и CHANGELOG!
("Сапожник без сапог" проблема v2.0)
```

**Пример того, что забыли в v1.4.0:**
- ❌ README говорил "11 шаблонов" вместо "14 шаблонов"
- ❌ Features list говорил "60% экономия" вместо "85% экономия (5x дешевле!)"
- ❌ Не упомянули PROJECT_SNAPSHOT.md и модульный фокус в features

**Как проверить:**
```bash
# После каждого коммита:
git diff HEAD~1 README.md       # Проверить что README обновлен
git diff HEAD~1 CHANGELOG.md    # Проверить что CHANGELOG обновлен
```

**Правило для AI:**
После КАЖДОГО коммита AI ДОЛЖЕН:
1. Прочитать README.md (features section, file count)
2. Прочитать CHANGELOG.md (последняя версия)
3. Проверить соответствие с коммитом
4. Если несоответствие → немедленно исправить

---

### Checklist Перед Релизом

Перед запуском `/release` убедись:
- [ ] Все изменения закоммичены
- [ ] Working directory чист
- [ ] Протестированы новые фичи
- [ ] Документация обновлена
- [ ] **README.md и CHANGELOG.md проверены (см. выше)**
- [ ] Нет конфликтов в git

---

## 🔍 Debugging Quick Reference

### [ЗАПОЛНИТЬ: Специфичные команды для отладки]

```bash
# Database debugging
[команды для проверки БД]

# API debugging
[команды для проверки API]

# State debugging
[команды для проверки состояния]
```

---

## 📊 Performance Guidelines

### [ЗАПОЛНИТЬ: Специфичные для проекта]

**API Optimization:**
- [Правило 1]
- [Правило 2]

**Database Performance:**
- [Правило 1]
- [Правило 2]

**Frontend Performance:**
- [Правило 1]
- [Правило 2]

---

## 🚀 Code Templates

### [ЗАПОЛНИТЬ: Шаблоны кода для проекта]

#### New Component Template
```typescript
// Пример шаблона компонента
```

#### New API Route Template
```typescript
// Пример шаблона API route
```

#### New Service Method Template
```typescript
// Пример шаблона метода сервиса
```

---

## 📝 Sprint Workflow

See **WORKFLOW.md** for detailed sprint processes, including:
- Sprint structure and phases
- Completion checklists
- Commit message templates
- Documentation update requirements

**Key Rule:** NEVER end a sprint without updating all relevant documentation files.

---

## 📋 Где брать чек-листы и задачи

**КРИТИЧНО для AI:**

**Когда пользователь спрашивает:**
- "Покажи чек-лист для текущего этапа"
- "Что осталось сделать?"
- "Какие задачи в Sprint 1?"
- "Дай план работы"

**✅ ПРАВИЛЬНО:**
1. Читай **BACKLOG.md** → там детальный план с чек-листами
2. Показывай статусы: ✅ DONE / 🚧 IN PROGRESS / ⏳ TODO
3. BACKLOG.md = single source of truth для задач
4. Если нужны архитектурные справки → тогда ARCHITECTURE.md

**❌ НЕПРАВИЛЬНО:**
- ❌ Не читай ARCHITECTURE.md для оперативных чек-листов
- ❌ ARCHITECTURE.md = справка о WHY (технологии, принципы), не про WHAT делать сейчас
- ❌ Не генерируй чек-лист "из головы" если есть BACKLOG.md
- ❌ Не пытайся извлечь задачи из разделов "Phase 1, Phase 2" в ARCHITECTURE.md

**Почему это важно:**
Если детальные задачи хранятся в ARCHITECTURE.md, AI может пропустить вложенные пункты
из-за большого размера файла. BACKLOG.md = structured task list, AI читает все пункты.

**Пример правильного ответа:**
```
Пользователь: "Покажи что осталось сделать в Sprint 1"

AI Response:
1. ✅ Читаю BACKLOG.md...
2. Показываю секцию "Sprint 1" с чек-листами
3. Объясняю статусы каждой задачи
4. Если нужны детали реализации → смотрю в ARCHITECTURE.md
```

**Исключение:**
Если BACKLOG.md пуст/не заполнен → можно использовать общий план
из ARCHITECTURE.md как fallback, но предложи пользователю создать BACKLOG.md
с детальными задачами.

---

## 🔄 Version History

- **[DATE]:** Initial template created
- [Добавляйте записи по мере развития проекта]

---

## 📝 Notes for Customization

Когда заполняете этот файл для конкретного проекта:

1. **Замените [ЗАПОЛНИТЬ]** на актуальную информацию
2. **Удалите эту секцию** после заполнения
3. **Добавляйте новые секции** по мере необходимости
4. **Обновляйте** после каждого спринта
5. **Документируйте паттерны** которые появляются в проекте
6. **Добавляйте в Common Issues** решенные проблемы

---

*This file should be updated after every sprint completion*
*Goal: Maintain living documentation for effective AI-assisted development*
*Compatible with: Claude Code, Cursor, GitHub Copilot, and other AI coding assistants*
