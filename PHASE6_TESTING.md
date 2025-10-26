# Phase 6 Testing Guide - Registration Logging to Supabase

**Version:** 0.5.0-dev
**Date:** 2025-10-25
**Status:** ✅ READY FOR TESTING

---

## 🎯 What Was Implemented

Phase 6 logs user registrations to Supabase for analytics:

1. ✅ `sb_log_registration_to_supabase()` - INSERT into `wp_user_registrations`
2. ✅ Called after successful user creation in `sb_handle_callback()`
3. ✅ Logs: user_id, email, registration_url, pair_id (if found)
4. ✅ Non-blocking: If INSERT fails, authentication still works
5. ✅ Error logging: Failures logged to WordPress debug.log

**Key principle:** Non-critical analytics - never breaks authentication!

---

## 📋 Testing Checklist

### Test 1: Registration Logging - With Pair ⚡ (5 minutes)

**Purpose:** Verify registration logged with pair_id when pair exists

**Setup:**
1. Create registration pair in Settings:
   - Registration: `/test-reg/` → Thank You: `/test-ty/`
2. Note the pair UUID in Supabase `wp_registration_pairs` table

**Steps:**
1. Open `/test-reg/` page (with auth form)
2. Test Magic Link authentication with NEW email (e.g., test1@example.com)
3. Complete authentication (user created in WordPress)

**Verification in Supabase:**
1. Go to Supabase Dashboard → Table Editor
2. Select `wp_user_registrations` table
3. Find latest row

**Expected result:**
```
user_id: <Supabase user UUID>
pair_id: <UUID from wp_registration_pairs> ✅
user_email: test1@example.com
registration_url: /test-reg/
registered_at: <current timestamp>
```

**Pass criteria:** Row inserted with correct pair_id

---

### Test 2: Registration Logging - Without Pair ⚡ (3 minutes)

**Purpose:** Verify registration logged with NULL pair_id when no pair

**Steps:**
1. Open page WITHOUT registration pair: `/random-page/` (with auth form)
2. Test Magic Link authentication with NEW email (e.g., test2@example.com)
3. Complete authentication

**Verification in Supabase:**
1. Check `wp_user_registrations` table
2. Find latest row for test2@example.com

**Expected result:**
```
user_id: <Supabase user UUID>
pair_id: NULL ✅
user_email: test2@example.com
registration_url: /random-page/
registered_at: <current timestamp>
```

**Pass criteria:** Row inserted with NULL pair_id (no pair found - OK!)

---

### Test 3: Multiple Registrations Analytics ⚡ (5 minutes)

**Purpose:** Verify multiple registrations from different pages tracked correctly

**Setup:**
1. Create 2 pairs in Settings:
   - `/services/` → `/services-thankyou/`
   - `/webinar/` → `/webinar-thankyou/`

**Steps:**
1. Register user1@example.com from `/services/`
2. Register user2@example.com from `/webinar/`
3. Register user3@example.com from `/login/` (no pair)

**Verification in Supabase:**
1. Query `wp_user_registrations`:
   ```sql
   SELECT user_email, registration_url, pair_id
   FROM wp_user_registrations
   ORDER BY registered_at DESC
   LIMIT 3;
   ```

**Expected result:**
| user_email | registration_url | pair_id |
|------------|-----------------|---------|
| user3@... | /login/ | NULL |
| user2@... | /webinar/ | <webinar-pair-uuid> |
| user1@... | /services/ | <services-pair-uuid> |

**Pass criteria:** Each registration tracked with correct pair_id

---

### Test 4: Analytics Query - Conversions by Page ⚡ (2 minutes)

**Purpose:** Verify data useful for analytics

**Supabase SQL Query:**
```sql
-- Count registrations by page
SELECT
  registration_url,
  COUNT(*) as registrations
FROM wp_user_registrations
GROUP BY registration_url
ORDER BY registrations DESC;
```

**Expected result:**
```
registration_url | registrations
-----------------|-------------
/services/       | 5
/webinar/        | 3
/login/          | 2
```

**Pass criteria:** Data useful for measuring which pages convert best

---

### Test 5: Analytics Query - Pair Performance ⚡ (2 minutes - Optional)

**Purpose:** Verify pair tracking enables A/B testing

**Supabase SQL Query:**
```sql
-- Registrations per pair
SELECT
  p.registration_page_url,
  p.thankyou_page_url,
  COUNT(r.id) as registrations
FROM wp_registration_pairs p
LEFT JOIN wp_user_registrations r ON r.pair_id = p.id
WHERE p.site_url = 'https://yoursite.com'
GROUP BY p.id, p.registration_page_url, p.thankyou_page_url
ORDER BY registrations DESC;
```

**Expected result:**
Shows which registration/thank-you pairs are most effective

**Pass criteria:** Query runs successfully, data makes sense

---

### Test 6: Non-Blocking Behavior - Supabase Down ⚡ (3 minutes)

**Purpose:** Verify authentication works even if Supabase logging fails

**Steps:**
1. Temporarily change Supabase URL in Settings to invalid:
   - Change: `https://yourproject.supabase.co`
   - To: `https://invalid-test.supabase.co`
2. Save Settings
3. Test Magic Link authentication

**Expected result:**
- ✅ Authentication WORKS (user created in WordPress)
- ✅ User can log in successfully
- ❌ Registration NOT logged to Supabase (expected failure)

**Check WordPress logs:**
```bash
tail -f debug.log
# Should see: "Supabase Bridge: Registration log failed - ..."
```

**Restore credentials:**
1. Fix Supabase URL back to correct value
2. Save Settings

**Pass criteria:** Authentication works even if Supabase unavailable

---

### Test 7: Existing User - No Duplicate Logs ⚡ (2 minutes)

**Purpose:** Verify only NEW user registrations logged (not every login)

**Steps:**
1. Register NEW user: test@example.com from `/test-reg/`
2. Check Supabase: 1 row for test@example.com ✅
3. Log out
4. Log in again with same user: test@example.com
5. Check Supabase again

**Expected result:**
- Still 1 row for test@example.com (NOT 2 rows)
- Logging only happens on user CREATION, not login

**Pass criteria:** No duplicate logs for existing users

---

### Test 8: Data Integrity ⚡ (2 minutes)

**Purpose:** Verify foreign key constraint works correctly

**Test 1: Delete pair → pair_id becomes NULL**
1. Create pair: `/test/` → `/test-ty/`
2. Register user from `/test/`
3. Check `wp_user_registrations`: pair_id = <uuid> ✅
4. Delete pair in WordPress Settings
5. Check `wp_user_registrations` again

**Expected result:**
- pair_id = NULL (foreign key ON DELETE SET NULL works)
- Row NOT deleted (only pair_id nullified)

**Test 2: User_id integrity**
1. Check that user_id matches Supabase Auth user UUID
2. Same UUID in both `auth.users` and `wp_user_registrations`

**Pass criteria:** Foreign key constraints work correctly

---

## 🛡️ Rollback Procedure

If Phase 6 causes issues:

### Option 1: Remove logging code only
```bash
git diff HEAD~1 supabase-bridge.php
# Revert:
# - Lines 302-364 (sb_log_registration_to_supabase function)
# - Lines 736-744 (logging call in sb_handle_callback)
```

### Option 2: Clear logs (keep code)
```sql
-- Supabase SQL Editor
DELETE FROM wp_user_registrations WHERE site_url = 'https://yoursite.com';
```

### Option 3: Full rollback to Phase 5
```bash
git revert HEAD
```

**Note:** Phase 6 is non-critical - authentication works even if logging fails

---

## ✅ Success Criteria

Phase 6 is successful if:

- ✅ New user registrations logged to `wp_user_registrations`
- ✅ Correct pair_id when pair exists
- ✅ NULL pair_id when no pair (graceful)
- ✅ Non-blocking: Authentication works even if logging fails
- ✅ No duplicate logs for existing users (only on creation)
- ✅ Data integrity: Foreign keys work correctly
- ✅ **Existing authentication works identically (zero regressions)**
- ✅ Error logging for failed inserts

---

## 🐛 Known Limitations

1. **No retry mechanism**
   - If Supabase down during registration, log lost
   - Not critical: user still created in WordPress
   - Future: Could add queue/retry

2. **No referrer = no log**
   - If referrer header missing, registration_url unknown
   - Rare: browsers usually send referrer
   - Logged to debug.log if happens

3. **No bulk import**
   - Existing WordPress users not retroactively logged
   - Only tracks NEW registrations going forward

---

## 📊 Testing Results Template

```
Date: _________
Tester: _________

Test 1 (Logging with Pair): [ ] PASS [ ] FAIL
Test 2 (Logging without Pair): [ ] PASS [ ] FAIL
Test 3 (Multiple Registrations): [ ] PASS [ ] FAIL
Test 4 (Analytics Query - Conversions): [ ] PASS [ ] FAIL
Test 5 (Analytics Query - Pairs): [ ] PASS [ ] FAIL [ ] SKIPPED
Test 6 (Non-Blocking): [ ] PASS [ ] FAIL
Test 7 (No Duplicate Logs): [ ] PASS [ ] FAIL
Test 8 (Data Integrity): [ ] PASS [ ] FAIL

Notes:
_________________________________________
_________________________________________

Phase 6 Status: [ ] APPROVED [ ] NEEDS FIXES
```

---

## 🚀 Next Steps (Post-Phase 6)

After Phase 6 approved:
- All 6 phases complete! 🎉
- Test entire flow end-to-end
- Document final features
- Consider:
  - Webhooks based on `wp_user_registrations`
  - Analytics dashboard
  - Email sequences per registration page

---

## 🎓 Analytics Use Cases

**What You Can Do With This Data:**

1. **Conversion Tracking:**
   ```sql
   SELECT
     registration_url,
     COUNT(*) as conversions,
     DATE(registered_at) as date
   FROM wp_user_registrations
   GROUP BY registration_url, DATE(registered_at)
   ORDER BY date DESC;
   ```

2. **A/B Testing:**
   - Create multiple registration pairs for same audience
   - Compare conversion rates
   - Optimize thank you pages

3. **Attribution:**
   - Know which marketing pages drive registrations
   - ROI per landing page

4. **Triggers/Webhooks:**
   - Supabase Database Webhooks on INSERT
   - Trigger emails/sequences based on registration_url
   - Different onboarding per source

---

**Last Updated:** 2025-10-25
**Phase:** 6 of 6 (FINAL!)
**Status:** Ready for Testing
