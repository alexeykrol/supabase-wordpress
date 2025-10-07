# 📦 Установка плагина Supabase Bridge на WordPress

**TL;DR:** Загрузи готовый **supabase-bridge.zip** (43KB) через WordPress Admin

---

## 🚀 РЕКОМЕНДУЕМЫЙ СПОСОБ: Установка через WordPress Admin (ZIP)

### Готовый файл для загрузки:

```
supabase-bridge.zip   ✅ 43KB - WordPress Plugin ZIP
```

**Содержимое ZIP:**
```
supabase-bridge.zip
├── supabase-bridge.php    ✅ 11KB  - Основной файл плагина + Admin UI
├── composer.json           ✅ 205B  - Описание зависимостей
├── composer.lock           ✅ 3KB   - Lock-файл версий
└── vendor/                 ✅ 184KB - Библиотека firebase/php-jwt
    ├── autoload.php
    ├── composer/
    └── firebase/php-jwt/
```

**Файл находится здесь:** `/Users/alexeykrolmini/Downloads/Code/supabase-bridge.zip`

---

## 📋 Пошаговая инструкция установки

### Шаг 1: Загрузка плагина в WordPress

1. Открой WordPress Admin: `https://yoursite.com/wp-admin/`
2. Перейди в **Plugins** → **Add New**
3. Нажми кнопку **Upload Plugin** (вверху страницы)
4. Выбери файл: `supabase-bridge.zip`
5. Нажми **Install Now**

### Шаг 2: Активация плагина

1. После установки нажми **Activate Plugin**
2. **Автоматически откроется страница настройки** с инструкциями
3. Если не открылась - перейди в меню **Supabase Bridge** (в левой панели админки)

### Шаг 3: Настройка wp-config.php

**На сервере** отредактируй `wp-config.php` и добавь ПЕРЕД строкой `/* That's all, stop editing! */`:

```php
// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=your-project-ref-here');
putenv('SUPABASE_URL=https://your-project-ref-here.supabase.co');
putenv('SUPABASE_ANON_KEY=your-supabase-anon-key-here');
```

### Шаг 4: Создание WordPress страниц

**Страница настройки плагина покажет готовый код для вставки!**

Создай 3 страницы:

1. **Страница входа** (любой URL) - вставь код кнопки из админки
2. **Callback страница** (URL: `/supabase-callback/`) - вставь код обработчика
3. **Страница регистрации** (URL: `/registr/`) - произвольный контент

### Шаг 5: Настройка Supabase Dashboard

1. Открой https://app.supabase.com
2. Выбери свой проект
3. **Authentication → URL Configuration** → Добавь Redirect URL:
   ```
   https://yoursite.com/your-login-page/
   ```
4. **Authentication → Providers** → Включи **Google OAuth**

---

## ✅ ОБЯЗАТЕЛЬНЫЕ ФАЙЛЫ (для работы плагина)

---

## ❌ НЕ НУЖНО загружать на сервер

### Документация (только для разработки):
```
❌ AGENTS.md              19KB - Инструкции для AI
❌ ARCHITECTURE.md         19KB - Техническая документация
❌ BACKLOG.md              13KB - Roadmap
❌ CLAUDE.md               2.5KB - Deprecated файл
❌ INSTALL.md              16KB - Инструкция установки
❌ README.md               11KB - Основная документация
❌ STATUS.md               8KB  - Статус проекта
❌ FLOW.md                 1.7KB - Диаграммы
❌ supabase-bridge.md      293B - Заметки
❌ Выход.md                3.6KB - Заметки
```

### Примеры HTML (только для справки):
```
❌ button.html             543B - Пример кнопки входа (вставишь в WP)
❌ htmlblock.html          934B - Пример callback страницы (вставишь в WP)
```

### Конфигурационные файлы (только локально):
```
❌ wp-config_questtales.php        3.8KB - Только для справки (данные добавишь в существующий wp-config.php)
❌ wp-config_alexeykrol.php        3.3KB - Другой проект
❌ wp-config-supabase-example.php  3.6KB - Пример
```

### Инструменты разработки:
```
❌ composer.phar           3MB - Composer локально (на сервере уже есть или не нужен)
```

### Системные файлы:
```
❌ .DS_Store              6KB - macOS системный файл
```

---

## 📋 Пошаговая инструкция деплоя

### Вариант 1: Загрузить только нужное (РЕКОМЕНДУЕТСЯ)

#### Шаг 1: Создай чистую папку для загрузки

```bash
# Создай временную папку с минимальным набором
mkdir supabase-bridge-deploy
cd supabase-bridge-deploy

# Скопируй только нужные файлы
cp /Users/alexeykrolmini/Downloads/Code/supabase-bridge/supabase-bridge.php .
cp /Users/alexeykrolmini/Downloads/Code/supabase-bridge/composer.json .
cp /Users/alexeykrolmini/Downloads/Code/supabase-bridge/composer.lock .

# Скопируй vendor целиком
cp -r /Users/alexeykrolmini/Downloads/Code/supabase-bridge/vendor .
```

Или просто выполни:

```bash
cd /Users/alexeykrolmini/Downloads/Code/supabase-bridge/
mkdir ../supabase-bridge-deploy
cp supabase-bridge.php composer.json composer.lock ../supabase-bridge-deploy/
cp -r vendor ../supabase-bridge-deploy/
```

**Результат:** Папка `supabase-bridge-deploy` готова к загрузке (~191KB)

#### Шаг 2: Загрузи на сервер

**FTP/SFTP:**
- Загрузи папку `supabase-bridge-deploy` → переименуй в `supabase-bridge`
- Путь на сервере: `wp-content/plugins/supabase-bridge/`

**cPanel:**
- Создай ZIP архив папки `supabase-bridge-deploy`
- Загрузи через File Manager
- Extract в `wp-content/plugins/`
- Переименуй в `supabase-bridge`

---

### Вариант 2: Загрузить всё (проще, но больше места)

Если лень разбираться, загрузи всю папку `supabase-bridge/` как есть:

```
✅ Плагин будет работать
✅ Просто на сервере будут лежать лишние .md файлы
❌ Займет ~3.3MB вместо 191KB (из-за composer.phar)
```

**Не критично**, но неоптимально.

---

## 🛠 Что делать с wp-config.php

**НЕ ЗАГРУЖАЙ** файл `wp-config_questtales.php` на сервер!

Вместо этого:

1. На сервере **открой существующий** `wp-config.php`
2. Найди строку:
   ```php
   /* That's all, stop editing! Happy publishing. */
   ```
3. **ПЕРЕД** этой строкой добавь:

```php
// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=your-project-ref-here');
putenv('SUPABASE_URL=https://your-project-ref-here.supabase.co');
putenv('SUPABASE_ANON_KEY=your-supabase-anon-key-here');
```

4. Сохрани файл

**Готово!** Конфигурация Supabase добавлена в существующий wp-config.php.

---

## 🎯 Итоговая структура на сервере

```
yoursite.com/
├── wp-config.php                     # Существующий файл + 3 строки Supabase
└── wp-content/plugins/
    └── supabase-bridge/              # Загруженный плагин
        ├── supabase-bridge.php       # ✅ Обязательно
        ├── composer.json              # ✅ Обязательно
        ├── composer.lock              # ✅ Обязательно
        └── vendor/                    # ✅ Обязательно (184KB)
            ├── autoload.php
            ├── composer/
            └── firebase/php-jwt/
```

**Всё! Больше ничего не нужно.**

---

## 📊 Сравнение размеров

| Что загружать | Размер | Файлов |
|---------------|--------|--------|
| **Минимум (рекомендуется)** | ~191 KB | 4 файла + vendor/ |
| Всё подряд | ~3.3 MB | 20+ файлов |
| Разница | **3.1 MB лишних** | 16+ лишних файлов |

**Экономия:** 94% места и времени загрузки!

---

## ✅ Быстрая команда для подготовки деплоя

Скопируй и выполни:

```bash
cd /Users/alexeykrolmini/Downloads/Code/
mkdir supabase-bridge-deploy
cd supabase-bridge-deploy
cp ../supabase-bridge/supabase-bridge.php .
cp ../supabase-bridge/composer.json .
cp ../supabase-bridge/composer.lock .
cp -r ../supabase-bridge/vendor .
echo "✅ Готово! Загружай папку supabase-bridge-deploy на сервер"
ls -lh
```

**Результат:**
- Создана папка `supabase-bridge-deploy/` с минимальным набором
- Готова к загрузке на yoursite.com

---

## 🚀 После загрузки на сервер

1. **Переименуй папку:**
   `supabase-bridge-deploy` → `supabase-bridge`

2. **Проверь путь:**
   ```
   wp-content/plugins/supabase-bridge/supabase-bridge.php
   ```
   Этот файл должен существовать!

3. **Активируй плагин:**
   WordPress Admin → Plugins → Supabase Bridge (Auth) → Activate

4. **Проверь работу:**
   Открой любую страницу yoursite.com → F12 → Console:
   ```javascript
   console.log(window.SUPABASE_CFG);
   ```
   Должен вывести объект с `url` и `anon` ✅

---

## 📝 Заметки

- ✅ HTML примеры (`button.html`, `htmlblock.html`) - вставь их код прямо в WordPress страницы
- ✅ Документация (.md файлы) - храни локально для справки
- ✅ На сервере уже есть PHP и всё необходимое окружение
- ✅ `vendor/` создан локально через `composer install` - загружай как есть
- ❌ **НЕ НУЖНО** запускать `composer install` на сервере (уже готово)

---

*Последнее обновление: 2025-10-01 19:30*
*Статус: READY FOR MINIMAL DEPLOYMENT*
