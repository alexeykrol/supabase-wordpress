# 🚀 Quick Start — Add Webhooks Tab

**Подход:** Постепенная интеграция, сначала UI, потом backend

---

## 📍 Шаг 1: Добавить Placeholder (5 минут)

**Цель:** Проверить что вкладка появляется и navigation работает

**Следуй инструкциям:**
→ См. `ADD-WEBHOOKS-TAB.md`

**Что получится:**
- 3-я вкладка "🪝 Webhooks" появится в Settings
- Контент: "Under Construction" placeholder
- Другие вкладки не затронуты

**Тестирование:**
1. Откройте WordPress Admin → Settings → Supabase Bridge
2. Проверьте что 3 вкладки: General Settings, Registration Pairs, **Webhooks**
3. Кликните на Webhooks — должен показать placeholder
4. Проверьте другие вкладки — должны работать как раньше

✅ **Если всё ОК** → переходите к Шагу 2

---

## 📍 Шаг 2: Добавить Полный UI (10 минут)

**Цель:** Добавить формы, настройки, инструкции (но без backend)

**Действия:**

1. Откройте `supabase-bridge.php`
2. Найдите функцию `sb_render_webhooks_tab()`
3. **Замените весь код** функции кодом из `webhooks-tab-full-code.php`

**Что получится:**
- ✅ Configuration form (Enable, Webhook URL)
- ✅ Status indicators (✅/❌)
- ✅ Supabase Setup instructions (collapsible sections)
- ✅ Test button (stub, но UI готов)
- ✅ Architecture diagram

**Тестирование:**
1. Откройте вкладку Webhooks
2. Заполните Webhook URL (любой, например: `https://hooks.n8n.cloud/webhook/test`)
3. Включите "Enable Webhooks"
4. Сохраните → должно показать "Settings saved!"
5. Проверьте "Current Status" — должно показать ✅ Enabled
6. Раскройте collapsible sections (Step 1, 2, 3) — должны открываться
7. Кликните "Send Test Webhook" — должен показать loading, потом stub message

✅ **Если всё ОК** → UI готов! Дайте feedback

---

## 📍 Шаг 3: Backend Implementation (после утверждения UI)

**Пока НЕ делаем!** Сначала утвердим UI/UX.

**Что нужно будет:**
1. AJAX handler для `sb_webhook_ajax_send_test()`
2. AJAX handler для `sb_webhook_ajax_get_logs()`
3. Supabase deployment (SQL + Edge Function)

---

## 🔄 Откат (если нужно)

### Откатить Шаг 2 (вернуть placeholder):
1. Откройте `supabase-bridge.php`
2. Найдите `sb_render_webhooks_tab()`
3. Замените код на простой placeholder из `ADD-WEBHOOKS-TAB.md` (Изменение 3)

### Откатить Шаг 1 (удалить вкладку):
1. Удалите код из навигации (Изменение 1)
2. Удалите `elseif` блок (Изменение 2)
3. Удалите функцию `sb_render_webhooks_tab()`

---

## 📊 Checklist

### После Шага 1:
- [ ] 3 вкладки видны в Settings
- [ ] Webhooks tab показывает placeholder
- [ ] Другие вкладки работают

### После Шага 2:
- [ ] Configuration form работает
- [ ] Settings сохраняются
- [ ] Status indicators обновляются
- [ ] Collapsible sections открываются
- [ ] Test button показывает stub message

### Feedback:
- [ ] UI понятный и удобный
- [ ] Инструкции clear
- [ ] Ничего не сломано в других вкладках

---

## 💬 Feedback Format

Напишите:
- ✅ **Что работает хорошо**
- ❌ **Что непонятно/неудобно**
- 💡 **Предложения по улучшению**

---

## 📁 Файлы

```
webhook-system/
├── QUICK-START.md              ← Вы здесь
├── ADD-WEBHOOKS-TAB.md         ← Шаг 1: Инструкции для placeholder
├── webhooks-tab-full-code.php  ← Шаг 2: Код для полного UI
├── DEPLOYMENT.md               ← Шаг 3: Backend deployment (позже)
└── README.md                   ← Обзор всего
```

---

*Quick Start Guide v0.8.0*
*UI-first approach для безопасной интеграции*
