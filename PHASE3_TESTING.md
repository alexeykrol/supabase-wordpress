# Phase 3 Testing Guide - Supabase Sync

**Version:** 0.5.0-dev
**Date:** 2025-10-25
**Status:** ✅ READY FOR TESTING

---

## 🎯 What Was Implemented

Phase 3 adds automatic Supabase synchronization when saving/deleting pairs:

1. ✅ `sb_sync_pair_to_supabase()` - UPSERT pair to Supabase
2. ✅ `sb_delete_pair_from_supabase()` - DELETE pair from Supabase
3. ✅ Integration in `sb_ajax_save_pair()` - sync after wp_options save
4. ✅ Integration in `sb_ajax_delete_pair()` - delete from Supabase after wp_options delete
5. ✅ Non-blocking: If Supabase fails, wp_options still works
6. ✅ Error logging: Failures logged to WordPress debug.log

**Key principle:** Graceful degradation - local (wp_options) always works, Supabase is "best effort".

---

## 📋 Testing Checklist

### Test 1: Verify Supabase Tables Exist ⚡ (2 minutes)

**Steps:**
1. Go to Supabase Dashboard → SQL Editor
2. Run query:
   ```sql
   SELECT * FROM wp_registration_pairs;
   ```

**Expected result:**
- Query runs successfully (no "relation does not exist" error)
- Table may be empty or have old data - that's OK

**Pass criteria:** Table exists and is queryable

---

### Test 2: Add New Pair with Supabase Sync ⚡ (3 minutes)

**Steps:**
1. Go to WordPress Admin → Supabase Bridge → Registration Pairs tab
2. Click "➕ Add New Pair"
3. Select pages:
   - Registration Page: "Test Registration" (or any page)
   - Thank You Page: "Test Thank You" (or any page)
4. Click "💾 Save Pair"

**Expected result:**
- Alert: "✅ Pair saved successfully!"
- Page reloads
- Table shows new pair in WordPress

**Verification in Supabase:**
1. Go to Supabase Dashboard → Table Editor
2. Select `wp_registration_pairs` table
3. Find new row with:
   - `site_url`: Your WordPress site URL
   - `registration_page_url`: `/test-registration/` (or your page)
   - `thankyou_page_url`: `/test-thank-you/` (or your page)
   - `created_at`: Current timestamp

**Pass criteria:** Pair appears in both WordPress AND Supabase

---

### Test 3: Delete Pair with Supabase Sync ⚡ (2 minutes)

**Steps:**
1. In Registration Pairs table, find pair created in Test 2
2. Click "🗑️ Delete" button
3. Confirm dialog: Click OK

**Expected result:**
- Alert: "✅ Pair deleted successfully!"
- Pair removed from WordPress table

**Verification in Supabase:**
1. Go to Supabase Dashboard → Table Editor
2. Check `wp_registration_pairs` table
3. Pair should be DELETED (no longer visible)

**Pass criteria:** Pair deleted from both WordPress AND Supabase

---

### Test 4: Edit Pair with Supabase Sync ⚡ (2 minutes)

**Steps:**
1. Create a new pair (Registration: "Edit Test", Thank You: "Original")
2. Note the pair ID in Supabase (UUID)
3. In WordPress, click "✏️ Edit" on that pair
4. Change Thank You Page to different page
5. Click "💾 Save Pair"

**Expected result:**
- WordPress: Pair updated successfully
- Supabase: Same UUID, but `thankyou_page_url` and `updated_at` changed

**Pass criteria:** UPSERT works - same ID, updated data

---

### Test 5: Graceful Degradation - Invalid Credentials ⚡ (3 minutes)

**Purpose:** Verify wp_options still works if Supabase fails

**Steps:**
1. Go to: Supabase Bridge → General Settings tab
2. Temporarily change Supabase URL to invalid value:
   - Change: `https://yourproject.supabase.co`
   - To: `https://invalid-test-12345.supabase.co`
3. Click "Save Settings"
4. Go to: Registration Pairs tab
5. Add new pair: Registration "Failover Test", Thank You "Test"
6. Click "💾 Save Pair"

**Expected result:**
- ✅ Alert: "Pair saved successfully!" (WordPress works!)
- ✅ Pair appears in WordPress table
- ❌ Pair NOT in Supabase (because credentials invalid)

**Check WordPress logs (optional):**
```bash
# In WordPress root or wp-content
tail -f debug.log
# Should see: "Supabase Bridge: Sync failed - ..."
```

**Restore credentials:**
1. Go to General Settings
2. Fix Supabase URL back to correct value
3. Save Settings

**Pass criteria:** WordPress functionality unaffected by Supabase failure

---

### Test 6: Data Consistency ⚡ (2 minutes)

**Steps:**
1. Create 3 pairs in WordPress
2. Verify all 3 appear in Supabase `wp_registration_pairs` table
3. Delete 1 pair in WordPress
4. Verify it's deleted in Supabase
5. Delete another pair in WordPress
6. Verify it's deleted in Supabase

**Expected result:**
- WordPress count: 1 pair remaining
- Supabase count: 1 pair remaining (same pair)
- All IDs match between WordPress and Supabase

**Pass criteria:** Perfect sync - same data in both places

---

### Test 7: Multiple Sites (Advanced) ⚡ (5 minutes - Optional)

**Purpose:** Verify `site_url` column works for multi-site setups

**Steps:**
1. Note current site URL in Supabase data: `https://yoursite.com`
2. Create pair in WordPress
3. Check Supabase: `site_url` should match current WordPress site

**If you have multiple WordPress sites:**
- Each site creates its own rows in Supabase
- `site_url` column differentiates them
- Each site only sees its own pairs in wp_options

**Pass criteria:** `site_url` correctly identifies source WordPress site

---

### Test 8: Existing Functionality Unchanged ⚡ (3 minutes)

**CRITICAL:** Verify existing auth still works!

**Steps:**
1. Go to: Supabase Bridge → General Settings tab
2. Verify all settings intact
3. Open login page (e.g., `/newlogin/`)
4. Test Magic Link authentication
5. Verify user created in WordPress
6. Verify redirect to Thank You page (from General Settings global default)

**Expected result:**
- Form displays correctly
- Authentication works
- User created in WordPress
- All existing Phase 1-2 functionality works

**Pass criteria:** Zero regressions - all existing features work identically

---

## 🛡️ Rollback Procedure

If Phase 3 causes issues:

### Option 1: Remove Supabase sync code only
```bash
git diff HEAD~1 supabase-bridge.php
# Remove Phase 3 sections:
# - Lines 203-300 (sync functions)
# - Lines 1310-1324 (save sync call)
# - Lines 1376-1379 (delete sync call)
```

### Option 2: Clear Supabase data (keep code)
```sql
-- Supabase SQL Editor
DELETE FROM wp_registration_pairs WHERE site_url = 'https://yoursite.com';
```

### Option 3: Full rollback to Phase 2
```bash
git revert HEAD
```

**Note:** wp_options data is NOT affected by Supabase issues - always safe!

---

## ✅ Success Criteria

Phase 3 is successful if:

- ✅ Add pair → synced to Supabase
- ✅ Delete pair → deleted from Supabase
- ✅ Edit pair → updated in Supabase (UPSERT)
- ✅ Supabase fails → WordPress still works (graceful degradation)
- ✅ Data consistency: WordPress ↔ Supabase match
- ✅ **Existing authentication works identically (zero regressions)**
- ✅ Error logging works (check debug.log)

---

## 🐛 Known Limitations

1. **No retry mechanism**
   - If Supabase temporarily down, sync fails silently
   - Fix: Manually re-save pair in WordPress to retry sync

2. **No bulk sync tool**
   - If wp_options has data but Supabase empty, no "sync all" button
   - Workaround: Re-save each pair individually to trigger sync

3. **Supabase RLS policies required**
   - Tables must allow INSERT/UPDATE/DELETE for authenticated users
   - Already configured in Phase 1 `supabase-tables.sql`

---

## 📊 Testing Results Template

```
Date: _________
Tester: _________

Test 1 (Supabase Tables Exist): [ ] PASS [ ] FAIL
Test 2 (Add with Sync): [ ] PASS [ ] FAIL
Test 3 (Delete with Sync): [ ] PASS [ ] FAIL
Test 4 (Edit with UPSERT): [ ] PASS [ ] FAIL
Test 5 (Graceful Degradation): [ ] PASS [ ] FAIL
Test 6 (Data Consistency): [ ] PASS [ ] FAIL
Test 7 (Multiple Sites): [ ] PASS [ ] FAIL [ ] SKIPPED
Test 8 (Existing Functionality): [ ] PASS [ ] FAIL

Notes:
_________________________________________
_________________________________________

Phase 3 Status: [ ] APPROVED [ ] NEEDS FIXES
```

---

## 🚀 Next Steps (Phase 4)

After Phase 3 approved:
- Phase 4: Inject pairs into JavaScript (frontend availability)
- Pairs become available to `auth-form.html` via `window.sbRegistrationPairs`
- No logic changes yet - just data injection

---

**Last Updated:** 2025-10-25
**Phase:** 3 of 6
**Status:** Ready for Testing
