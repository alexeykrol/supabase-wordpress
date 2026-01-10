-- Add provider column to auth_telemetry table
-- Tracks authentication provider: 'magic_link', 'google', 'facebook'

ALTER TABLE auth_telemetry
ADD COLUMN provider TEXT;

-- Add index for faster queries by provider
CREATE INDEX idx_auth_telemetry_provider ON auth_telemetry(provider);

-- Comment
COMMENT ON COLUMN auth_telemetry.provider IS 'Authentication provider: magic_link, google, facebook';
