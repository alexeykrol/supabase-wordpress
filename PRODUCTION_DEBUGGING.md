# Production Debugging Guide

**Plugin:** Supabase Bridge v0.9.1
**Last Updated:** 2025-12-17
**For:** Production site remote debugging

---

## Overview

This guide explains how to enable detailed logging and provide remote access for debugging the Supabase Bridge plugin on your production WordPress site.

---

## Step 1: Enable WordPress Debug Logging

### In `wp-config.php`

Add these lines **before** `/* That's all, stop editing! */`:

```php
// Enable debug logging (БЕЗОПАСНО для production)
define('WP_DEBUG', true);           // Включить режим отладки
define('WP_DEBUG_LOG', true);       // Записывать ошибки в файл
define('WP_DEBUG_DISPLAY', false);  // НЕ показывать на сайте (ВАЖНО!)
@ini_set('display_errors', 0);      // Отключить вывод ошибок
```

### What this does:

- ✅ **WP_DEBUG = true** - Enables debug mode (required for our logging system)
- ✅ **WP_DEBUG_LOG = true** - Writes all errors to `/wp-content/debug.log`
- ✅ **WP_DEBUG_DISPLAY = false** - **CRITICAL:** Errors are NOT shown on the website (security)
- ✅ **display_errors = 0** - Extra safety: disable PHP error display

### Log file location:

```
/wp-content/debug.log
```

---

## Step 2: Enhanced Logging (Already Active)

The plugin v0.9.1 includes enhanced logging system that logs:

### What gets logged:

1. **Rate limiting** - IP address, attempt count
2. **CSRF validation** - Origin/Referer headers
3. **JWT verification** - JWKS cache hits/misses, token validation
4. **User sync** - User found by UUID/email, user creation
5. **Authentication** - Login success/failure with IP
6. **Errors** - Full error messages with stack traces

### Log levels:

- **DEBUG** - Detailed execution flow (function entry/exit, cache hits)
- **INFO** - Important events (user created, logged in)
- **WARNING** - Potential issues (rate limit exceeded, duplicate callbacks)
- **ERROR** - Critical failures (CSRF failed, JWT invalid)

### Example log entry:

```
[2025-12-17 15:23:45] [Supabase Bridge] [INFO] User logged in successfully | Context: {"wp_user_id":42,"email":"user@example.com","ip":"192.168.1.1"}
```

---

## Step 3: Sensitive Data Protection

### Automatic redaction:

The logging system automatically **masks sensitive data**:

- JWT tokens → `eyJhbGci...[REDACTED]`
- Passwords → `[REDACTED]`
- API keys → `sk_live_ab...[REDACTED]`
- Cookies → `[REDACTED]`

### Long strings are truncated:

```
Long value (>500 chars) → First 500 chars...[TRUNCATED]
```

**Safe to share:** You can safely send me the debug.log file - all secrets are automatically removed.

---

## Step 4: SSH Read-Only Access (Recommended)

For full debugging capabilities, set up SSH read-only access:

### Create dedicated SSH user:

```bash
# On your server (as root or sudo user)

# 1. Create user
sudo useradd -m -s /bin/bash claude_debug

# 2. Set strong random password
sudo passwd claude_debug
# (Enter a secure password - you'll share this with me)

# 3. Create SSH directory
sudo mkdir -p /home/claude_debug/.ssh
sudo chmod 700 /home/claude_debug/.ssh

# 4. Add my SSH public key (I'll provide this)
# sudo nano /home/claude_debug/.ssh/authorized_keys
# (Paste my public key)
sudo chmod 600 /home/claude_debug/.ssh/authorized_keys
sudo chown -R claude_debug:claude_debug /home/claude_debug/.ssh
```

### Restrict access (read-only):

```bash
# 1. Add user to www-data group (read WordPress files)
sudo usermod -a -G www-data claude_debug

# 2. Limit to specific directories
# Edit /etc/ssh/sshd_config:
Match User claude_debug
    ChrootDirectory /var/www/html
    ForceCommand internal-sftp
    PermitTunnel no
    AllowAgentForwarding no
    AllowTcpForwarding no
    X11Forwarding no
```

### Restart SSH service:

```bash
sudo systemctl restart sshd
```

### Test connection:

```bash
ssh claude_debug@yoursite.com
```

---

## Step 5: Alternative - Debug Log Sharing

If SSH is not possible, you can manually share debug.log:

### View recent logs:

```bash
# Last 100 lines
tail -n 100 /wp-content/debug.log

# Live monitoring (Ctrl+C to stop)
tail -f /wp-content/debug.log
```

### Download debug.log:

**Via FTP/SFTP:**
1. Connect to your site
2. Navigate to `/wp-content/`
3. Download `debug.log`

**Via WordPress admin:**
1. Install "WP File Manager" plugin (temporary)
2. Navigate to `/wp-content/`
3. Download `debug.log`
4. Delete plugin after download

### Send to me:

- Email: Share debug.log file
- Paste: Copy last 200 lines to chat

---

## Step 6: Supabase Dashboard Access (Optional)

To see database-side debugging:

### Read-only access:

1. Go to your Supabase project
2. Settings → Team
3. Invite me: **your-claude-email@example.com**
4. Role: **Read Only**

### What I can see:

- Registration events (`wp_user_registrations`)
- Registration pairs (`wp_registration_pairs`)
- Webhook logs (`webhook_logs`)
- RLS policies (security rules)

**Note:** I will NOT have access to modify any data or see user passwords.

---

## Step 7: Testing the Logging

### Trigger a test authentication:

1. Go to your site's registration page
2. Try registering with a new email (or use Google OAuth)
3. Check debug.log for new entries

### Expected log entries:

```
[Timestamp] [Supabase Bridge] [DEBUG] → Entering function: sb_handle_callback
[Timestamp] [Supabase Bridge] [DEBUG] Rate limiting check | Context: {"ip":"...","attempts":1}
[Timestamp] [Supabase Bridge] [DEBUG] CSRF validation | Context: {...}
[Timestamp] [Supabase Bridge] [DEBUG] JWT received | Context: {"jwt_length":425}
[Timestamp] [Supabase Bridge] [INFO] Starting WordPress user sync | Context: {...}
[Timestamp] [Supabase Bridge] [INFO] User logged in successfully | Context: {...}
```

---

## Step 8: Disable Logging (After Debugging)

### When debugging is complete:

```php
// In wp-config.php, change:
define('WP_DEBUG', false);  // Disable debug mode
```

Or keep enabled with log rotation:

```bash
# Rotate debug.log weekly (via cron)
0 0 * * 0 mv /var/www/html/wp-content/debug.log /var/www/html/wp-content/debug.log.old && touch /var/www/html/wp-content/debug.log
```

---

## Security Checklist

Before enabling debugging:

- [ ] `WP_DEBUG_DISPLAY = false` (errors NOT shown on site)
- [ ] `debug.log` is NOT publicly accessible (check: `yoursite.com/wp-content/debug.log` should return 403)
- [ ] `.htaccess` blocks access to `debug.log`:
  ```apache
  <Files debug.log>
    Order allow,deny
    Deny from all
  </Files>
  ```
- [ ] SSH user has limited permissions (read-only)
- [ ] SSH password is strong (20+ chars, random)
- [ ] Logging is disabled after debugging complete

---

## Troubleshooting

### Debug.log is empty

**Solution:**
```bash
# Check file permissions
ls -la /wp-content/debug.log

# Should be writable by web server
sudo chown www-data:www-data /wp-content/debug.log
sudo chmod 644 /wp-content/debug.log
```

### Too many log entries

**Solution:**
```bash
# Clear old logs
> /wp-content/debug.log

# Or view only Supabase Bridge logs
grep "Supabase Bridge" /wp-content/debug.log | tail -n 100
```

### SSH connection refused

**Solution:**
```bash
# Check SSH service status
sudo systemctl status sshd

# Check firewall
sudo ufw status
sudo ufw allow 22/tcp
```

---

## What I Need From You

To start debugging, please provide:

### Minimum (for basic debugging):
1. ✅ `WP_DEBUG_LOG` enabled in `wp-config.php`
2. ✅ Share debug.log file (last 200 lines)

### Recommended (for full debugging):
1. ✅ SSH read-only access credentials
2. ✅ Server details (OS, PHP version, WordPress version)
3. ✅ Error description and steps to reproduce

### Optional (for Supabase debugging):
1. ⏳ Read-only access to Supabase Dashboard
2. ⏳ Supabase project URL

---

## Common Issues

### Auth Form Not Visible for Non-Logged-In Users

**Symptom:** Form visible when logged in, but invisible in private browsing mode.

**Cause:** Caching plugin (LiteSpeed, WP Rocket, W3 Total Cache) serves cached version without dynamic content.

**Solution:** See detailed guide: **[CACHE_TROUBLESHOOTING.md](./CACHE_TROUBLESHOOTING.md)**

**Quick Fix:**
1. Add auth pages to cache exclusions
2. Clear all caches
3. Test in private window

---

## Support

If you have questions about this setup:

1. Check [CACHE_TROUBLESHOOTING.md](./CACHE_TROUBLESHOOTING.md) for cache-related issues
2. Check [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md) for general production config
3. Check [QUICK_SETUP_CHECKLIST.md](./QUICK_SETUP_CHECKLIST.md) for deployment guide
4. Share debug.log and I'll help troubleshoot

---

**Remember:** Always disable `WP_DEBUG` after debugging is complete to avoid performance impact.
