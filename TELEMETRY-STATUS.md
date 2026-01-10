# Telemetry System Status (v0.10.2)

**Date:** 2025-01-09
**Status:** Process 1 Complete ✅ | Process 2 Ready for Setup 🔧

---

## What Was Built

### ✅ Process 1: Data Collection (COMPLETE)

**Status:** Active and collecting data from live traffic

**What it does:**
- Tracks 5 authentication events:
  1. `magic_link_requested` - User requested MagicLink email
  2. `magic_link_clicked` - User clicked link in email
  3. `oauth_requested` - User initiated OAuth (Google/Facebook)
  4. `auth_success` - Authentication succeeded
  5. `auth_failure` - Authentication failed (with error details)

**How it works:**
- Zero-impact tracking using `navigator.sendBeacon()`
- Fire-and-forget (no await, no blocking)
- Direct writes to Supabase `auth_telemetry` table
- Captures: email, error codes, URLs, timestamps, user agent

**Files Modified:**
- `auth-form.html` - Added `trackTelemetry()` function + 3 tracking points
- `test-no-elem-2-wordpress-paste.html` - Added `trackTelemetry()` function + 3 tracking points
- `supabase-bridge.php` - Added Telemetry tab in WordPress admin

**Database:**
- `auth_telemetry` table created in Supabase ✅
- RLS policies configured for anon INSERT/SELECT ✅
- Indexes on event, email, created_at ✅

**WordPress Admin:**
- New tab: **Supabase Bridge → Telemetry**
- Displays:
  - Live statistics (total events, success rate, failure rate)
  - Recent 100 events table
  - Event breakdown by type
  - Empty state when no data

**Deployment:**
- All files uploaded to production ✅
- Live on: alexeykrol.com

---

### 🔧 Process 2: Automated Analysis (READY FOR SETUP)

**Status:** Scripts created, needs manual configuration

**What it does:**
- Runs every 3 hours via cron
- Queries Supabase for new telemetry events
- Analyzes data using Claude API
- Generates markdown reports with:
  - Statistics summary
  - Critical issues identified
  - Root cause analysis
  - Actionable recommendations

**Files Created:**
- `telemetry-analyzer.sh` - Main analysis script
- `telemetry-analyzer.env` - Configuration (needs Claude API key)
- `telemetry-analyzer.env.example` - Template for reference
- `TELEMETRY-SETUP.md` - Complete setup guide

**Dependencies:**
- `jq` (JSON parser) - May need installation on server
- Claude API key - Get from https://console.anthropic.com/

**Cost Estimate:**
- Claude 3.5 Sonnet: ~$0.36/month (8 analyses/day)
- Can reduce to $0.18/month with 6-hour frequency

---

## What You Need to Do

### Step 1: Get Claude API Key

1. Go to: https://console.anthropic.com/settings/keys
2. Create new API key
3. Copy the key (starts with `sk-ant-api03-...`)

### Step 2: Configure on Server

SSH into production:
```bash
ssh -i ~/.ssh/claude_prod_new -p 65002 u465545808@45.145.187.249
```

Navigate to plugin directory:
```bash
cd /home/u465545808/domains/alexeykrol.com/public_html/wp-content/plugins/supabase-bridge
```

Edit env file:
```bash
nano telemetry-analyzer.env
```

Add your Claude API key:
```bash
CLAUDE_API_KEY=sk-ant-api03-YOUR-ACTUAL-KEY-HERE
```

Save and exit (Ctrl+X, Y, Enter).

### Step 3: Test the Script

Run manually:
```bash
bash telemetry-analyzer.sh
```

Expected output:
```
[2025-01-09 10:30:00] Starting telemetry analysis...
[2025-01-09 10:30:01] Fetching telemetry data from Supabase...
[2025-01-09 10:30:02] Analyzing 45 events with Claude API...
[2025-01-09 10:30:10] Report saved: ./telemetry-reports/telemetry-report-2025-01-09_10-30-10.md
[2025-01-09 10:30:10] Analysis complete! Status: 🟢
```

Check the report:
```bash
cat telemetry-reports/telemetry-report-*.md
```

### Step 4: Setup Cron Job

Edit crontab:
```bash
crontab -e
```

Add this line:
```cron
0 */3 * * * cd /home/u465545808/domains/alexeykrol.com/public_html/wp-content/plugins/supabase-bridge && bash telemetry-analyzer.sh >> /home/u465545808/domains/alexeykrol.com/public_html/wp-content/telemetry-analyzer.log 2>&1
```

Save and verify:
```bash
crontab -l
```

### Step 5: Monitor

After 24-48 hours:
1. Check WordPress admin: **Supabase Bridge → Telemetry**
2. Review automated reports in `telemetry-reports/` directory
3. Read Claude's analysis and recommendations
4. Implement fixes based on insights

---

## Current Data Collection

**Since:** 2025-01-09 (when telemetry tracking was deployed)

**Expected Data Points:**
- Every MagicLink request
- Every OAuth attempt
- Every authentication success/failure
- Error codes for failures
- Landing URL tracking (from UTM campaigns)

**View Live Data:**
1. Login to WordPress admin
2. Navigate to: **Supabase Bridge → Telemetry**
3. See real-time statistics and recent events

---

## Architecture

```
┌──────────────────────────────────────────────┐
│ Frontend (auth-form.html)                    │
├──────────────────────────────────────────────┤
│ User Action → trackTelemetry()               │
│              ↓                               │
│         sendBeacon() (non-blocking)          │
│              ↓                               │
│      Supabase REST API                       │
│              ↓                               │
│   INSERT INTO auth_telemetry                 │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ Callback (test-no-elem-2-wordpress-paste)    │
├──────────────────────────────────────────────┤
│ Auth Result → trackTelemetry()               │
│              ↓                               │
│         sendBeacon()                         │
│              ↓                               │
│      Supabase REST API                       │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ WordPress Admin Tab                          │
├──────────────────────────────────────────────┤
│ sb_render_telemetry_tab()                    │
│              ↓                               │
│    Query Supabase REST API                   │
│              ↓                               │
│  Display stats + recent events               │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ Cron Job (every 3 hours)                     │
├──────────────────────────────────────────────┤
│ telemetry-analyzer.sh                        │
│              ↓                               │
│    Query Supabase (last 3h)                  │
│              ↓                               │
│    Send to Claude API                        │
│              ↓                               │
│  Generate report (.md)                       │
│              ↓                               │
│  Save to telemetry-reports/                  │
└──────────────────────────────────────────────┘
```

---

## Troubleshooting

### No data in WordPress admin tab?

Check Supabase directly:
```bash
curl -H "apikey: YOUR_ANON_KEY" "https://mrwzuwdrmdfyleqwmuws.supabase.co/rest/v1/auth_telemetry?limit=10"
```

If empty, wait for live traffic to generate events.

### Script fails with "jq: command not found"?

Contact Hostinger support to install `jq` package.

### Reports not generating?

Check cron log:
```bash
tail -f /home/u465545808/domains/alexeykrol.com/public_html/wp-content/telemetry-analyzer.log
```

---

## Next Steps

1. **Today:** Configure Claude API key and test script
2. **Setup cron:** Run analyzer every 3 hours
3. **Wait 24-48h:** Collect meaningful data volume
4. **Review reports:** Read Claude's analysis
5. **Implement fixes:** Based on identified patterns

**Goal:** Reduce 13% failure rate by identifying and fixing root causes.

---

**Version:** v0.10.2
**Last Updated:** 2025-01-09
**Status:** Ready for production setup
