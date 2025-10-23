# Project Architecture

**Project:** Supabase Bridge for WordPress
**Version:** 0.1.0
**Last Updated:** 2025-10-01

---

## 📊 Overview

**Supabase Bridge** is a WordPress plugin that integrates Supabase Auth as the authentication provider for WordPress. It provides seamless user synchronization between Supabase's modern authentication system and WordPress's user management.

### Key Design Goals
1. **Security First:** Server-side JWT verification, no client-side trust
2. **Minimal Footprint:** Single PHP file, one dependency
3. **WordPress Native:** Uses standard WordPress APIs and patterns
4. **Provider Agnostic:** Works with any Supabase OAuth provider
5. **Zero Configuration UI:** Environment variables only

---

## 🏗️ Technology Stack

### Backend
- **Platform:** WordPress 5.0+
- **Language:** PHP 7.4+
- **Dependency:** `firebase/php-jwt` ^6.10 (JWT verification via JWKS)

### Frontend
- **Library:** Supabase JS SDK v2 (CDN-loaded)
- **JavaScript:** Vanilla JS (no build step required)

### External Services
- **Supabase Auth:** Identity provider (OAuth, email/password, magic links)
- **JWKS Endpoint:** Public key distribution for JWT verification

---

## 🔧 System Architecture

### High-Level Flow
```
┌─────────────┐         ┌──────────────┐         ┌────────────┐
│   Browser   │ ◄─────► │ Supabase Auth│ ◄─────► │   OAuth    │
│  (Frontend) │         │   Service    │         │  Provider  │
└──────┬──────┘         └──────────────┘         └────────────┘
       │                                                ▲
       │ POST /wp-json/supabase-auth/callback          │
       ▼                                                │
┌─────────────────────────────────────────────────────┘
│  WordPress Plugin (Supabase Bridge)
│  ├─ JWT Verification (JWKS)
│  ├─ User Creation/Update
│  └─ WordPress Session Setup
└─────────────────────────────────────────────────────┐
                                                       │
                                                       ▼
                                              ┌─────────────────┐
                                              │  WordPress Core │
                                              │   (wp_users)    │
                                              └─────────────────┘
```

### Component Breakdown

#### 1. Frontend Integration
**Location:** Embedded in WordPress pages via HTML blocks or page builders

**Responsibilities:**
- Initialize Supabase JS client with configuration from `window.SUPABASE_CFG`
- Handle OAuth button clicks (trigger `signInWithOAuth()`)
- Capture session on callback page (via `getSession()`)
- POST JWT to WordPress REST endpoint
- Redirect to authenticated area on success

**Code Pattern:**
```javascript
const { createClient } = window.supabase;
const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);

// Login trigger
await sb.auth.signInWithOAuth({
  provider: 'google',
  options: { redirectTo: '/supabase-callback/' }
});

// Callback handler
const { data: { session } } = await sb.auth.getSession();
await fetch('/wp-json/supabase-auth/callback', {
  method: 'POST',
  body: JSON.stringify({ access_token: session.access_token })
});
```

#### 2. WordPress Plugin (supabase-bridge.php)
**Single File Architecture:** All logic in one file (~100 lines)

**Core Functions:**

**a) Configuration Injection (`wp_enqueue_scripts` hook)**
```php
add_action('wp_enqueue_scripts', function () {
  // Load Supabase JS from CDN
  wp_enqueue_script('supabase-js', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2');

  // Inject config from environment variables
  wp_add_inline_script('supabase-js', 'window.SUPABASE_CFG = {...}', 'before');
});
```
**Why:** Avoids hardcoding credentials in HTML, uses WordPress script queue

**b) REST API Endpoints (`rest_api_init` hook)**
```php
add_action('rest_api_init', function () {
  // POST /wp-json/supabase-auth/callback
  register_rest_route('supabase-auth', '/callback', [...]);

  // POST /wp-json/supabase-auth/logout
  register_rest_route('supabase-auth', '/logout', [...]);
});
```

**c) JWT Verification (`sb_handle_callback` function)**
**Critical Security Component**

**Steps:**
1. **Fetch JWKS:** Retrieve public keys from Supabase's `.well-known/jwks.json`
2. **Decode JWT:** Use `firebase/php-jwt` library with JWKS
3. **Validate Claims:**
   - `iss` (issuer): Must match Supabase project URL
   - `exp` (expiration): Must be in future
   - `email`: Must exist
   - `sub` (subject): Supabase user UUID
   - `email_verified`: Optional strict check
4. **Error Handling:** Return `WP_Error` on any validation failure

**Code Flow:**
```php
// 1. Fetch JWKS
$jwks = "https://{$projectRef}.supabase.co/auth/v1/.well-known/jwks.json";
$keys = json_decode(wp_remote_retrieve_body(wp_remote_get($jwks)), true);

// 2. Verify JWT signature and decode
$publicKeys = \Firebase\JWT\JWK::parseKeySet($keys);
$decoded = \Firebase\JWT\JWT::decode($jwt, $publicKeys);

// 3. Validate claims
if ($decoded->iss !== $expectedIssuer) throw new Exception('Bad issuer');
if (time() >= $decoded->exp) throw new Exception('Expired token');

// 4. Create/update WordPress user
$user = get_user_by('email', $decoded->email);
if (!$user) {
  $uid = wp_create_user($email, wp_generate_password(), $email);
  update_user_meta($uid, 'supabase_user_id', $decoded->sub);
}

// 5. Set WordPress auth cookie
wp_set_auth_cookie($user->ID, true, is_ssl());
```

**d) User Synchronization**
**Pattern:** "Create on first login, update on subsequent"

**Database Schema:**
- `wp_users` table: Standard WordPress users
  - `user_email`: Synced from JWT `email` claim
  - `user_login`: Set to email (or username if provided)
  - `user_pass`: Random password (not used, login via Supabase only)
- `wp_usermeta` table: Custom metadata
  - `supabase_user_id`: Stores Supabase `sub` (UUID) for linking

**Sync Logic:**
```php
$user = get_user_by('email', $claims['email']);
if (!$user) {
  // First login: create new WordPress user
  $uid = wp_create_user($email, wp_generate_password(), $email);
  update_user_meta($uid, 'supabase_user_id', $claims['sub']);
} else {
  // Subsequent login: update metadata
  update_user_meta($user->ID, 'supabase_user_id', $claims['sub']);
}
```

#### 3. Configuration Management
**Pattern:** Environment variables via `getenv()`

**Required Variables:**
```
SUPABASE_PROJECT_REF   # e.g., "abcdefghijk"
SUPABASE_URL           # e.g., "https://abcdefghijk.supabase.co"
SUPABASE_ANON_KEY      # Public anon key (safe for frontend)
```

**Where to Find These Values:**
1. Go to [Supabase Dashboard](https://app.supabase.com)
2. Select your project
3. Navigate to **Settings** → **API**
4. Copy:
   - **Project URL** → `SUPABASE_URL`
   - Extract project ref from URL (e.g., `abcdefghijk` from `https://abcdefghijk.supabase.co`) → `SUPABASE_PROJECT_REF`
   - **Project API keys** → **anon public** → `SUPABASE_ANON_KEY`

**Storage Location:** `wp-config.php` (WordPress root directory)

Add **BEFORE** the line `/* That's all, stop editing! Happy publishing. */`:
```php
// Supabase Bridge Configuration
putenv('SUPABASE_PROJECT_REF=abcdefghijk');  // Your project ref
putenv('SUPABASE_URL=https://abcdefghijk.supabase.co');  // Your project URL
putenv('SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...');  // anon public key
```

**Reference File:** See `wp-config-supabase-example.php` for complete annotated example

**Access Pattern in Plugin:**
```php
function sb_cfg($key, $default = null) {
  $value = getenv($key);
  return $value !== false ? $value : $default;
}

// Usage:
$project_ref = sb_cfg('SUPABASE_PROJECT_REF', '');
$url = sb_cfg('SUPABASE_URL', '');
$anon_key = sb_cfg('SUPABASE_ANON_KEY', '');
```

---

## 🔐 Security Architecture

### JWT Verification (JWKS-based)
**Why JWKS?**
- ✅ Public key cryptography (RS256 algorithm)
- ✅ No shared secrets in WordPress
- ✅ Automatic key rotation support (Supabase manages keys)
- ✅ Standard OAuth/OIDC practice

**Threat Model:**
- ❌ **Client cannot forge JWTs** (requires private key held only by Supabase)
- ❌ **Expired tokens rejected** (exp claim validation)
- ❌ **Wrong issuer rejected** (prevents token reuse from other services)
- ❌ **Tampered tokens rejected** (signature verification fails)

**Attack Vectors Mitigated:**
1. **Token Forgery:** Signature validation prevents fake JWTs
2. **Token Replay:** Expiration check limits validity window
3. **Token Reuse:** Issuer validation ensures Supabase origin
4. **Man-in-the-Middle:** HTTPS enforcement required

### Input Sanitization
**WordPress Security Functions:**
- `sanitize_email()` - Email addresses
- `sanitize_text_field()` - General text (sub, metadata)
- `wp_remote_get()` - Safe HTTP requests (no direct curl)

**Applied At:**
- JWT claims before database storage
- User metadata before `update_user_meta()`
- All REST endpoint parameters

### Configuration Security
**Secrets Handling:**
- ✅ **anon_key:** Public, safe to expose in frontend JavaScript
- ❌ **service_role_key:** NEVER used in this plugin (not needed)
- ✅ **JWKS URL:** Public endpoint, safe to fetch

**Best Practices:**
- Environment variables (not database options)
- No credentials in version control
- HTTPS required for production (SSL check in auth cookie)

---

## 🗄️ Data Architecture

### WordPress Database Tables Used

**`wp_users`**
| Column       | Usage                                      |
|--------------|--------------------------------------------|
| ID           | Auto-increment primary key                 |
| user_login   | Set to email (Supabase doesn't use login)  |
| user_pass    | Random hash (not used, login via Supabase) |
| user_email   | Synced from JWT `email` claim              |
| user_status  | Default 0 (active)                         |

**`wp_usermeta`**
| meta_key          | meta_value            | Purpose                      |
|-------------------|-----------------------|------------------------------|
| supabase_user_id  | UUID from JWT `sub`   | Link to Supabase auth.users  |

### Supabase Database Integration
**Current:** No direct database access (Auth only)

**Future Consideration:**
- Use `supabase_user_id` to link WordPress users with Supabase database records
- Enable RLS policies: `auth.uid() = (SELECT supabase_user_id FROM ...)`
- Sync additional user metadata (name, avatar, etc.)

---

## 🔄 Authentication Flow (Detailed)

### 1. Login Initiation (Browser)
```javascript
// User clicks "Login with Google"
const { createClient } = window.supabase;
const sb = createClient(window.SUPABASE_CFG.url, window.SUPABASE_CFG.anon);

await sb.auth.signInWithOAuth({
  provider: 'google',
  options: {
    redirectTo: 'https://example.com/supabase-callback/'
  }
});
```
**Result:** Browser redirected to Google OAuth consent screen

### 2. OAuth Authorization (External Provider)
- User approves permissions
- Google redirects to Supabase Auth service
- Supabase creates session and redirects to `redirectTo` URL

### 3. Callback Page (WordPress Page)
**URL:** `/supabase-callback/` (WordPress page with HTML block)

**JavaScript Execution:**
```javascript
// 1. Get session from Supabase (contains JWT)
const { data: { session }, error } = await sb.auth.getSession();

// 2. Send JWT to WordPress REST API
const response = await fetch('/wp-json/supabase-auth/callback', {
  method: 'POST',
  credentials: 'include', // Send/receive cookies
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    access_token: session.access_token,
    user: session.user // Optional: full user object
  })
});

// 3. Redirect to authenticated area
if (response.ok) {
  // Can redirect to any page: /account/, /thank-you/, /registr/, etc.
  window.location.href = '/account/';
}
```

### 4. WordPress REST Endpoint Processing
**Endpoint:** `POST /wp-json/supabase-auth/callback`

**Steps:**
1. Extract `access_token` from request body
2. Fetch JWKS from Supabase
3. Verify JWT signature and claims
4. Find or create WordPress user
5. Update `supabase_user_id` in usermeta
6. Set WordPress auth cookie via `wp_set_auth_cookie()`
7. Return success response

**Response:**
```json
{
  "ok": true,
  "user_id": 123
}
```

### 5. WordPress Session Established
- User now authenticated in WordPress
- Standard WordPress session cookies set
- Can access protected pages/admin area
- WordPress plugins/themes recognize user via `wp_get_current_user()`

### Logout Flow (Bidirectional)
```javascript
// 1. Logout from Supabase
await sb.auth.signOut();

// 2. Logout from WordPress
await fetch('/wp-json/supabase-auth/logout', {
  method: 'POST',
  credentials: 'include'
});

// 3. Redirect to homepage
window.location.href = '/';
```

**WordPress Logout Endpoint:**
```php
function() {
  wp_destroy_current_session();
  wp_clear_auth_cookie();
  return ['ok' => true];
}
```

---

## 📐 Design Decisions

### Decision 1: Single File Plugin
**Choice:** All logic in `supabase-bridge.php` (~100 lines)
**Rationale:**
- Simple deployment (drop file + `composer install`)
- Easy code review (all logic visible)
- No complex autoloading needed
- Minimal abstraction for 2 REST endpoints

**Trade-offs:**
- ✅ Easier to understand and audit
- ✅ Faster performance (no autoloader overhead)
- ❌ Harder to extend if plugin grows (solution: refactor when needed)

### Decision 2: Server-Side JWT Verification
**Choice:** Verify JWT on server using JWKS, not trust client
**Rationale:**
- Security: Client-provided JWTs cannot be trusted
- Standard: OAuth/OIDC best practice
- Flexibility: Supports key rotation automatically

**Alternatives Considered:**
- ❌ Trust client-provided user data: Insecure (easily spoofed)
- ❌ Use service_role key: Not needed, security risk if exposed
- ✅ JWKS verification: Industry standard, secure

### Decision 3: Environment Variables for Config
**Choice:** Use `getenv()` and `wp-config.php` for configuration
**Rationale:**
- Security: No credentials in database dumps
- Portability: Same code, different configs per environment
- WordPress standard: Sensitive data goes in wp-config.php

**Alternatives Considered:**
- ❌ WordPress options table: Exposes credentials in database
- ❌ Hardcoded values: Not portable, version control risk
- ✅ Environment variables: Best practice

### Decision 4: Create WordPress Users on First Login
**Choice:** Mirror Supabase users into `wp_users` table
**Rationale:**
- Compatibility: WordPress plugins expect `wp_users` records
- Functionality: Roles, capabilities, metadata work natively
- Integration: Seamless with existing WordPress ecosystem

**Alternatives Considered:**
- ❌ Virtual users (no wp_users records): Breaks most WordPress plugins
- ❌ Manual user creation: Poor UX, admin burden
- ✅ Automatic mirroring: Best UX, full compatibility

### Decision 5: No Custom UI for Configuration
**Choice:** Environment variables only, no admin settings page
**Rationale:**
- Security: Admin panel can be compromised (credentials safer in wp-config.php)
- Simplicity: One-time setup, no maintenance
- WordPress best practice: Sensitive config in wp-config.php

**Future Consideration:**
- Add admin UI for non-sensitive settings (button text, redirect URLs)
- Keep credentials in wp-config.php only

**Post-Login Redirect Customization:**
Currently configured via JavaScript in callback page. Common patterns:
- `/account/` - User dashboard
- `/thank-you/` or `/registr/` - Thank you/welcome page with instructions
- `/dashboard/` - Admin or member area
- External URL - Redirect to separate application

---

## 🚀 Performance Characteristics

### Plugin Overhead
- **Script Loading:** 1 CDN request (Supabase JS, ~50KB gzipped)
- **Configuration Injection:** ~200 bytes inline JavaScript
- **REST Endpoint:** ~1 request per login (not per page load)
- **Database Queries:** 1-2 queries per login (user lookup + metadata)

### Scalability
- **User Creation:** O(1) per new user (single INSERT)
- **JWKS Fetching:** Can be cached (recommended: 15 min TTL)
- **Session Management:** Standard WordPress cookies (scales to 10,000s of users)
- **Bottleneck:** JWKS fetch on high-traffic login spikes (solution: cache)

### Optimization Opportunities
1. **JWKS Caching:** Use WordPress transients API (`set_transient()`)
2. **Lazy Script Loading:** Only load Supabase JS on login/callback pages
3. **User Metadata Batch Updates:** Reduce `update_user_meta()` calls

---

## 🧩 Extension Points

### 1. Custom User Metadata Sync
**Example:** Sync Supabase user metadata to WordPress
```php
// In sb_handle_callback() after user creation:
if (isset($claims['user_metadata'])) {
  $metadata = $claims['user_metadata'];
  update_user_meta($user->ID, 'first_name', $metadata['first_name'] ?? '');
  update_user_meta($user->ID, 'last_name', $metadata['last_name'] ?? '');
}
```

### 2. Role Mapping (Supabase → WordPress)
**Example:** Map Supabase roles to WordPress roles
```php
// In sb_handle_callback() after user creation:
$supabase_role = $claims['app_metadata']['role'] ?? 'subscriber';
$role_map = [
  'admin' => 'administrator',
  'editor' => 'editor',
  'user' => 'subscriber'
];
$user->set_role($role_map[$supabase_role] ?? 'subscriber');
```

### 3. Custom OAuth Providers
**No Code Needed:** Configure in Supabase Dashboard
- Providers: Google, Apple, Facebook, GitHub, Discord, etc.
- Plugin automatically supports any provider Supabase enables

### 4. Email/Password Authentication
**Example:** Add email/password login form
```javascript
// Login with email/password
const { data, error } = await sb.auth.signInWithPassword({
  email: 'user@example.com',
  password: 'password123'
});

// Same callback flow as OAuth (sends JWT to WordPress)
```

### 5. Magic Link Authentication
**Example:** Passwordless login via email
```javascript
const { data, error } = await sb.auth.signInWithOtp({
  email: 'user@example.com',
  options: {
    emailRedirectTo: 'https://example.com/supabase-callback/'
  }
});
```

---

## 📚 Related Documentation

- **README.md** - User-facing documentation and setup guide
- **FLOW.md** - Authentication flow diagram (Mermaid)
- **BACKLOG.md** - Roadmap and implementation status
- **AGENTS.md** - AI assistant working instructions

---

## 🔄 Version History

- **2025-10-01:** Initial architecture documentation for v0.1.0

---

*This document describes the technical architecture and design decisions*
*Updated when architectural changes occur*
*Last updated: 2025-10-01*
