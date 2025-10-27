# Добавление вкладки "Webhooks" в supabase-bridge.php

**Цель:** Добавить 3-ю вкладку "🔗 Webhooks" в существующий Settings page плагина

---

## 📍 Изменение 1: Добавить вкладку в навигацию

**Файл:** `supabase-bridge.php`
**Строка:** После строки `1096` (после вкладки "Registration Pairs")

**Найди:**
```php
      <a href="?page=supabase-bridge-setup&tab=pairs" class="nav-tab <?php echo $current_tab === 'pairs' ? 'nav-tab-active' : ''; ?>">
        🔗 Registration Pairs
      </a>
    </h2>
```

**Добавь ПЕРЕД `</h2>`:**
```php
      <a href="?page=supabase-bridge-setup&tab=webhooks" class="nav-tab <?php echo $current_tab === 'webhooks' ? 'nav-tab-active' : ''; ?>">
        🪝 Webhooks
      </a>
```

**Результат будет:**
```php
      <a href="?page=supabase-bridge-setup&tab=pairs" class="nav-tab <?php echo $current_tab === 'pairs' ? 'nav-tab-active' : ''; ?>">
        🔗 Registration Pairs
      </a>
      <a href="?page=supabase-bridge-setup&tab=webhooks" class="nav-tab <?php echo $current_tab === 'webhooks' ? 'nav-tab-active' : ''; ?>">
        🪝 Webhooks
      </a>
    </h2>
```

---

## 📍 Изменение 2: Добавить контент вкладки

**Файл:** `supabase-bridge.php`
**Строка:** После строки `1339` (после `</div><!-- End Tab 2: Registration Pairs -->`)

**Найди:**
```php
      </div><!-- End Tab 2: Registration Pairs -->

    <?php endif; ?>
```

**Добавь МЕЖДУ ними:**
```php
    <?php elseif ($current_tab === 'webhooks'): ?>
      <!-- TAB 3: Webhooks -->
      <div class="tab-content">
        <?php sb_render_webhooks_tab(); ?>
      </div><!-- End Tab 3: Webhooks -->
```

**Результат будет:**
```php
      </div><!-- End Tab 2: Registration Pairs -->

    <?php elseif ($current_tab === 'webhooks'): ?>
      <!-- TAB 3: Webhooks -->
      <div class="tab-content">
        <?php sb_render_webhooks_tab(); ?>
      </div><!-- End Tab 3: Webhooks -->

    <?php endif; ?>
```

---

## 📍 Изменение 3: Добавить функцию для вкладки

**Файл:** `supabase-bridge.php`
**Строка:** В КОНЕЦ файла (перед последним `?>` если есть, или просто в конец)

**Добавь:**
```php
// === Phase 3: Webhooks Tab (v0.8.0) ===
function sb_render_webhooks_tab() {
  ?>
  <div style="margin-top: 20px;">
    <h2>🪝 Webhook System for n8n/make</h2>
    <p>Configure webhooks to send user registration events to n8n or make.com workflows.</p>

    <!-- Placeholder / Coming Soon -->
    <div style="background: #f0f6fc; border: 1px solid #d1e4f5; border-radius: 6px; padding: 40px; text-align: center; margin: 20px 0;">
      <p style="font-size: 18px; color: #333; margin: 0;">
        🚧 Under Construction
      </p>
      <p style="color: #666; margin: 10px 0 0 0;">
        Webhook system is being developed. Full UI will be available soon.
      </p>
    </div>

    <!-- Documentation Link -->
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
      <p style="margin: 0;"><strong>📖 Documentation:</strong></p>
      <p style="margin: 5px 0 0 0;">
        See <code>webhook-system/README.md</code> for architecture and deployment guide.
      </p>
    </div>
  </div>
  <?php
}
```

---

## ✅ Тестирование

После внесения изменений:

1. Откройте **WordPress Admin → Settings → Supabase Bridge**
2. Вы должны увидеть **3 вкладки:**
   - ⚙️ General Settings
   - 🔗 Registration Pairs
   - 🪝 Webhooks (NEW!)
3. Кликните на вкладку "Webhooks"
4. Должно показать "Under Construction" placeholder

---

## 📝 Что дальше?

**Phase 1: UI Testing (сейчас)**
- [x] Добавить навигацию для вкладки
- [x] Добавить контент вкладки (placeholder)
- [ ] Протестировать переключение вкладок
- [ ] Убедиться что другие вкладки работают

**Phase 2: Full UI (следующий шаг)**
- [ ] Заменить `sb_render_webhooks_tab()` на полный UI
- [ ] Добавить Configuration form
- [ ] Добавить Supabase Setup instructions
- [ ] Добавить Test & Monitor section

**Phase 3: Backend**
- [ ] Implement AJAX handlers
- [ ] Supabase integration
- [ ] Webhook delivery testing

---

## 🔄 Откат изменений (если нужно)

Чтобы удалить вкладку:

1. Удалите код из навигации (Изменение 1)
2. Удалите `elseif` блок (Изменение 2)
3. Удалите функцию `sb_render_webhooks_tab()` (Изменение 3)

Никаких других частей плагина это не затронет!

---

*Integration Guide v0.8.0*
*Минимальные изменения для безопасного тестирования UI*
