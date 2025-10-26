-- Phase 1 Table Fix - Add missing columns to wp_user_registrations
-- Issue: Table created without user_id and pair_id columns
-- Solution: Add columns with proper constraints

-- ============================================
-- Add missing columns to wp_user_registrations
-- ============================================

-- Add user_id column (UUID, NOT NULL)
ALTER TABLE wp_user_registrations
ADD COLUMN IF NOT EXISTS user_id UUID;

-- Add pair_id column (UUID, nullable, foreign key to wp_registration_pairs)
ALTER TABLE wp_user_registrations
ADD COLUMN IF NOT EXISTS pair_id UUID
REFERENCES wp_registration_pairs(id) ON DELETE SET NULL;

-- ============================================
-- Create missing indexes
-- ============================================

-- Index for analytics queries
CREATE INDEX IF NOT EXISTS idx_user_registrations_pair
ON wp_user_registrations(pair_id, registered_at DESC);

CREATE INDEX IF NOT EXISTS idx_user_registrations_user
ON wp_user_registrations(user_id);

-- ============================================
-- Verification
-- ============================================

-- Check table structure
-- \d wp_user_registrations

-- Expected columns:
-- - id (uuid, primary key)
-- - user_id (uuid, NOT NULL after update)
-- - pair_id (uuid, nullable, FK to wp_registration_pairs)
-- - user_email (text, NOT NULL)
-- - registration_url (text, NOT NULL)
-- - registered_at (timestamptz)
