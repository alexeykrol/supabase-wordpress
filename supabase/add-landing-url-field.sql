-- Migration: Add landing_url field to wp_user_registrations
-- Purpose: Track the original landing page URL (with UTM/source parameters) for marketing analytics
-- Date: 2026-01-07

-- Add landing_url column
ALTER TABLE public.wp_user_registrations 
ADD COLUMN landing_url TEXT;

-- Add comment
COMMENT ON COLUMN public.wp_user_registrations.landing_url IS 'Original landing page URL where user first arrived (before auth form), cleaned from fbclid/gclid tracking parameters';

-- Create index for analytics queries
CREATE INDEX idx_wp_user_registrations_landing_url 
ON public.wp_user_registrations(landing_url);

-- Verify
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name = 'wp_user_registrations' 
AND column_name = 'landing_url';
