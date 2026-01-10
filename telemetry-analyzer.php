#!/usr/bin/env php
<?php
/**
 * Telemetry Analyzer Script (v0.11.0) - PHP Version
 *
 * Purpose: Analyze auth_telemetry data from Supabase using Claude API
 * Schedule: Run every 3 hours via cron
 * Output: Markdown reports saved to telemetry-reports/
 * Features: Telemetry events + "Waiting for Verification" users + Conversion metrics
 *
 * Usage: php telemetry-analyzer.php
 */

// Configuration
$script_dir = dirname(__FILE__);
$env_file = $script_dir . '/telemetry-analyzer.env';
$report_dir = $script_dir . '/telemetry-reports';

// Load environment variables
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!empty($key) && !empty($value)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Get configuration
$supabase_url = getenv('SUPABASE_URL') ?: '';
$supabase_anon_key = getenv('SUPABASE_ANON_KEY') ?: '';
$supabase_service_key = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '';
$claude_api_key = getenv('CLAUDE_API_KEY') ?: '';
$analysis_window_hours = (int)(getenv('ANALYSIS_WINDOW_HOURS') ?: 3);

// Constants
$claude_api_url = 'https://api.anthropic.com/v1/messages';
$claude_model = 'claude-sonnet-4-5-20250929';

/**
 * Log message with timestamp
 */
function log_message($message) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

/**
 * Error and exit
 */
function error_exit($message) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $message . PHP_EOL);
    exit(1);
}

/**
 * Check credentials
 */
function check_credentials($supabase_url, $supabase_anon_key, $claude_api_key) {
    if (empty($supabase_url)) {
        error_exit('SUPABASE_URL not set in telemetry-analyzer.env');
    }
    if (empty($supabase_anon_key)) {
        error_exit('SUPABASE_ANON_KEY not set in telemetry-analyzer.env');
    }
    if (empty($claude_api_key)) {
        error_exit('CLAUDE_API_KEY not set in telemetry-analyzer.env');
    }
}

/**
 * Fetch telemetry data from Supabase
 */
function fetch_telemetry_data($supabase_url, $supabase_anon_key, $hours) {
    log_message('Fetching telemetry data from Supabase...');

    // Calculate cutoff time
    $cutoff = gmdate('Y-m-d\TH:i:s', strtotime("-$hours hours")) . 'Z';

    // Build URL
    $url = $supabase_url . '/rest/v1/auth_telemetry?created_at=gte.' . urlencode($cutoff) . '&order=created_at.desc';

    // Make request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_anon_key,
        'Authorization: Bearer ' . $supabase_anon_key,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_exit("Supabase API returned HTTP $http_code");
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        error_exit('Invalid JSON response from Supabase');
    }

    return $data;
}

/**
 * Fetch users "Waiting for Verification" from Supabase Auth
 */
function fetch_waiting_users($supabase_url, $supabase_service_key) {
    log_message('Fetching "Waiting for Verification" users...');

    // Query users who haven't confirmed email
    $url = $supabase_url . '/auth/v1/admin/users';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_service_key,
        'Authorization: Bearer ' . $supabase_service_key,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        log_message("Warning: Could not fetch users (HTTP $http_code), continuing without this data");
        return [];
    }

    $result = json_decode($response, true);
    if (!isset($result['users'])) {
        log_message('Warning: Invalid response from Auth API, continuing without this data');
        return [];
    }

    // Filter users waiting for email verification
    $waiting_users = array_filter($result['users'], function($user) {
        return empty($user['email_confirmed_at']) && !empty($user['email']);
    });

    log_message('Found ' . count($waiting_users) . ' users waiting for verification');

    return array_values($waiting_users);
}

/**
 * Analyze data with Claude API
 */
function analyze_with_claude($telemetry_data, $waiting_users, $claude_api_key, $claude_api_url, $claude_model, $hours) {
    $event_count = count($telemetry_data);
    $waiting_count = count($waiting_users);
    log_message("Analyzing $event_count events + $waiting_count waiting users with Claude API...");

    if ($event_count === 0) {
        log_message("No telemetry events in last $hours hours - skipping analysis");
        return null;
    }

    // Build prompt
    $data_json = json_encode($telemetry_data, JSON_PRETTY_PRINT);

    // Add waiting users info
    $waiting_info = "";
    if ($waiting_count > 0) {
        $waiting_emails = array_map(function($user) {
            $created = isset($user['created_at']) ? substr($user['created_at'], 0, 10) : 'unknown';
            return "- {$user['email']} (created: $created)";
        }, array_slice($waiting_users, 0, 10)); // Show first 10

        $waiting_list = implode("\n", $waiting_emails);
        $more = $waiting_count > 10 ? "\n... and " . ($waiting_count - 10) . " more" : "";

        $waiting_info = "\n\n**Users \"Waiting for Verification\" (never confirmed email):**\nTotal: $waiting_count users\n\nSample:\n$waiting_list$more\n";
    }

    $prompt = "You are an expert in authentication systems and data analysis. Analyze this telemetry data from a WordPress + Supabase authentication system.

**Context:**
- MagicLink emails sent via Amazon SES
- OAuth providers: Google, Facebook
- Current bounce rate: 0.28%
- Reported failure rate: ~13%

**Telemetry Data (last $hours hours):**
```json
$data_json
```
$waiting_info

**Analysis Tasks:**

1. **Calculate Statistics:**
   - Total events
   - Event breakdown by type
   - Success rate (auth_success / total auth requests)
   - Failure rate (auth_failure / total auth requests)
   - Click-through rate (magic_link_clicked / magic_link_requested)
   - Email delivery rate (magic_link_clicked / magic_link_requested)
   - OAuth conversion rate (successful callbacks / oauth_requested)

2. **Identify Issues:**
   - Top error codes and their meanings
   - Patterns in failures (time-based, device-based, etc.)
   - Missing events in flow (e.g., magic_link_requested but no click)
   - \"Waiting for Verification\" users - are they stuck due to email delivery issues?

3. **Root Cause Analysis:**
   - Why are users failing authentication?
   - Are bounce rate (0.28%) and failure rate (13%) related?
   - Device switching issues (if any)
   - Why do users never confirm email (bounce, spam, user error)?

4. **Recommendations:**
   - Immediate fixes needed
   - UX improvements
   - Monitoring alerts to setup
   - How to reduce \"Waiting for Verification\" count

**Output Format:**
Provide a concise markdown report with these sections:
- 📊 Statistics Summary (include conversion rates)
- 🔴 Critical Issues
- 🟡 Warnings
- ✅ Working Well
- 💡 Recommendations

Keep it actionable and specific.";

    // Call Claude API
    $payload = [
        'model' => $claude_model,
        'max_tokens' => 4096,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ];

    $ch = curl_init($claude_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $claude_api_key,
        'anthropic-version: 2023-06-01',
        'content-type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_exit("Claude API returned HTTP $http_code: $response");
    }

    $result = json_decode($response, true);
    if (!isset($result['content'][0]['text'])) {
        $error = $result['error']['message'] ?? 'Unknown error';
        error_exit("Claude API error: $error");
    }

    return $result['content'][0]['text'];
}

/**
 * Save report to file
 */
function save_report($analysis, $event_count, $hours, $report_dir) {
    // Create report directory
    if (!is_dir($report_dir)) {
        mkdir($report_dir, 0755, true);
    }

    // Generate filename
    $timestamp = date('Y-m-d_H-i-s');
    $report_file = $report_dir . '/telemetry-report-' . $timestamp . '.md';

    // Build report
    $report = "# Telemetry Analysis Report

**Generated:** " . date('Y-m-d H:i:s') . "
**Analysis Window:** Last $hours hours
**Events Analyzed:** $event_count

---

$analysis

---

**Report ID:** $timestamp
**Analyzer Version:** v0.11.0
";

    file_put_contents($report_file, $report);

    log_message("Report saved: $report_file");
    return $report_file;
}

/**
 * Determine status from report content
 */
function get_status($report_file) {
    $content = file_get_contents($report_file);

    if (stripos($content, 'critical') !== false || stripos($content, '🔴') !== false) {
        return '🔴';
    } elseif (stripos($content, 'warning') !== false || stripos($content, '🟡') !== false) {
        return '🟡';
    }

    return '🟢';
}

// Main execution
log_message('Starting telemetry analysis...');

// Check credentials
check_credentials($supabase_url, $supabase_anon_key, $claude_api_key);

// Fetch data
$telemetry_data = fetch_telemetry_data($supabase_url, $supabase_anon_key, $analysis_window_hours);
$event_count = count($telemetry_data);

if ($event_count === 0) {
    log_message("No telemetry events in last $analysis_window_hours hours - exiting");
    exit(0);
}

// Fetch waiting users
$waiting_users = fetch_waiting_users($supabase_url, $supabase_service_key);

// Analyze with Claude
$analysis = analyze_with_claude($telemetry_data, $waiting_users, $claude_api_key, $claude_api_url, $claude_model, $analysis_window_hours);

if ($analysis === null) {
    exit(0);
}

// Save report
$report_file = save_report($analysis, $event_count, $analysis_window_hours, $report_dir);

// Get status
$status = get_status($report_file);

log_message("Analysis complete! Status: $status");
log_message("Report: $report_file");
