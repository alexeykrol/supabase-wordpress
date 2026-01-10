# Telemetry System Setup Guide (v0.10.2)

## Overview

The telemetry system has two independent processes:

1. **Process 1: Data Collection** (Active ✅)
   - Frontend tracking via `navigator.sendBeacon()`
   - Events: magic_link_requested, magic_link_clicked, oauth_requested, auth_success, auth_failure
   - Storage: Supabase `auth_telemetry` table
   - Impact: Zero (non-blocking, fire-and-forget)

2. **Process 2: Automated Analysis** (Setup Required 🔧)
   - Bash script queries Supabase every 3 hours
   - Analyzes data using Claude API
   - Generates markdown reports with insights

## Setup Process 2: Automated Analysis

### Step 1: Install Dependencies

SSH into production server:
```bash
ssh -i ~/.ssh/claude_prod_new -p 65002 u465545808@45.145.187.249
```

Check if `jq` is installed:
```bash
jq --version
```

If not installed, contact hosting support or use alternative JSON parser.

### Step 2: Configure Claude API Key

Get API key from: https://console.anthropic.com/settings/keys

Edit the env file:
```bash
cd /home/u465545808/domains/alexeykrol.com/public_html/wp-content/plugins/supabase-bridge
nano telemetry-analyzer.env
```

Add your Claude API key:
```bash
CLAUDE_API_KEY=sk-ant-api03-your-actual-key-here
```

Save and exit (Ctrl+X, Y, Enter).

### Step 3: Test the Script

Run manually to verify it works:
```bash
cd /home/u465545808/domains/alexeykrol.com/public_html/wp-content/plugins/supabase-bridge
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
ls -la telemetry-reports/
cat telemetry-reports/telemetry-report-*.md
```

### Step 4: Setup Cron Job

Edit crontab:
```bash
crontab -e
```

Add this line to run analyzer every 3 hours:
```cron
0 */3 * * * cd /home/u465545808/domains/alexeykrol.com/public_html/wp-content/plugins/supabase-bridge && bash telemetry-analyzer.sh >> /home/u465545808/domains/alexeykrol.com/public_html/wp-content/telemetry-analyzer.log 2>&1
```

This runs at: 00:00, 03:00, 06:00, 09:00, 12:00, 15:00, 18:00, 21:00 (every 3 hours).

Save and exit.

Verify cron job:
```bash
crontab -l
```

### Step 5: Monitor Execution

Check logs:
```bash
tail -f /home/u465545808/domains/alexeykrol.com/public_html/wp-content/telemetry-analyzer.log
```

Check latest report:
```bash
ls -lt /home/u465545808/domains/alexeykrol.com/public_html/wp-content/plugins/supabase-bridge/telemetry-reports/ | head -5
```

## WordPress Admin Tab

View telemetry data in WordPress:
1. Login to WordPress admin
2. Navigate to: **Supabase Bridge → Telemetry**
3. See live statistics and recent events
4. Reports section (coming soon)

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│ Process 1: Data Collection (Client-Side)               │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  User Action → trackTelemetry() → sendBeacon()         │
│                                   ↓                     │
│                            Supabase REST API            │
│                                   ↓                     │
│                         auth_telemetry table            │
│                                                         │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Process 2: Automated Analysis (Server-Side Cron)       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Cron (every 3h) → telemetry-analyzer.sh                │
│                           ↓                             │
│                  Query Supabase API                     │
│                           ↓                             │
│                  Analyze with Claude API                │
│                           ↓                             │
│              Save report (.md file)                     │
│                           ↓                             │
│          WordPress admin displays reports               │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

## Security

- **telemetry-analyzer.env** - Contains secrets, NEVER commit to git
- **telemetry-reports/** - Contains analysis, safe to share
- All API calls use HTTPS
- Supabase has RLS (Row Level Security) enabled

## Troubleshooting

### Error: "jq: command not found"

Contact Hostinger support to install `jq` or use Python alternative:
```bash
python3 -m json.tool
```

### Error: "CLAUDE_API_KEY not set"

Verify .env file:
```bash
cat telemetry-analyzer.env | grep CLAUDE_API_KEY
```

Should show: `CLAUDE_API_KEY=sk-ant-api03-...`

### Error: "Invalid JSON response from Supabase"

Check Supabase credentials:
```bash
curl -H "apikey: YOUR_ANON_KEY" "https://YOUR_PROJECT.supabase.co/rest/v1/auth_telemetry?limit=1"
```

### No reports generated

Check if data exists:
```bash
# Last 3 hours of data
curl -s -H "apikey: YOUR_ANON_KEY" "https://YOUR_PROJECT.supabase.co/rest/v1/auth_telemetry?limit=10" | jq '.'
```

## Cost Estimate

**Claude API Costs:**
- Model: Claude 3.5 Sonnet
- Input: ~2K tokens/analysis (telemetry data)
- Output: ~1K tokens/analysis (report)
- Price: $3 per 1M input tokens, $15 per 1M output tokens
- Frequency: 8 times/day (every 3 hours)

Monthly cost: ~$0.36 (240 analyses × $0.0015 each)

**Optimization:**
- Use Claude 3.5 Haiku for cheaper analysis ($0.01/analysis)
- Adjust frequency to 6 hours (4 times/day) to halve costs

## Next Steps

After 24-48 hours of data collection:
1. Review automated reports in `telemetry-reports/`
2. Identify patterns in auth failures
3. Implement fixes based on Claude's recommendations
4. Monitor success/failure rate trends

---

**Version:** v0.10.2
**Last Updated:** 2025-01-09
