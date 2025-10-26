-- Phase 6 Table Fix - Add thankyou_page_url to wp_user_registrations
-- Issue: Missing column to store where user was redirected
-- Solution: Add thankyou_page_url for complete analytics

-- ============================================
-- Add thankyou_page_url column
-- ============================================

ALTER TABLE wp_user_registrations
ADD COLUMN IF NOT EXISTS thankyou_page_url TEXT;

-- ============================================
-- Verification
-- ============================================

-- Check table structure (should show thankyou_page_url)
-- \d wp_user_registrations

-- Expected columns:
-- - id (uuid, primary key)
-- - user_id (uuid)
-- - pair_id (uuid, nullable, FK)
-- - user_email (text)
-- - registration_url (text) ← where user registered
-- - thankyou_page_url (text) ← where user was redirected (NEW!)
-- - registered_at (timestamptz)
