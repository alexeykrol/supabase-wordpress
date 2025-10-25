# Phase 2 Testing Guide - Registration Pairs UI

**Version:** 0.5.0-dev
**Date:** 2025-10-25
**Status:** ✅ READY FOR TESTING

---

## 🎯 What Was Implemented

Phase 2 adds Settings UI for managing registration/thank-you page pairs:

1. ✅ Two tabs in Settings (General, Registration Pairs)
2. ✅ Empty state with "Add New Pair" button
3. ✅ Table view of existing pairs
4. ✅ Modal dialog for Add/Edit operations
5. ✅ AJAX handlers for Create/Delete
6. ✅ Storage in `wp_options` (key: `sb_registration_pairs`)

**Key principle:** Completely isolated - doesn't sync to Supabase yet (Phase 3).

---

## 📋 Testing Checklist

### Test 1: Tabs Navigation ⚡ (1 minute)

**Steps:**
1. WordPress Admin → Supabase Bridge
2. You should see two tabs:
   - ⚙️ General Settings (active by default)
   - 🔗 Registration Pairs

3. Click "🔗 Registration Pairs" tab

**Expected result:**
- Tab switches without page reload
- URL changes to `?page=supabase-bridge-setup&tab=pairs`
- You see empty state: "📋 No registration pairs created yet"
- "➕ Add New Pair" button visible

**Pass criteria:** Tabs work, no JavaScript errors

---

### Test 2: Add New Pair ⚡ (3 minutes)

**Steps:**
1. Go to Registration Pairs tab
2. Click "➕ Add New Pair" button

**Expected result:**
- Modal dialog opens
- Title: "Add New Pair"
- Two dropdowns:
  - Registration Page (shows all pages)
  - Thank You Page (shows all pages)
- Buttons: "💾 Save Pair" and "Cancel"

**Now test functionality:**
3. Click "Cancel" → Modal closes ✅
4. Click "➕ Add New Pair" again
5. Select pages:
   - Registration Page: "newlogin" (or any page)
   - Thank You Page: "thankyoureg" (or any page)
6. Click "💾 Save Pair"

**Expected result:**
- Alert: "✅ Pair saved successfully!"
- Page reloads
- Table appears with 1 row showing:
  - Registration Page: "newlogin" with URL `/newlogin/`
  - Thank You Page: "thankyoureg" with URL `/thankyoureg/`
  - Created: Current date/time
  - Actions: ✏️ Edit, 🗑️ Delete

**Pass criteria:** Pair saves, displays in table correctly

---

### Test 3: Add Multiple Pairs ⚡ (2 minutes)

**Steps:**
1. Click "➕ Add New Pair" again
2. Select different pages:
   - Registration: Choose another page
   - Thank You: Choose another page
3. Click "💾 Save Pair"

**Expected result:**
- Table now shows 2 rows
- Each row displays correct page names and URLs
- Created timestamps are different

**Pass criteria:** Multiple pairs coexist without conflicts

---

### Test 4: Duplicate Prevention ⚡ (2 minutes)

**Steps:**
1. Click "➕ Add New Pair"
2. Select same Registration Page as first pair
3. Select any Thank You Page
4. Click "💾 Save Pair"

**Expected result:**
- Alert: "❌ Error: Registration page already has a pair"
- Modal stays open
- Table unchanged (still shows only 2 pairs)

**Pass criteria:** System prevents duplicate registration pages

---

### Test 5: Delete Pair ⚡ (2 minutes)

**Steps:**
1. In table, find first pair (e.g., "newlogin")
2. Click "🗑️ Delete" button
3. Confirm dialog: "Delete pair for 'newlogin'?"
4. Click OK

**Expected result:**
- Alert: "✅ Pair deleted successfully!"
- Page reloads
- Table now shows 1 row (second pair remains)

**Pass criteria:** Pair deleted successfully

---

### Test 6: Data Persistence ⚡ (1 minute)

**Steps:**
1. Note current pairs in table (e.g., 1 pair remaining)
2. Go to another WordPress admin page (e.g., Dashboard)
3. Return to: Supabase Bridge → Registration Pairs tab

**Expected result:**
- Pairs still there (not lost on navigation)
- Same data as before

**Verification (advanced):**
```php
// WordPress Admin → Tools → Site Health → Info → Constants
// Or run via WP-CLI:
wp option get sb_registration_pairs --format=json
```

**Pass criteria:** Data persists across page navigations

---

### Test 7: Edit Functionality ⚡ (1 minute - Placeholder)

**Steps:**
1. Click "✏️ Edit" button on any pair

**Expected result:**
- Alert: "Edit functionality coming in next commit"

**Why:** Edit requires loading pair data into modal - will be added if needed.

**Pass criteria:** Alert shows (edit not implemented yet)

---

### Test 8: Existing Functionality Unchanged ⚡ (3 minutes)

**CRITICAL:** Verify existing auth still works!

**Steps:**
1. Go to: Supabase Bridge → General Settings tab
2. All previous settings should be visible:
   - Supabase Credentials
   - Thank You Page selector
   - Database Status
   - Setup Instructions
3. Open your login page (e.g., `/newlogin/`)
4. Test Magic Link authentication

**Expected result:**
- Form displays correctly
- Authentication works
- User created in WordPress
- Redirects to Thank You page (from General Settings, NOT from pairs yet)

**Pass criteria:** Zero regressions - all existing auth works identically

---

## 🛡️ Rollback Procedure

If Phase 2 causes issues:

### Option 1: Revert code only
```bash
git diff HEAD~1 supabase-bridge.php
# Remove Phase 2 sections (lines 696-1262)
```

### Option 2: Clear pairs data
```php
// WordPress Admin → Tools → Site Health → Info
delete_option('sb_registration_pairs');
```

### Option 3: Full rollback
```bash
git revert HEAD
```

---

## ✅ Success Criteria

Phase 2 is successful if:

- ✅ Tabs work (General, Registration Pairs)
- ✅ Can add new pairs (modal + AJAX save)
- ✅ Can delete pairs (confirmation + AJAX delete)
- ✅ Duplicate prevention works
- ✅ Data persists in `wp_options`
- ✅ **Existing authentication works identically (zero regressions)**
- ✅ No JavaScript errors in console

---

## 🐛 Known Limitations

1. **Edit functionality placeholder**
   - Currently shows alert instead of editing
   - Can be added if needed (simple to implement)

2. **No Supabase sync yet**
   - Pairs stored only in WordPress `wp_options`
   - Supabase sync coming in Phase 3

3. **No validation for same page in both dropdowns**
   - System allows Registration Page = Thank You Page
   - Should add validation: `if (reg_id === ty_id) { error }`

---

## 📊 Testing Results Template

```
Date: _________
Tester: _________

Test 1 (Tabs Navigation): [ ] PASS [ ] FAIL
Test 2 (Add New Pair): [ ] PASS [ ] FAIL
Test 3 (Add Multiple Pairs): [ ] PASS [ ] FAIL
Test 4 (Duplicate Prevention): [ ] PASS [ ] FAIL
Test 5 (Delete Pair): [ ] PASS [ ] FAIL
Test 6 (Data Persistence): [ ] PASS [ ] FAIL
Test 7 (Edit Placeholder): [ ] PASS [ ] FAIL
Test 8 (Existing Functionality): [ ] PASS [ ] FAIL

Notes:
_________________________________________
_________________________________________

Phase 2 Status: [ ] APPROVED [ ] NEEDS FIXES
```

---

## 🚀 Next Steps (Phase 3)

After Phase 2 approved:
- Phase 3: Sync `wp_options` → Supabase (non-blocking)
- On save/delete: Also update Supabase table
- Use try/catch - if Supabase fails, wp_options still works

---

**Last Updated:** 2025-10-25
**Phase:** 2 of 6
**Status:** Ready for Testing
