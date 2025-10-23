# Security Policy

## Version

**Current Version:** 0.3.2
**Status:** Production Ready
**Last Security Audit:** 2025-10-07

---

## Security Measures Implemented

### 1. Authentication & Authorization

#### JWT Verification
- **RS256 signature verification** using JWKS (JSON Web Key Set)
- **Public key cryptography** - server-side verification only
- **Strict claim validation:**
  - `iss` (issuer) - must match Supabase project
  - `aud` (audience) - must be "authenticated"
  - `exp` (expiration) - checked against current time
  - `email` and `sub` (subject) - required fields
  - `email_verified` - mandatory check (prevents unverified accounts)

#### JWKS Caching
- **1-hour cache** for JWKS public keys
- **SSL verification** enforced on external requests
- **Error handling** with proper logging
- **Status code validation** (must be 200 OK)

### 2. Cross-Site Request Forgery (CSRF) Protection

#### Strict Origin Validation (v0.3.2)
- **Origin/Referer header validation** on all REST endpoints
- **Exact host matching** - prevents header bypass attacks
- **Both callback and logout endpoints** protected
- **Fails closed** - rejects requests without valid headers

### 3. Rate Limiting

#### Brute Force Protection
- **10 attempts per 60 seconds** per IP address
- **Transient-based storage** (WordPress native)
- **HTTP 429** response on limit exceeded
- **Automatic reset** on successful authentication

### 4. Input Validation & Sanitization

#### Email Validation
- `sanitize_email()` - WordPress native function
- `is_email()` - RFC 5322 compliance check
- Prevents email injection attacks

#### User Metadata
- `sanitize_text_field()` for Supabase user IDs
- No direct database queries - uses WordPress APIs

### 5. HTTP Security Headers

#### Defense in Depth Headers
```http
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: [see below]
```

#### Content Security Policy (CSP)
```
default-src 'self';
script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
connect-src 'self' https://*.supabase.co;
style-src 'self' 'unsafe-inline';
img-src 'self' data: https:;
font-src 'self' data:;
frame-ancestors 'self';
```

**Note:** CSP only applies to frontend pages (not admin)

### 6. Open Redirect Protection

#### Redirect URL Validation
- **Same-origin policy** enforcement
- **Whitelist approach** - only internal redirects allowed
- **Fallback to default** on invalid URLs
- Prevents phishing attacks via redirect parameter

### 7. Password Security

#### Strong Password Generation
- **32 characters** random password
- **High complexity** (uppercase, lowercase, numbers, symbols)
- `wp_generate_password(32, true, true)`
- Users authenticate via Supabase (password never used)

### 8. Error Handling

#### Information Disclosure Prevention
- **Generic error messages** to users
- **Detailed logging** to server logs only
- **No stack traces** exposed to frontend
- **Audit trail** for all authentication attempts

#### Audit Logging
```php
// Success
error_log('Supabase Bridge: Successful authentication - User ID: X, Email: Y, IP: Z');

// Failure
error_log('Supabase Bridge: Authentication failed - Error: X, IP: Y');

// Logout
error_log('Supabase Bridge: User logout - User ID: X, IP: Y');
```

### 9. Dependency Management

#### PHP Dependencies
- **firebase/php-jwt:** ^6.11.1 (latest stable)
- **No transitive vulnerabilities** detected
- **Composer audit:** Pass (2025-10-07)

### 10. Configuration Security

#### Environment Variables
- **No hardcoded credentials** in code
- **putenv() / define()** for configuration
- **Separate example file** (wp-config-supabase-example.php)
- **.gitignore** protects sensitive files

#### Key Management
- **ONLY anon public key** used in frontend
- **NEVER service_role key** exposed
- **JWKS public keys** fetched dynamically

---

## Security Best Practices

### For Administrators

1. **Keep dependencies updated:**
   ```bash
   composer update
   composer audit
   ```

2. **Configure environment variables securely:**
   - Add to `wp-config.php` (not tracked in git)
   - Use `define()` or `putenv()`
   - Never commit credentials to version control

3. **Monitor audit logs:**
   - Check WordPress debug.log regularly
   - Look for failed authentication attempts
   - Monitor rate limit triggers

4. **Configure Supabase properly:**
   - Enable email verification (mandatory)
   - Configure redirect URLs whitelist
   - Enable RLS (Row Level Security) policies
   - Use OAuth Advanced access for Facebook (email scope)

5. **HTTPS Only:**
   - Always use HTTPS in production
   - Enable `is_ssl()` checks
   - Configure secure cookies

### For Developers

1. **Never expose service_role key**
2. **Always validate and sanitize input**
3. **Use WordPress APIs** (don't write raw SQL)
4. **Test with WP_DEBUG enabled**
5. **Follow WordPress Coding Standards**

---

## Vulnerability Disclosure

### Reporting Security Issues

If you discover a security vulnerability, please report it responsibly:

1. **DO NOT** open a public GitHub issue
2. **Email:** [your-email@example.com]
3. **Include:**
   - Description of the vulnerability
   - Steps to reproduce
   - Affected versions
   - Suggested fix (if any)

### Response Timeline

- **24 hours:** Initial acknowledgment
- **7 days:** Initial assessment and triage
- **30 days:** Fix developed and tested
- **45 days:** Security release published

---

## Security Changelog

### v0.3.2 (2025-10-05) - CRITICAL SECURITY HOTFIX
- Fixed Origin/Referer bypass vulnerability (strict host comparison)
- Added CSRF protection for logout endpoint
- Improved error messages to prevent information leakage

### v0.3.1 (2025-10-05) - Security Update
- Added CSRF protection (Origin/Referer validation)
- Added JWT aud (audience) validation
- Mandatory email verification enforcement
- Open redirect protection
- JWKS caching (1 hour)
- Rate limiting (10 attempts/60s)
- PHP >=8.0 requirement
- .gitignore added

### v0.3.3 (2025-10-07) - Enhanced Security
- Added HTTP security headers (CSP, X-Frame-Options, etc.)
- Improved error handling and logging
- Enhanced audit trail for all authentication events
- Stronger password generation (32 chars)
- SSL verification enforcement for JWKS
- Additional email validation checks
- Default user role assignment (subscriber)
- Clear rate limit on successful auth

---

## Security Features Roadmap

### Planned (Future Versions)

- [ ] Two-factor authentication (2FA) support
- [ ] IP-based geolocation blocking
- [ ] Advanced rate limiting (distributed cache)
- [ ] Session management improvements
- [ ] Security event webhooks
- [ ] Automated security scanning (CI/CD)
- [ ] GDPR compliance tools
- [ ] Account activity logging (user-facing)

---

## Compliance

### Standards Followed

- **OWASP Top 10** - Addressed all relevant vulnerabilities
- **WordPress Security Best Practices** - Follows official guidelines
- **PHP Security Best Practices** - Modern PHP patterns
- **GDPR** - Minimal data collection, user consent

### Security Checklist

- [x] SQL Injection - Uses WordPress APIs (parameterized queries)
- [x] XSS - Input sanitization with WordPress functions
- [x] CSRF - Origin/Referer validation
- [x] Session Fixation - WordPress session management
- [x] Brute Force - Rate limiting implemented
- [x] Open Redirect - Same-origin validation
- [x] Information Disclosure - Generic error messages
- [x] Insecure Dependencies - Regular audits
- [x] Insecure Configuration - Environment variables
- [x] Missing Security Headers - All headers implemented

---

## Contact

**Project:** Supabase Bridge for WordPress
**Version:** 0.3.2
**License:** MIT
**Author:** Alexey Krol

**Support:** [GitHub Issues](https://github.com/yourusername/supabase-bridge/issues)
**Security:** [security@example.com]

---

**Last Updated:** 2025-10-07
**Next Audit:** 2026-01-07 (quarterly)
