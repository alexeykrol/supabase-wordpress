# Phase 4 Testing Guide - JavaScript Injection

**Version:** 0.5.0-dev
**Date:** 2025-10-25
**Status:** ✅ READY FOR TESTING

---

## 🎯 What Was Implemented

Phase 4 injects registration pairs into JavaScript for frontend availability:

1. ✅ Fetch pairs from `wp_options` in `wp_enqueue_scripts` hook
2. ✅ Prepare pairs for JavaScript (only URL fields)
3. ✅ Inject into `window.SUPABASE_CFG.registrationPairs` array
4. ✅ Available globally on all frontend pages
5. ✅ No logic changes - data only (Phase 5 will use this data)

**Key principle:** Data injection only - existing redirect logic unchanged until Phase 5.

---

## 📋 Testing Checklist

### Test 1: Verify JavaScript Injection ⚡ (2 minutes)

**Steps:**
1. Ensure you have at least 2 registration pairs created:
   - Go to: Supabase Bridge → Registration Pairs
   - Add pairs if needed (e.g., "/services/" → "/services-thankyou/")
2. Open any page with `[supabase_auth_form]` shortcode (e.g., `/newlogin/`)
3. Open Browser DevTools (F12)
4. Go to Console tab
5. Type: `window.SUPABASE_CFG`

**Expected result:**
```javascript
{
  url: "https://yourproject.supabase.co",
  anon: "your-anon-key...",
  thankYouUrl: "/thank-you/",           // Global fallback
  registrationPairs: [                  // NEW in Phase 4
    {
      registration_url: "/services/",
      thankyou_url: "/services-thankyou/"
    },
    {
      registration_url: "/login/",
      thankyou_url: "/welcome/"
    }
  ]
}
```

**Pass criteria:**
- `registrationPairs` array exists
- Contains all pairs from WordPress Settings
- Each pair has `registration_url` and `thankyou_url`

---

### Test 2: Empty Pairs Handling ⚡ (1 minute)

**Steps:**
1. Go to: Supabase Bridge → Registration Pairs
2. Delete all pairs
3. Refresh page with auth form
4. Open Console
5. Type: `window.SUPABASE_CFG.registrationPairs`

**Expected result:**
```javascript
[]  // Empty array
```

**Pass criteria:** Empty array when no pairs (not undefined, not null)

---

### Test 3: Data Structure Validation ⚡ (2 minutes)

**Purpose:** Verify data is clean and ready for Phase 5

**Steps:**
1. Create pair: "/test-reg/" → "/test-ty/"
2. Refresh page with auth form
3. Console: `window.SUPABASE_CFG.registrationPairs[0]`

**Expected result:**
```javascript
{
  registration_url: "/test-reg/",  // Path only (not full URL)
  thankyou_url: "/test-ty/"        // Path only (not full URL)
}
```

**Should NOT include:**
- ❌ `id` (internal UUID)
- ❌ `registration_page_id` (WordPress page ID)
- ❌ `thankyou_page_id` (WordPress page ID)
- ❌ `created_at` (timestamp)
- ❌ `site_url` (WordPress site URL)

**Pass criteria:** Only `registration_url` and `thankyou_url` fields present

---

### Test 4: Multiple Pages Test ⚡ (2 minutes)

**Purpose:** Verify injection works on all pages, not just specific ones

**Steps:**
1. Create 2 pairs in Settings
2. Visit page WITH auth form: `/newlogin/`
   - Check: `window.SUPABASE_CFG.registrationPairs` exists ✅
3. Visit page WITHOUT auth form: Home page `/`
   - Check: `window.SUPABASE_CFG.registrationPairs` exists ✅
4. Visit another random page: `/about/`
   - Check: `window.SUPABASE_CFG.registrationPairs` exists ✅

**Expected result:**
- Pairs available on ALL frontend pages (not admin)
- Same data everywhere

**Pass criteria:** Consistent data across all pages

---

### Test 5: No Logic Change Verification ⚡ (3 minutes)

**CRITICAL:** Verify existing redirect still uses global Thank You Page

**Steps:**
1. Set global Thank You Page in Settings: e.g., "/thank-you/"
2. Create registration pair:
   - Registration: "/special-reg/" → Thank You: "/special-ty/"
3. Open `/special-reg/` page with auth form
4. Test Magic Link authentication
5. After authentication, check redirect URL

**Expected result:**
- Redirect should go to GLOBAL Thank You Page: `/thank-you/`
- NOT to pair-specific page: `/special-ty/`
- (Phase 5 will change this behavior)

**Why:** Phase 4 only injects data - doesn't use it yet.

**Pass criteria:** Existing redirect logic unchanged

---

### Test 6: Check for JavaScript Errors ⚡ (1 minute)

**Steps:**
1. Open page with auth form
2. Open Console (F12)
3. Check for any red errors

**Expected result:**
- No JavaScript errors
- No "undefined" errors
- `window.SUPABASE_CFG` loads cleanly

**Pass criteria:** Zero JavaScript errors

---

### Test 7: Admin Page Exclusion ⚡ (1 minute)

**Purpose:** Verify pairs NOT injected in WordPress Admin

**Steps:**
1. Go to WordPress Admin: `/wp-admin/`
2. Open Console (F12)
3. Type: `window.SUPABASE_CFG`

**Expected result:**
```javascript
undefined  // Not injected in admin
```

**Why:** `if (is_admin()) return;` in code prevents injection in admin

**Pass criteria:** `SUPABASE_CFG` undefined in admin area

---

### Test 8: Performance Check ⚡ (2 minutes - Optional)

**Purpose:** Verify injection doesn't slow down page load

**Steps:**
1. Create 10 registration pairs in Settings
2. Refresh page with auth form
3. Open DevTools → Network tab
4. Check inline script size

**Expected result:**
- Inline script with pairs: < 5 KB (very small)
- Page load time: No noticeable difference

**Pass criteria:** Minimal performance impact

---

## 🛡️ Rollback Procedure

If Phase 4 causes issues:

### Option 1: Remove JavaScript injection only
```bash
git diff HEAD~1 supabase-bridge.php
# Revert lines 386-402 to original (remove Phase 4 code)
```

### Option 2: Full rollback to Phase 3
```bash
git revert HEAD
```

**Note:** Phase 4 is data-only - very safe, low risk

---

## ✅ Success Criteria

Phase 4 is successful if:

- ✅ `window.SUPABASE_CFG.registrationPairs` exists on frontend
- ✅ Contains all pairs from WordPress Settings
- ✅ Data structure: only `registration_url` and `thankyou_url`
- ✅ Empty array when no pairs (not undefined)
- ✅ Available on all frontend pages
- ✅ NOT available in WordPress Admin
- ✅ **Existing redirect logic unchanged (zero regressions)**
- ✅ No JavaScript errors

---

## 🐛 Known Limitations

1. **Global injection (all pages)**
   - Pairs injected even on pages without auth form
   - Minimal impact: ~1-2 KB data
   - Future: Could optimize to inject only on pages with shortcode

2. **No current page detection**
   - Array contains all pairs
   - Phase 5 will filter by current page URL

3. **No deduplication**
   - If same registration page in multiple pairs, both included
   - Shouldn't happen due to duplicate prevention in Phase 2

---

## 📊 Testing Results Template

```
Date: _________
Tester: _________

Test 1 (JavaScript Injection): [ ] PASS [ ] FAIL
Test 2 (Empty Pairs): [ ] PASS [ ] FAIL
Test 3 (Data Structure): [ ] PASS [ ] FAIL
Test 4 (Multiple Pages): [ ] PASS [ ] FAIL
Test 5 (No Logic Change): [ ] PASS [ ] FAIL
Test 6 (JavaScript Errors): [ ] PASS [ ] FAIL
Test 7 (Admin Exclusion): [ ] PASS [ ] FAIL
Test 8 (Performance): [ ] PASS [ ] FAIL [ ] SKIPPED

Notes:
_________________________________________
_________________________________________

Phase 4 Status: [ ] APPROVED [ ] NEEDS FIXES
```

---

## 🚀 Next Steps (Phase 5)

After Phase 4 approved:
- Phase 5: Update redirect logic to use page-specific pairs
- Modify redirect logic in REST endpoint
- Fallback to global Thank You Page if no pair found
- Critical phase - changes existing behavior!

---

**JavaScript Usage Example (for Phase 5):**

```javascript
// Phase 5 will use this data like:
const currentPath = window.location.pathname;
const pair = window.SUPABASE_CFG.registrationPairs.find(
  p => p.registration_url === currentPath
);

const redirectUrl = pair
  ? pair.thankyou_url                    // Page-specific
  : window.SUPABASE_CFG.thankYouUrl;     // Global fallback
```

---

**Last Updated:** 2025-10-25
**Phase:** 4 of 6
**Status:** Ready for Testing
