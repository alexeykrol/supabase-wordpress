<?php
/**
 * Supabase Bridge Configuration Example
 *
 * Add these lines to your wp-config.php file BEFORE the line:
 * "That's all, stop editing! Happy publishing."
 *
 * This configures the Supabase Bridge plugin to connect to your Supabase project.
 */

/* ========================================
   SUPABASE CONFIGURATION
   ======================================== */

/**
 * Supabase Project Reference
 *
 * Find this in your Supabase project URL:
 * https://[PROJECT_REF].supabase.co
 *
 * Example: if your URL is https://abcdefghijk.supabase.co
 * then your PROJECT_REF is "abcdefghijk"
 */
putenv('SUPABASE_PROJECT_REF=your-project-ref-here');

/**
 * Supabase Project URL
 *
 * Full URL to your Supabase project.
 * Format: https://[PROJECT_REF].supabase.co
 *
 * Find this in: Supabase Dashboard → Settings → API → Project URL
 */
putenv('SUPABASE_URL=https://your-project-ref-here.supabase.co');

/**
 * Supabase Anonymous (Public) Key
 *
 * This is the PUBLIC anon key - it's safe to expose in frontend JavaScript.
 *
 * Find this in: Supabase Dashboard → Settings → API → Project API keys → anon public
 *
 * ⚠️ IMPORTANT: Use ONLY the "anon public" key, NEVER the "service_role" key!
 * The service_role key gives admin access and should never be used in WordPress.
 */
putenv('SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.your-anon-key-here');

/* ========================================
   END SUPABASE CONFIGURATION
   ======================================== */


/* ========================================
   EXAMPLE: Complete wp-config.php snippet
   ======================================== */

/*

// Add this section to your wp-config.php between line 90-96:
// (After "Add any custom values between this line" comment)

// Supabase Bridge Configuration
// Replace with your actual Supabase project credentials
putenv('SUPABASE_PROJECT_REF=your-project-ref-here');
putenv('SUPABASE_URL=https://your-project-ref-here.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.your-actual-anon-key-here');

// That's all, stop editing! Happy publishing.

*/


/* ========================================
   SECURITY NOTES
   ======================================== */

/**
 * ✅ SAFE TO USE:
 * - SUPABASE_ANON_KEY (anon public key)
 * - SUPABASE_URL (public URL)
 * - SUPABASE_PROJECT_REF (public identifier)
 *
 * These are meant to be public and are exposed in frontend JavaScript.
 *
 * ❌ NEVER USE:
 * - service_role key (provides admin access)
 * - Direct database credentials
 *
 * The Supabase Bridge plugin ONLY uses the anon key for:
 * 1. Loading Supabase JS library in browser
 * 2. OAuth authentication (user identity only)
 *
 * All JWT verification happens server-side via JWKS (public key cryptography).
 */


/* ========================================
   TROUBLESHOOTING
   ======================================== */

/**
 * If you see error: "SUPABASE_PROJECT_REF not set"
 *
 * Make sure you added the putenv() lines BEFORE this line:
 * "That's all, stop editing! Happy publishing."
 *
 * If still not working, try using define() instead:
 *
 * define('SUPABASE_PROJECT_REF', 'your-project-ref');
 * define('SUPABASE_URL', 'https://your-project-ref.supabase.co');
 * define('SUPABASE_ANON_KEY', 'eyJhbGci...');
 *
 * And update supabase-bridge.php to use:
 * function sb_cfg($key, $def = null) {
 *   if (defined($key)) return constant($key);
 *   $v = getenv($key);
 *   return $v !== false ? $v : $def;
 * }
 */
