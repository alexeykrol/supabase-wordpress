-- Phase 3 RLS Fix - Allow Anon Key Server-Side Operations
-- Issue: WordPress uses Anon Key for server-side sync, but RLS requires authenticated users
-- Solution: Allow all operations for now (can restrict by site_url later if needed)

-- ============================================
-- Step 1: Drop existing restrictive policies
-- ============================================

DROP POLICY IF EXISTS "Users can insert pairs" ON wp_registration_pairs;
DROP POLICY IF EXISTS "Users can update their site pairs" ON wp_registration_pairs;
DROP POLICY IF EXISTS "Users can delete their site pairs" ON wp_registration_pairs;
DROP POLICY IF EXISTS "Users can read their site pairs" ON wp_registration_pairs;

DROP POLICY IF EXISTS "Users can insert registrations" ON wp_user_registrations;
DROP POLICY IF EXISTS "Users can read their site registrations" ON wp_user_registrations;

-- ============================================
-- Step 2: Create permissive policies for server sync
-- ============================================

-- Allow server to manage registration pairs
CREATE POLICY "Allow server sync for pairs"
ON wp_registration_pairs
FOR ALL
USING (true)
WITH CHECK (true);

-- Allow server to log user registrations
CREATE POLICY "Allow server sync for registrations"
ON wp_user_registrations
FOR ALL
USING (true)
WITH CHECK (true);

-- ============================================
-- Verification queries (optional)
-- ============================================

-- After running above, test with:
-- SELECT * FROM wp_registration_pairs;
-- SELECT * FROM wp_user_registrations;

-- Note: These policies are permissive for simplicity
-- Future enhancement: Restrict by site_url or use Service Role Key
