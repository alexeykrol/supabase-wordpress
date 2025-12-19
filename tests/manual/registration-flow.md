# Manual Test Checklist: Registration Flow

**Purpose:** Test complete registration flow with OAuth providers
**Duration:** ~10-15 minutes
**Frequency:** Before each release, after major changes

---

## 🧪 Test Environment

- [ ] Browser: Chrome (normal mode)
- [ ] Browser: Chrome (incognito)
- [ ] Browser: Safari (normal mode)
- [ ] Browser: Safari (private)
- [ ] Browser: Firefox (normal mode)
- [ ] Browser: Firefox (private)

---

## 📋 Pre-Test Setup

### Registration Pairs Configuration

1. [ ] Login to WordPress Admin
2. [ ] Navigate to: **Supabase Bridge** → **🔗 Registration Pairs**
3. [ ] Verify existing pair:
   - Registration Page: `/test-no-elem/`
   - Thank You Page: `/currency-chatgpt/`
4. [ ] Note: Create additional pairs if testing multiple pages

### Test Email Accounts

Prepare fresh email addresses for each test:
- [ ] Google OAuth: `testuser+TIMESTAMP@gmail.com`
- [ ] Facebook OAuth: Use test user account
- [ ] Magic Link: `testuser+TIMESTAMP@gmail.com`

**Tip:** Use `+timestamp` suffix to create unique emails (e.g., `user+20251218@gmail.com`)

---

## 🔵 Test 1: Google OAuth Registration

### Setup
- [ ] Clear browser cache and cookies
- [ ] Open browser in incognito/private mode
- [ ] Logout from all Google accounts

### Test Steps

1. **Navigate to auth form**
   - [ ] Go to: `https://alexeykrol.com/test-no-elem/`
   - [ ] Page loads successfully
   - [ ] "Login with Google" button visible

2. **Initiate Google OAuth**
   - [ ] Click "Login with Google"
   - [ ] Redirects to Google login page
   - [ ] URL contains `accounts.google.com`

3. **Authenticate with Google**
   - [ ] Enter new email (use +timestamp suffix)
   - [ ] Enter password
   - [ ] Complete 2FA if required
   - [ ] Click "Allow" for permissions

4. **Verify redirect chain**
   - [ ] Redirects to Supabase
   - [ ] Redirects to `/test-no-elem-2/` (callback handler)
   - [ ] Shows "Processing authentication..." spinner
   - [ ] Shows "✓ Welcome! Redirecting..." success message

5. **Verify final redirect**
   - [ ] ✅ **EXPECTED:** Redirects to `/currency-chatgpt/` (Thank You page from Registration Pairs)
   - [ ] ❌ **FAIL IF:** Redirects to `/registr/` or `/account/` (wrong page)
   - [ ] ❌ **FAIL IF:** Stays on callback page

6. **Verify user creation**
   - [ ] Go to: **WordPress Admin** → **Users**
   - [ ] New user exists with correct email
   - [ ] User metadata contains `supabase_user_id`
   - [ ] User role is "Subscriber"

### Expected Console Logs (F12 → Console)

```
🔗 Login return URL detected: https://...
📝 Registration page URL: /test-no-elem/
```

### Expected Result
✅ **PASS:** User redirected to `/currency-chatgpt/`
❌ **FAIL:** User redirected elsewhere

---

## 🔵 Test 2: Facebook OAuth Registration

### Setup
- [ ] Clear browser cache and cookies
- [ ] Open fresh incognito/private window
- [ ] Logout from all Facebook accounts

### Test Steps

1. **Navigate to auth form**
   - [ ] Go to: `https://alexeykrol.com/test-no-elem/`

2. **Initiate Facebook OAuth**
   - [ ] Click "Login with Facebook"
   - [ ] Redirects to Facebook login page

3. **Authenticate with Facebook**
   - [ ] Enter Facebook test user credentials
   - [ ] Click "Continue as [Name]"

4. **Verify redirect chain**
   - [ ] Redirects through Supabase → Callback → Thank You page
   - [ ] Final destination: `/currency-chatgpt/`

5. **Verify user creation**
   - [ ] Check WordPress users
   - [ ] New user exists with Facebook email

### Expected Result
✅ **PASS:** User redirected to `/currency-chatgpt/`
❌ **FAIL:** User redirected elsewhere

---

## 🔵 Test 3: Magic Link Registration

### Setup
- [ ] Clear browser cache and cookies
- [ ] Have email inbox ready (Gmail recommended)

### Test Steps

1. **Navigate to auth form**
   - [ ] Go to: `https://alexeykrol.com/test-no-elem/`

2. **Request Magic Link**
   - [ ] Click "Magic Link"
   - [ ] Enter email: `testuser+TIMESTAMP@gmail.com`
   - [ ] Click "Send Magic Link"
   - [ ] See confirmation: "Check your email..."

3. **Open Magic Link email**
   - [ ] Go to email inbox
   - [ ] Find email from Supabase
   - [ ] Subject: "Confirm your signup"
   - [ ] Click "Confirm your mail" button

4. **Verify redirect chain**
   - [ ] Opens in browser
   - [ ] Redirects through Supabase → Callback → Thank You page
   - [ ] Final destination: `/currency-chatgpt/`

5. **Verify user creation**
   - [ ] Check WordPress users
   - [ ] New user exists with email address

### Expected Result
✅ **PASS:** User redirected to `/currency-chatgpt/`
❌ **FAIL:** User redirected elsewhere

---

## 🔵 Test 4: Existing User Login (Return-to-Origin)

**Purpose:** Verify existing users return to origin page, NOT Thank You page

### Setup
- [ ] Use email from previous test (already registered)
- [ ] Clear cookies but keep user in WordPress

### Test Steps

1. **Logout from WordPress**
   - [ ] Click "Logout" in WordPress admin

2. **Navigate to different page**
   - [ ] Go to: `https://alexeykrol.com/premium-course/`
   - [ ] Note: This is NOT the registration page

3. **Click "Login" button**
   - [ ] Redirects to: `/test-no-elem/` (auth form)
   - [ ] localStorage saves: `login_return_url = /premium-course/`

4. **Login with existing credentials**
   - [ ] Use Google/Facebook with SAME email as before
   - [ ] Complete OAuth flow

5. **Verify redirect**
   - [ ] ✅ **EXPECTED:** Redirects to `/premium-course/` (where you started)
   - [ ] ❌ **FAIL IF:** Redirects to `/currency-chatgpt/` (Thank You page)

### Expected Result
✅ **PASS:** Existing user returns to `/premium-course/`
❌ **FAIL:** User redirected to Thank You page

---

## 🔵 Test 5: Cross-Browser Compatibility

Repeat Test 1 (Google OAuth) in each browser:

### Chrome
- [ ] Normal mode: ✅ PASS / ❌ FAIL
- [ ] Incognito: ✅ PASS / ❌ FAIL

### Safari
- [ ] Normal mode: ✅ PASS / ❌ FAIL
- [ ] Private: ✅ PASS / ❌ FAIL

### Firefox
- [ ] Normal mode: ✅ PASS / ❌ FAIL
- [ ] Private: ✅ PASS / ❌ FAIL

---

## 🔵 Test 6: Mobile Browsers (Optional)

- [ ] iOS Safari
- [ ] iOS Chrome
- [ ] Android Chrome
- [ ] Android Samsung Browser

---

## 🐛 Common Issues & Debugging

### Issue: Redirects to wrong page

**Debug Steps:**
1. Open browser console (F12)
2. Check console logs for:
   ```
   📝 Registration page URL: /test-no-elem/
   User type: NEW (registration) / EXISTING (login)
   REGISTRATION → Thank you page: /currency-chatgpt/
   ```
3. Check localStorage:
   ```javascript
   localStorage.getItem('registration_page_url')
   localStorage.getItem('login_return_url')
   ```

### Issue: User not created

**Debug Steps:**
1. SSH to server
2. Check WordPress debug log:
   ```bash
   tail -f /path/to/wp-content/debug.log | grep "Supabase Bridge"
   ```
3. Look for:
   - JWT verification errors
   - User creation errors
   - Database connection issues

### Issue: Stays on callback page

**Debug Steps:**
1. Check browser console for JavaScript errors
2. Verify WordPress API responds:
   ```bash
   curl -X POST https://alexeykrol.com/wp-json/supabase/v1/callback \
     -H "Content-Type: application/json" \
     -d '{"access_token":"test"}'
   ```

---

## ✅ Test Completion Checklist

- [ ] All 6 tests completed
- [ ] All browsers tested
- [ ] No failures recorded
- [ ] All debug logs reviewed
- [ ] Issues documented (if any)

---

## 📊 Test Results Template

**Date:** YYYY-MM-DD
**Tester:** Your Name
**Version:** v0.9.8

| Test | Chrome | Safari | Firefox | Result |
|------|--------|--------|---------|--------|
| Google OAuth | ✅ | ✅ | ✅ | PASS |
| Facebook OAuth | ✅ | ✅ | ✅ | PASS |
| Magic Link | ✅ | ✅ | ✅ | PASS |
| Existing User | ✅ | ✅ | ✅ | PASS |

**Notes:** [Any observations, bugs found, or edge cases]

---

**Next Steps:**
- If all tests pass → Mark as ready for production
- If failures found → Document issues in GitHub/BACKLOG.md
- If inconsistent → Run again in different environment
