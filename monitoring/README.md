# Data Integrity Monitoring

This directory contains scripts for monitoring data integrity across Supabase Auth, WordPress, and analytics systems.

## Scripts

### `check-integrity.sh`

Validates that user registrations are properly tracked across all systems:

- **Supabase Auth** (`auth.users`) - Source of truth for all registrations
- **WordPress Tracking** (`wp_user_registrations`) - Analytics and landing page tracking
- **Landing Page Attribution** (`pair_id`) - Ensures all registrations are linked to landing pages
- **Memberships & Enrollments** (planned) - Verifies users get proper access

## Setup

1. **Copy environment file:**
   ```bash
   cd monitoring
   cp .env.example .env
   ```

2. **Add Supabase credentials to `.env`:**
   - Get from: Supabase Dashboard → Settings → API
   - Fill in `SUPABASE_URL` and `SUPABASE_ANON_KEY`

3. **Configure SSH access:**
   - Ensure `~/.ssh/config` has `alexeykrol-prod` alias
   - Test: `ssh alexeykrol-prod "echo OK"`

## Usage

### Run integrity check for last hour:
```bash
./monitoring/check-integrity.sh
```

### Check specific time period:
```bash
./monitoring/check-integrity.sh "6 hours"
./monitoring/check-integrity.sh "24 hours"
./monitoring/check-integrity.sh "7 days"
```

## Output

The script reports:

1. **Registration Tracking:**
   - Compares `auth.users` vs `wp_user_registrations`
   - Alerts if any registrations were not tracked

2. **Landing Page Tracking:**
   - Checks how many registrations have `pair_id` set
   - Alerts if registrations are missing landing page attribution

3. **Memberships & Enrollments:** (coming soon)
   - Verifies users got proper membership assignments
   - Checks course enrollments

### Exit Codes

- `0` - All checks passed, no data integrity issues
- `1` - Data integrity issues detected (see report)

## Scheduling

To run automatically every hour during high-traffic periods:

```bash
# Add to crontab (crontab -e)
0 * * * * cd /path/to/supabase-bridge && ./monitoring/check-integrity.sh >> monitoring/logs/$(date +\%Y-\%m-\%d).log 2>&1
```

## Security

- `.env` file is in `.gitignore` and never committed
- SSH credentials stored in `~/.ssh/config`
- All Supabase queries use read-only anon key
- No data is modified, only queried

## Notes

- Script runs **locally**, not on production server
- Queries Supabase REST API directly
- Uses SSH to verify WordPress data
- Safe to run during production traffic
