-- Supabase Tables for Registration Pairs Feature
-- Phase 1: Create tables for tracking registration/thank-you page pairs
-- Created: 2025-10-25
-- Version: 0.5.0

-- Table 1: Registration/Thank You page pairs
-- Stores mapping between registration pages and their thank you pages
CREATE TABLE IF NOT EXISTS wp_registration_pairs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  site_url TEXT NOT NULL,
  registration_page_url TEXT NOT NULL,
  thankyou_page_url TEXT NOT NULL,
  registration_page_id INTEGER,
  thankyou_page_id INTEGER,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  UNIQUE(site_url, registration_page_url)
);

-- Index for fast lookup by registration URL
CREATE INDEX IF NOT EXISTS idx_registration_pairs_url
ON wp_registration_pairs(site_url, registration_page_url);

-- Table 2: User registrations log
-- Tracks which user registered through which registration page
CREATE TABLE IF NOT EXISTS wp_user_registrations (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL,
  pair_id UUID REFERENCES wp_registration_pairs(id) ON DELETE SET NULL,
  user_email TEXT NOT NULL,
  registration_url TEXT NOT NULL,
  registered_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Index for analytics queries
CREATE INDEX IF NOT EXISTS idx_user_registrations_pair
ON wp_user_registrations(pair_id, registered_at DESC);

CREATE INDEX IF NOT EXISTS idx_user_registrations_user
ON wp_user_registrations(user_id);

-- Row Level Security (RLS) Policies
-- Enable RLS on both tables
ALTER TABLE wp_registration_pairs ENABLE ROW LEVEL SECURITY;
ALTER TABLE wp_user_registrations ENABLE ROW LEVEL SECURITY;

-- Policy: Allow authenticated users to read pairs for their site
CREATE POLICY "Users can read their site pairs"
ON wp_registration_pairs FOR SELECT
USING (auth.role() = 'authenticated');

-- Policy: Allow authenticated users to insert pairs
CREATE POLICY "Users can insert pairs"
ON wp_registration_pairs FOR INSERT
WITH CHECK (auth.role() = 'authenticated');

-- Policy: Allow authenticated users to update their site pairs
CREATE POLICY "Users can update their site pairs"
ON wp_registration_pairs FOR UPDATE
USING (auth.role() = 'authenticated');

-- Policy: Allow authenticated users to delete their site pairs
CREATE POLICY "Users can delete their site pairs"
ON wp_registration_pairs FOR DELETE
USING (auth.role() = 'authenticated');

-- Policy: Allow authenticated users to insert registration logs
CREATE POLICY "Users can insert registration logs"
ON wp_user_registrations FOR INSERT
WITH CHECK (auth.role() = 'authenticated');

-- Policy: Allow authenticated users to read registration logs
CREATE POLICY "Users can read registration logs"
ON wp_user_registrations FOR SELECT
USING (auth.role() = 'authenticated');

-- Updated_at trigger function
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to auto-update updated_at
CREATE TRIGGER update_wp_registration_pairs_updated_at
BEFORE UPDATE ON wp_registration_pairs
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- Comments for documentation
COMMENT ON TABLE wp_registration_pairs IS 'Stores registration page to thank you page mappings for WordPress sites';
COMMENT ON TABLE wp_user_registrations IS 'Logs user registrations for analytics and webhook triggers';

COMMENT ON COLUMN wp_registration_pairs.site_url IS 'WordPress site URL (e.g., https://questtales.com)';
COMMENT ON COLUMN wp_registration_pairs.registration_page_url IS 'Registration page path (e.g., /services/)';
COMMENT ON COLUMN wp_registration_pairs.thankyou_page_url IS 'Thank you page path (e.g., /services-thankyou/)';
COMMENT ON COLUMN wp_registration_pairs.registration_page_id IS 'WordPress page ID for registration page';
COMMENT ON COLUMN wp_registration_pairs.thankyou_page_id IS 'WordPress page ID for thank you page';
