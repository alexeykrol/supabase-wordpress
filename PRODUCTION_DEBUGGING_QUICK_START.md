# Production Debugging - Quick Start

**5-Minute Setup Guide**

---

## Step 1: Enable Debug Logging (2 min)

Edit `wp-config.php` and add **before** `/* That's all, stop editing! */`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

**Save and upload.**

---

## Step 2: Test Authentication (1 min)

1. Go to your registration page
2. Register with a test email (or use Google OAuth)
3. Check if authentication works

---

## Step 3: Check Debug Log (1 min)

### Via FTP/SSH:

```bash
tail -n 50 /wp-content/debug.log
```

### Via WordPress File Manager plugin:

1. Install "WP File Manager"
2. Navigate to `/wp-content/debug.log`
3. View last 50 lines

---

## Step 4: Share Log With Me (1 min)

### If you see errors:

```bash
# Get last 200 lines
tail -n 200 /wp-content/debug.log > debug_excerpt.txt
```

**Send me `debug_excerpt.txt`**

### If no errors:

Great! Your plugin is working correctly.

---

## What I'll See in the Log

```
[Timestamp] [Supabase Bridge] [INFO] → Entering function: sb_handle_callback
[Timestamp] [Supabase Bridge] [DEBUG] JWT received
[Timestamp] [Supabase Bridge] [INFO] Starting WordPress user sync
[Timestamp] [Supabase Bridge] [INFO] User logged in successfully
```

**All sensitive data (tokens, passwords) is automatically redacted.**

---

## ⚠️ Common Issue: Form Not Visible

**If form doesn't appear in private browsing:**

**Cause:** Caching plugin (LiteSpeed, WP Rocket, etc.)

**Quick Fix:**
1. Add page to cache exclusions
2. Clear all caches
3. Test again

**Full Guide:** [CACHE_TROUBLESHOOTING.md](./CACHE_TROUBLESHOOTING.md)

---

## Need More Help?

- **Cache issues:** [CACHE_TROUBLESHOOTING.md](./CACHE_TROUBLESHOOTING.md)
- **Full debugging:** [PRODUCTION_DEBUGGING.md](./PRODUCTION_DEBUGGING.md)

---

**Done!** The plugin now logs all authentication events to `/wp-content/debug.log`.
