# OAuth Provider Setup Guide

**Purpose:** Configure Google and Facebook OAuth providers in Supabase for webhook data enrichment
**Target Audience:** Developers deploying WordPress-Supabase Bridge with webhook system
**Last Updated:** 2025-10-27

---

## 🎯 Why Configure OAuth Providers?

When users register via Google or Facebook OAuth, the webhook payload sent to n8n/Make includes:
- `user_email` - User's email address
- `user_id` - Supabase UUID
- `user_metadata` - Profile data (name, avatar, etc.) from OAuth provider

**Without OAuth configuration:** Webhooks still work, but you only get email + UUID
**With OAuth configuration:** Webhooks include rich profile data from Google/Facebook

---

## 🔐 Security Note

**⚠️ IMPORTANT:** OAuth credentials are sensitive! Follow these security practices:

1. **Never commit credentials to Git** - Use environment variables
2. **Rotate secrets regularly** - Change OAuth client secrets every 90 days
3. **Restrict callback URLs** - Only whitelist your actual domains
4. **Use separate credentials per environment** - Different keys for dev/staging/production
5. **Monitor OAuth logs** - Check Supabase Dashboard → Authentication → Logs

---

## 🔵 Google OAuth Setup

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project (or select existing)
3. Name: "Supabase Auth - [Your Site Name]"
4. Click **Create**

### Step 2: Enable Google+ API

1. In sidebar: **APIs & Services** → **Library**
2. Search: "Google+ API"
3. Click **Enable**

### Step 3: Configure OAuth Consent Screen

1. Sidebar: **APIs & Services** → **OAuth consent screen**
2. User Type: **External** (or Internal if Google Workspace)
3. Click **Create**

**Fill out form:**
```
App name: [Your WordPress Site Name]
User support email: your@email.com
Application home page: https://yoursite.com
Authorized domains: yoursite.com
Developer contact: your@email.com
```

4. **Scopes:** Add these scopes:
   - `.../auth/userinfo.email` (required)
   - `.../auth/userinfo.profile` (required)
5. Click **Save and Continue**

### Step 4: Create OAuth Credentials

1. Sidebar: **APIs & Services** → **Credentials**
2. Click **+ CREATE CREDENTIALS** → **OAuth client ID**
3. Application type: **Web application**
4. Name: "Supabase Auth - [Your Site Name]"

**Authorized redirect URIs:**
```
https://YOUR_PROJECT_REF.supabase.co/auth/v1/callback
```

**Example:**
```
https://fomzkfdcueugsykhhzqe.supabase.co/auth/v1/callback
```

5. Click **Create**
6. **Copy your credentials:**
   - Client ID: `1234567890-abcdefghijklmnop.apps.googleusercontent.com`
   - Client secret: `GOCSPX-abc123xyz`

### Step 5: Configure in Supabase

1. Go to [Supabase Dashboard](https://supabase.com/dashboard)
2. Select your project
3. Sidebar: **Authentication** → **Providers**
4. Find **Google** → Click to expand

**Enable and configure:**
```
Enabled: ✅ ON
Client ID: [paste from Google Console]
Client Secret: [paste from Google Console]
```

5. Click **Save**

### Step 6: Test Google OAuth

1. WordPress site → Login page with `[supabase_auth_form]` shortcode
2. Click "Sign in with Google"
3. Authenticate with Google account
4. Should redirect to WordPress and create user
5. Check webhook in Make.com - should include:
   ```json
   {
     "event": "user_registered",
     "data": {
       "user_email": "user@gmail.com",
       "user_id": "uuid-here",
       "user_metadata": {
         "name": "John Doe",
         "avatar_url": "https://lh3.googleusercontent.com/...",
         "email_verified": true,
         "provider": "google"
       }
     }
   }
   ```

---

## 🔵 Facebook OAuth Setup

### Step 1: Create Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Click **My Apps** → **Create App**
3. App Type: **Consumer** (for login functionality)
4. Click **Next**

**Fill out form:**
```
Display Name: [Your WordPress Site Name]
Contact Email: your@email.com
```

5. Click **Create App**

### Step 2: Add Facebook Login Product

1. In dashboard: Find **Facebook Login** product
2. Click **Set Up**
3. Choose **Web** platform
4. Skip the quickstart wizard (click **Settings** in sidebar)

### Step 3: Configure OAuth Redirect

1. Sidebar: **Facebook Login** → **Settings**
2. **Valid OAuth Redirect URIs:** Add:
```
https://YOUR_PROJECT_REF.supabase.co/auth/v1/callback
```

**Example:**
```
https://fomzkfdcueugsykhhzqe.supabase.co/auth/v1/callback
```

3. Click **Save Changes**

### Step 4: Request Advanced Access (CRITICAL!)

**⚠️ IMPORTANT:** By default, Facebook only gives "Default" access to `email` scope, which doesn't work in production.

1. Sidebar: **App Review** → **Permissions and Features**
2. Find **email** permission
3. Click **Request Advanced Access**
4. Fill out form:
   ```
   Tell us how you'll use this data:
   "We use email to create WordPress user accounts and send registration confirmation emails."

   Platform: Web
   ```
5. Submit for review

**Note:** Approval takes 1-7 days. Until approved, Facebook OAuth only works with app admins/testers.

### Step 5: Add Test Users (During Review)

1. Sidebar: **Roles** → **Test Users**
2. Click **Add**
3. Add your email as test user
4. Test users can use Facebook OAuth immediately (no approval needed)

### Step 6: Get App Credentials

1. Sidebar: **Settings** → **Basic**
2. **Copy your credentials:**
   - App ID: `1234567890123456`
   - App Secret: Click **Show** → `abc123def456ghi789`

### Step 7: Configure in Supabase

1. Go to [Supabase Dashboard](https://supabase.com/dashboard)
2. Select your project
3. Sidebar: **Authentication** → **Providers**
4. Find **Facebook** → Click to expand

**Enable and configure:**
```
Enabled: ✅ ON
Facebook App ID: [paste from Facebook dashboard]
Facebook App Secret: [paste from Facebook dashboard]
```

5. Click **Save**

### Step 8: Test Facebook OAuth

1. WordPress site → Login page with `[supabase_auth_form]` shortcode
2. Click "Sign in with Facebook"
3. Authenticate with Facebook account (must be test user if pending review)
4. Should redirect to WordPress and create user
5. Check webhook in Make.com - should include:
   ```json
   {
     "event": "user_registered",
     "data": {
       "user_email": "user@facebook.com",
       "user_id": "uuid-here",
       "user_metadata": {
         "name": "Jane Smith",
         "avatar_url": "https://graph.facebook.com/.../picture",
         "email_verified": true,
         "provider": "facebook"
       }
     }
   }
   ```

---

## 🔍 Troubleshooting

### Google OAuth Issues

**Problem:** "Error 400: redirect_uri_mismatch"
**Solution:** Check that redirect URI in Google Console EXACTLY matches:
```
https://YOUR_PROJECT_REF.supabase.co/auth/v1/callback
```
No trailing slash, correct project ref.

**Problem:** "Access blocked: This app's request is invalid"
**Solution:** Complete OAuth consent screen configuration (all required fields).

**Problem:** "Email not verified" error in WordPress
**Solution:** This is normal - OAuth email verification fixed in v0.3.5. Update plugin to latest version.

---

### Facebook OAuth Issues

**Problem:** "Can't Load URL: The domain of this URL isn't included in the app's domains"
**Solution:** Add your domain to **App Domains** in Settings → Basic:
```
App Domains: yoursite.com, supabase.co
```

**Problem:** "email" permission not working
**Solution:** Request Advanced Access for email permission (see Step 4 above). Until approved, only works with test users.

**Problem:** "The parameter app_id is required"
**Solution:** Check that App ID is correctly configured in Supabase Dashboard → Authentication → Providers → Facebook.

**Problem:** Webhook doesn't include user metadata
**Solution:** Ensure Facebook app has "public_profile" and "email" permissions enabled in App Review.

---

## 📊 Webhook Payload Comparison

### Without OAuth (Magic Link only):
```json
{
  "event": "user_registered",
  "data": {
    "user_email": "user@example.com",
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "registration_url": "/services/",
    "thankyou_page_url": "/services-thankyou/",
    "registered_at": "2025-10-27T12:34:56.789Z"
  },
  "timestamp": "2025-10-27T12:34:56.789Z"
}
```

### With OAuth (Google/Facebook):
```json
{
  "event": "user_registered",
  "data": {
    "user_email": "user@gmail.com",
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "registration_url": "/services/",
    "thankyou_page_url": "/services-thankyou/",
    "registered_at": "2025-10-27T12:34:56.789Z",
    "user_metadata": {
      "name": "John Doe",
      "avatar_url": "https://lh3.googleusercontent.com/...",
      "email_verified": true,
      "provider": "google",
      "sub": "1234567890"
    }
  },
  "timestamp": "2025-10-27T12:34:56.789Z"
}
```

**Benefits of OAuth data:**
- Personalized emails (use `name` field)
- Avatar images in webhooks/notifications
- Email pre-verified (trust Google/Facebook verification)
- Provider tracking (know which OAuth provider user chose)

---

## 🔄 Webhook Integration with OAuth

### Example: Make.com Scenario

**Scenario:** Send personalized welcome email when user registers via Google OAuth

**Make.com Workflow:**
1. **Webhook Trigger** - Receives webhook from Supabase
2. **Router** - Check `data.user_metadata.provider`
   - Path A: `provider = "google"` → Use Google personalization
   - Path B: `provider = "facebook"` → Use Facebook personalization
   - Path C: No provider → Generic welcome email
3. **Gmail Module** - Send email
   - To: `{{data.user_email}}`
   - Subject: "Welcome, {{data.user_metadata.name || data.user_email}}!"
   - Body: Include avatar from `data.user_metadata.avatar_url`

**Benefits:**
- Personalized greetings ("Hi John!" vs "Hi user@gmail.com!")
- Show user's avatar in email
- Different email templates per OAuth provider
- Track conversion rates by provider

---

## 📚 Additional Resources

### Official Documentation:
- [Supabase Google OAuth](https://supabase.com/docs/guides/auth/social-login/auth-google)
- [Supabase Facebook OAuth](https://supabase.com/docs/guides/auth/social-login/auth-facebook)
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login](https://developers.facebook.com/docs/facebook-login)

### WordPress-Supabase Bridge Documentation:
- [DEPLOYMENT.md](./DEPLOYMENT.md) - Webhook system deployment
- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture
- [README.md](./README.md) - Project overview

---

## ✅ Checklist

Use this checklist to verify OAuth setup:

### Google OAuth:
- [ ] Google Cloud project created
- [ ] Google+ API enabled
- [ ] OAuth consent screen configured (app name, domains, scopes)
- [ ] OAuth client created (Web application type)
- [ ] Redirect URI added: `https://YOUR_PROJECT_REF.supabase.co/auth/v1/callback`
- [ ] Client ID and Secret copied
- [ ] Configured in Supabase Dashboard → Authentication → Providers → Google
- [ ] Tested login via WordPress site
- [ ] Verified webhook payload includes `user_metadata`

### Facebook OAuth:
- [ ] Facebook app created (Consumer type)
- [ ] Facebook Login product added
- [ ] OAuth redirect URI configured
- [ ] **Advanced Access requested for "email" permission** (CRITICAL!)
- [ ] Test users added (if pending review)
- [ ] App ID and Secret copied
- [ ] Configured in Supabase Dashboard → Authentication → Providers → Facebook
- [ ] Tested login via WordPress site (with test user if needed)
- [ ] Verified webhook payload includes `user_metadata`

### Webhook Verification:
- [ ] Webhook received in Make.com/n8n
- [ ] Payload includes all expected fields
- [ ] `user_metadata` present for OAuth registrations
- [ ] `name` and `avatar_url` populated correctly
- [ ] Can distinguish between OAuth providers via `provider` field

---

*OAuth Setup Guide for Webhook System v0.8.1*
*Created: 2025-10-27*
*Part of: WordPress-Supabase Bridge with Webhook System*
