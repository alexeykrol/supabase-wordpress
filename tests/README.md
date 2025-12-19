# Supabase Bridge Test Suite

**Framework:** Claude Code Starter v2.3.1
**Created:** 2025-12-18
**Purpose:** Automated and AI-assisted testing infrastructure

---

## 🚀 БЫСТРЫЙ СТАРТ (для нетерпеливых)

### **Хотите просто запустить все тесты?**

```bash
/test
```

**Вот и всё!** Я (Claude) сам:
1. Запущу все тесты
2. Соберу результаты
3. Проанализирую проблемы
4. Выдам bug report

**Или через командную строку:**

```bash
composer test
# или
bash tests/run-all.sh
```

**Что будет проверено:**
- ✅ Smoke tests (5 секунд) - базовые проверки
- ✅ Unit tests (1 минута) - детальные проверки функций
- 📊 AI analysis - я найду паттерны и проблемы

**Результат:** Полный отчёт о состоянии кода

---

## 📁 Directory Structure

```
tests/
├── unit/              # PHPUnit unit tests (fast, isolated)
├── smoke/             # Smoke test scripts (quick health checks)
├── manual/            # Manual testing checklists
├── ai-assisted/       # Tests where AI analyzes results
├── reports/           # Test execution reports (gitignored)
└── README.md          # This file
```

---

## 🚀 Quick Start

### **1. Smoke Tests (Fastest - 10 seconds)**

Run before/after deployment to check basic functionality:

```bash
bash tests/smoke/health-check.sh
```

### **2. Unit Tests (Fast - 1 minute)**

Test individual functions in isolation:

```bash
composer test
# or
vendor/bin/phpunit
```

### **3. Manual Tests (Human Required)**

Follow checklist for OAuth flows and browser testing:

```bash
cat tests/manual/registration-flow.md
```

### **4. AI-Assisted Analysis**

Generate test report and ask Claude to analyze:

```bash
bash tests/ai-assisted/generate-report.sh
# Then: "Claude, analyze tests/reports/latest.json"
```

---

## 📊 Test Types

### **Unit Tests** (`tests/unit/`)

**What:** Test individual PHP functions in isolation
**Speed:** ⚡⚡⚡ Very fast (milliseconds per test)
**Coverage:** Functions, validation logic, data transformations

**Examples:**
- `ValidationTest.php` - Email, URL, UUID validation
- `RegistrationPairsTest.php` - Thank You page lookup logic
- `JWTVerificationTest.php` - JWT decode and validation

**Run:**
```bash
vendor/bin/phpunit tests/unit/
```

---

### **Smoke Tests** (`tests/smoke/`)

**What:** Quick health checks (bash scripts)
**Speed:** ⚡⚡⚡ Very fast (5-10 seconds total)
**Coverage:** Endpoints, configuration, database connectivity

**Examples:**
- `health-check.sh` - Check site is up, plugin active, config present
- `api-endpoints.sh` - Test REST API endpoints respond
- `database-check.sh` - Verify Registration Pairs exist

**Run:**
```bash
bash tests/smoke/health-check.sh
```

---

### **Manual Tests** (`tests/manual/`)

**What:** Human-executed test checklists
**Speed:** 🐌 Slow (5-15 minutes)
**Coverage:** OAuth flows, browser compatibility, UI/UX

**Examples:**
- `registration-flow.md` - Step-by-step registration testing
- `browser-compatibility.md` - Cross-browser checklist
- `oauth-providers.md` - Google/Facebook/Magic Link testing

**Run:**
```bash
# Read and follow checklist
cat tests/manual/registration-flow.md
```

---

### **AI-Assisted Tests** (`tests/ai-assisted/`)

**What:** Scripts that generate reports for AI analysis
**Speed:** ⚡⚡ Medium (30 seconds to generate)
**Coverage:** Pattern detection, anomaly analysis, regression detection

**Examples:**
- `generate-report.sh` - Collect test results, logs, metrics
- `analyze-logs.sh` - Extract patterns from WordPress debug.log
- `compare-versions.sh` - Compare test results across versions

**How it works:**
1. Script generates JSON report in `tests/reports/`
2. You share report with Claude
3. Claude analyzes patterns, finds issues, suggests improvements

**Run:**
```bash
bash tests/ai-assisted/generate-report.sh
# Then in Claude: "Analyze tests/reports/2025-12-18-14-30.json"
```

---

## 🎯 Test Coverage Goals

| Component | Unit Tests | Smoke Tests | Manual Tests | Status |
|-----------|-----------|-------------|--------------|--------|
| JWT Verification | ✅ | ✅ | ❌ | 🟢 Good |
| User Creation | ✅ | ✅ | ✅ | 🟢 Good |
| Registration Pairs | ✅ | ✅ | ✅ | 🟢 Good |
| OAuth Flows | ❌ | ⚠️ | ✅ | 🟡 Manual only |
| Magic Link | ❌ | ⚠️ | ✅ | 🟡 Manual only |
| Webhooks | ⏳ | ⏳ | ⏳ | ⏳ Todo |
| MemberPress Integration | ⏳ | ⏳ | ⏳ | ⏳ Todo |
| LearnDash Integration | ⏳ | ⏳ | ⏳ | ⏳ Todo |

**Legend:**
✅ Implemented | ⚠️ Partial | ❌ Not possible | ⏳ Todo | 🟢 Good | 🟡 Acceptable | 🔴 Needs work

---

## 🔧 Setup Instructions

### **Install PHPUnit**

```bash
# Install Composer dependencies
composer require --dev phpunit/phpunit

# Verify installation
vendor/bin/phpunit --version
```

### **Configure PHPUnit**

Already configured in `phpunit.xml` at project root.

### **Install WordPress Test Suite (Optional)**

For integration tests with WordPress:

```bash
bash tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

---

## 🤖 AI-Assisted Testing Workflow

**How Claude helps with testing:**

### **1. Pattern Detection**
Claude analyzes test reports and finds patterns:
- "Registration fails only on Safari with Magic Link"
- "Email validation rejects 10% of valid .co.uk emails"

### **2. Regression Detection**
Compare test results across versions:
- "Thank You page redirect stopped working in v0.9.8"
- "User creation 20% slower than v0.9.7"

### **3. Test Case Generation**
Claude suggests additional test cases:
- "You're not testing email with +alias (user+test@gmail.com)"
- "Add test for registration_url with query params"

### **4. Log Analysis**
Claude reads WordPress debug.log and finds issues:
- "JWT verification failing due to clock skew"
- "Distributed lock timeout causing duplicate users"

---

## 📝 Writing New Tests

### **Unit Test Template**

```php
<?php
// tests/unit/ExampleTest.php

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase {
  public function testValidEmail() {
    $this->assertTrue(sb_validate_email('test@example.com'));
  }

  public function testInvalidEmail() {
    $this->assertFalse(sb_validate_email('not-an-email'));
  }
}
```

### **Smoke Test Template**

```bash
#!/bin/bash
# tests/smoke/example.sh

echo "🧪 Running example smoke test..."

# Test 1: Check something exists
if [ -f "supabase-bridge.php" ]; then
  echo "✅ Plugin file exists"
else
  echo "❌ Plugin file missing"
  exit 1
fi

echo "✅ All checks passed!"
```

---

## 🐛 Troubleshooting

### **PHPUnit not found**

```bash
composer install
```

### **Tests failing locally but passing on production**

Check environment differences:
- PHP version
- WordPress version
- Plugin versions
- Database data

### **Smoke tests timeout**

Increase timeout in script:
```bash
TIMEOUT=30  # seconds
```

---

## 📚 Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Plugin Testing](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [Bash Testing Best Practices](https://github.com/bats-core/bats-core)

---

**Questions?** Ask Claude for help with test setup or analysis!
