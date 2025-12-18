# Cache Plugin Troubleshooting

**Critical:** Caching plugins can break authentication forms for non-logged-in users!

---

## 🔴 Common Symptom

**Problem:**
- Auth form **visible** when logged in ✅
- Auth form **invisible** when logged out ❌
- No "Supabase Auth initialized" in Console
- Form HTML missing from DOM

**Cause:** Caching plugin serves cached version without dynamic content.

---

## ✅ Solution: Add Pages to Cache Exclusions

All pages with `[supabase_auth_form]` shortcode MUST be excluded from cache.

---

## 🔧 Setup by Plugin

### LiteSpeed Cache (Most Common)

**Steps:**
1. WordPress Admin → **LiteSpeed Cache** → **Cache** tab
2. Scroll to **[4] Исключения** (Exclusions)
3. Find **"Не кешировать URI"** (Do Not Cache URIs)
4. Add your pages:
   ```
   /login/*
   /register/
   /signup/
   /auth/
   /test-no-elem/
   ```

**Using Wildcards:**
```
/login/*          ← Covers /login/, /login/page1/, /login/page2/
/landing-*        ← Covers /landing-1/, /landing-2/, /landing-offer/
/promo-*          ← Covers /promo-a/, /promo-b/, etc.
```

**Important:** Wildcard `*` only works for URL structure, NOT WordPress parent-child relationships!

**After changes:**
1. Click **"Сохранить изменения"** (Save Changes)
2. Go to **[3] Очистка** (Purge) tab
3. Click **"Успешная очистка всех кешей"** (Purge All Caches)

---

### WP Rocket

**Steps:**
1. WordPress Admin → **Settings** → **WP Rocket**
2. Go to **Advanced Rules** tab
3. Find **"Never cache URL(s)"** section
4. Add your pages (one per line):
   ```
   /login/(.*)
   /register/
   /signup/
   /auth/
   ```

**Using Regex:**
```
/login/(.*)       ← Matches /login/ and all subpages
/(login|register|signup|auth)/
```

**After changes:**
1. Click **Save Changes**
2. Click **Clear Cache** in admin bar

---

### W3 Total Cache

**Steps:**
1. WordPress Admin → **Performance** → **Page Cache**
2. Scroll to **"Never cache the following pages:"**
3. Add your pages (one per line):
   ```
   login/*
   register/
   signup/
   auth/
   ```

**After changes:**
1. Click **Save all settings**
2. **Performance** → **Dashboard** → **Empty all caches**

---

### WP Super Cache

**Steps:**
1. WordPress Admin → **Settings** → **WP Super Cache**
2. Go to **Advanced** tab
3. Find **"Accepted Filenames & Rejected URIs"**
4. Add to **"Rejected URIs"**:
   ```
   /login/
   /register/
   /signup/
   /auth/
   ```

**After changes:**
1. Click **Update Status**
2. Go to **Contents** tab → **Delete Cache**

---

### WP Fastest Cache

**Steps:**
1. WordPress Admin → **WP Fastest Cache**
2. Go to **Exclude** tab
3. Click **"Add New Rule"**
4. Select **"Is Equal To"** and add:
   ```
   /login/
   /register/
   /signup/
   ```

**After changes:**
1. Click **Submit**
2. Go to **Delete Cache** tab → **Delete Cache**

---

## 🎯 Naming Convention Strategy

### Problem: Many Landing Pages

If you have **hundreds of landing pages** with auth forms:
```
/landing-offer-1/
/landing-offer-2/
/landing-promo-a/
/landing-promo-b/
```

**Bad approach:** Add each page manually (hard to maintain)

**Good approach:** Use naming convention + wildcard
```
Cache Exclusions:
/landing-*
```

This excludes ALL pages starting with `/landing-`.

---

### Recommended URL Structure

**For Authentication Pages:**
```
/auth/login/          ← Main login
/auth/register/       ← Registration
/auth/google/         ← Google OAuth
/auth/facebook/       ← Facebook OAuth
```

**Cache Exclusion:**
```
/auth/*
```

One rule covers everything! ✅

---

**For Landing Pages:**
```
/lp/offer-1/
/lp/offer-2/
/lp/promo-black-friday/
```

**Cache Exclusion:**
```
/lp/*
```

---

**For Membership Pages:**
```
/account/*            ← User dashboard, profile, settings
/members/*            ← Member-only content
/checkout/*           ← Payment pages
```

**Cache Exclusions:**
```
/account/*
/members/*
/checkout/*
```

---

## ⚠️ Special Cases

### Membership Plugins (MemberPress, Paid Memberships Pro)

**Problem:** Different content for different membership levels.

**Solution:** Most caching plugins have "Do not cache for logged in users" option.

**LiteSpeed Cache:**
- Enable **"Не кешировать залогиненных пользователей"** (Do Not Cache Logged In Users)

**WP Rocket:**
- Enable **"User Cache"** → **"Enable Caching for Logged-in Users"** → Add membership pages to exclusions

---

### LMS Plugins (LearnDash, LifterLMS)

**Problem:** Course progress, quiz states cached incorrectly.

**Solution:** Exclude all course/lesson pages.

**For LearnDash:**
```
/courses/*
/lessons/*
/topics/*
/quizzes/*
```

**For LifterLMS:**
```
/course/*
/lesson/*
/quiz/*
```

---

### Dynamic Content (AJAX, User-Specific Data)

**Problem:** Personalized content (name, progress bars, conditional sections) cached.

**Solution:**
1. Use AJAX for dynamic parts (recommended)
2. OR exclude entire page from cache

**Example:**
```html
<!-- Static content (cacheable) -->
<h1>Welcome!</h1>

<!-- Dynamic content (loaded via AJAX) -->
<div id="user-dashboard">
  Loading...
  <script>
    // Fetch user data via AJAX after page load
    fetch('/wp-json/custom/v1/user-data')
      .then(r => r.json())
      .then(data => {
        document.getElementById('user-dashboard').innerHTML = data.html;
      });
  </script>
</div>
```

This allows caching the page while keeping user-specific content dynamic.

---

## 🔍 How to Diagnose Cache Issues

### Step 1: Check Browser Console (Non-Logged-In User)

**Open DevTools → Console:**

**✅ Working (no cache issue):**
```
✅ Supabase Auth initialized
✅ Ready to authenticate
```

**❌ Broken (cache issue):**
```
(no Supabase messages)
```

---

### Step 2: Check Elements Tab

**Open DevTools → Elements → Search (Cmd+F):**

Search for: `supabase-auth-container`

**✅ Working:**
```html
<div id="supabase-auth-container">
  <div class="auth-buttons">
    <button>Continue with Google</button>
    ...
  </div>
</div>
```

**❌ Broken (cache issue):**
```
(element not found)
```

---

### Step 3: Compare Logged-In vs Logged-Out

**Test in 2 windows:**
1. **Normal window (logged in)** → Form visible ✅
2. **Private window (logged out)** → Form missing ❌

**If different** → Cache plugin is serving different versions!

---

### Step 4: Bypass Cache (Testing)

**Add `?nocache=1` to URL:**
```
https://yoursite.com/login/?nocache=1
```

Most caching plugins skip caching for query parameters.

**If form appears with `?nocache=1`** → Confirmed cache issue!

---

## 🛠️ Testing After Fix

**After adding exclusions:**

1. **Clear all caches** (plugin + browser)
2. **Open private window** (Cmd+Shift+N)
3. **Visit auth page**
4. **Check Console:**
   ```
   ✅ Supabase Auth initialized
   ✅ Ready to authenticate
   ```
5. **Test full registration:**
   - Click "Continue with Google"
   - Authorize
   - Should redirect back and log in ✅

---

## 📊 Performance Impact

**Question:** Won't excluding pages from cache slow down my site?

**Answer:** Minimal impact if done correctly.

### Auth Pages (Low Traffic)

Pages like `/login/`, `/register/` have:
- **Low traffic** (only non-logged-in users visit once)
- **No SEO value** (shouldn't be indexed)
- **Fast render** (just an HTML form)

**Impact:** Negligible

---

### Landing Pages (High Traffic)

Landing pages might have high traffic from ads.

**Solution:** Cache the page, load form via AJAX

**Example:**
```html
<!-- landing-page.php (cached) -->
<!DOCTYPE html>
<html>
<body>
  <h1>Special Offer!</h1>
  <p>Sign up now and get 50% off!</p>

  <!-- Placeholder for auth form -->
  <div id="auth-form-container">
    Loading...
  </div>

  <script>
    // Load auth form dynamically (after page cached)
    fetch('/wp-json/supabase-bridge/v1/auth-form')
      .then(r => r.text())
      .then(html => {
        document.getElementById('auth-form-container').innerHTML = html;
      });
  </script>
</body>
</html>
```

**Benefits:**
- Landing page fully cached ✅
- Auth form loads dynamically ✅
- Fast page load ✅

---

## 🔐 Security Considerations

### NEVER Cache These Pages:

```
/wp-admin/*           ← WordPress admin
/wp-login.php         ← WordPress login
/checkout/*           ← Payment pages
/account/*            ← User dashboard
/cart/*               ← Shopping cart (WooCommerce)
/my-account/*         ← WooCommerce account
```

Most caching plugins exclude these by default, but verify!

---

### Cache Poisoning Risk

**Problem:** Attacker tricks cache into serving malicious content to all users.

**Solution:** Use cache plugins with:
- Separate cache for logged-in users
- CSRF protection
- Secure cache keys

**Recommended plugins:**
- ✅ LiteSpeed Cache (built-in security)
- ✅ WP Rocket (commercial, well-maintained)
- ⚠️ Free plugins: verify security features

---

## 📝 Checklist for Production

Before going live with Supabase Bridge:

- [ ] Identify all pages with `[supabase_auth_form]` shortcode
- [ ] Add pages to cache exclusions (with wildcards if possible)
- [ ] Clear all caches (plugin + CDN + browser)
- [ ] Test in private window (non-logged-in user)
- [ ] Verify Console shows "Supabase Auth initialized"
- [ ] Test full registration flow (Google OAuth + callback)
- [ ] Monitor error logs for cache-related issues
- [ ] Document excluded URLs for team reference

---

## 🆘 Still Not Working?

If form still doesn't appear after excluding from cache:

### 1. Check Other Caching Layers

**Server-Level Cache:**
- Nginx FastCGI cache
- Apache mod_cache
- Varnish

**Contact hosting support** to exclude auth pages.

**CDN Cache (Cloudflare, CloudFront):**
- Add Page Rules to bypass cache for `/login/*`

---

### 2. Disable Cache Plugin Temporarily

**Test without cache:**
1. Deactivate caching plugin
2. Test auth form in private window
3. If works → Cache plugin configuration issue
4. If still broken → Different problem (check PRODUCTION_DEBUGGING.md)

---

### 3. Check .htaccess Rules

Some caching plugins add rules to `.htaccess`.

**Check for:**
```apache
# LiteSpeed Cache rules
<IfModule LiteSpeed>
  # ...
</IfModule>
```

**If rules persist after deactivation:**
1. Backup `.htaccess`
2. Remove cache-related rules
3. Test again

---

## 📚 Additional Resources

- **LiteSpeed Cache Documentation:** https://docs.litespeedtech.com/lscache/
- **WP Rocket Documentation:** https://docs.wp-rocket.me/
- **W3 Total Cache Documentation:** https://www.boldgrid.com/support/w3-total-cache/

---

## 💡 Pro Tips

### Tip 1: Use Staging Environment

**Test cache exclusions on staging first:**
1. Clone production to staging
2. Configure cache exclusions
3. Test thoroughly
4. Deploy to production

---

### Tip 2: Monitor Cache Hit Rate

**After adding exclusions, check:**
- Cache hit rate shouldn't drop significantly
- Only auth pages bypass cache (low traffic)

**LiteSpeed Cache:**
- Dashboard shows cache hit rate %

**Goal:** Keep hit rate >80% while excluding auth pages

---

### Tip 3: Document Your Setup

**Create internal documentation:**
```markdown
# Cache Exclusions - YourSite.com

## LiteSpeed Cache Settings

### Excluded URLs:
- /login/*
- /register/
- /account/*
- /checkout/*

### Last Updated: 2025-12-17
### Updated By: Admin
```

**Benefits:**
- Team knows which pages excluded
- Easy to update when adding new pages
- Prevent accidental cache of auth pages

---

**Questions?** Check [PRODUCTION_DEBUGGING.md](./PRODUCTION_DEBUGGING.md) for general troubleshooting.
