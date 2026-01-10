#!/usr/bin/env php
<?php
/**
 * Telemetry Analyzer Script (v0.12.0) - PHP Version
 *
 * Purpose: Validate telemetry code correctness + Analyze auth failures
 * Schedule: Run every hour via cron
 * Output: Markdown reports saved to telemetry-reports/
 *
 * Analysis Flow:
 * 1. Data Quality Check - compare Supabase Auth vs Telemetry
 * 2. If telemetry is correct - analyze failure causes
 * 3. If telemetry has issues - report code problems first
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
$analysis_window_hours = (int)(getenv('ANALYSIS_WINDOW_HOURS') ?: 1); // Default: 1 hour

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
 * Fetch auth stats from Supabase Auth for Data Quality Check
 * Returns: successful logins (last_sign_in_at) and waiting users (no login yet)
 */
function fetch_auth_stats($supabase_url, $supabase_service_key, $hours) {
    log_message('Fetching auth stats from Supabase Auth...');

    $stats = [
        'successful_logins' => 0,      // Users with last_sign_in_at in time window
        'waiting_users' => 0,          // Users created but never logged in
        'total_users_created' => 0,    // Users created in time window
        'users_detail' => []           // For debugging
    ];

    if (empty($supabase_service_key)) {
        log_message('Warning: SERVICE_ROLE_KEY not set, skipping auth stats');
        return $stats;
    }

    // Query users from Supabase Auth
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
        log_message("Warning: Could not fetch users (HTTP $http_code)");
        return $stats;
    }

    $result = json_decode($response, true);
    if (!isset($result['users'])) {
        log_message('Warning: Invalid response from Auth API');
        return $stats;
    }

    $cutoff = strtotime("-$hours hours");

    foreach ($result['users'] as $user) {
        // Check last_sign_in_at in time window (successful logins)
        $last_sign_in = isset($user['last_sign_in_at']) ? strtotime($user['last_sign_in_at']) : null;
        if ($last_sign_in && $last_sign_in >= $cutoff) {
            $stats['successful_logins']++;
        }

        // Check created_at in time window (new users)
        $created = strtotime($user['created_at'] ?? '');
        if ($created >= $cutoff) {
            $stats['total_users_created']++;

            // Waiting = created but never logged in
            if (empty($user['last_sign_in_at'])) {
                $stats['waiting_users']++;
                $stats['users_detail'][] = [
                    'email' => $user['email'] ?? 'unknown',
                    'created_at' => $user['created_at'] ?? ''
                ];
            }
        }
    }

    log_message("Auth stats: {$stats['successful_logins']} successful logins, {$stats['waiting_users']} waiting users");

    return $stats;
}

/**
 * Calculate telemetry stats from events
 */
function calculate_telemetry_stats($telemetry_data) {
    $stats = [
        'total_events' => count($telemetry_data),
        'magic_link_requested' => 0,
        'magic_link_clicked' => 0,
        'oauth_requested' => 0,
        'auth_success' => 0,
        'auth_failure' => 0,
        'failures_by_code' => []
    ];

    foreach ($telemetry_data as $event) {
        $event_type = $event['event'] ?? '';

        switch ($event_type) {
            case 'magic_link_requested':
                $stats['magic_link_requested']++;
                break;
            case 'magic_link_clicked':
                $stats['magic_link_clicked']++;
                break;
            case 'oauth_requested':
                $stats['oauth_requested']++;
                break;
            case 'auth_success':
                $stats['auth_success']++;
                break;
            case 'auth_failure':
                $stats['auth_failure']++;
                $error_code = $event['error_code'] ?? 'unknown';
                if (!isset($stats['failures_by_code'][$error_code])) {
                    $stats['failures_by_code'][$error_code] = 0;
                }
                $stats['failures_by_code'][$error_code]++;
                break;
        }
    }

    return $stats;
}

/**
 * Data Quality Check - compare Supabase Auth vs Telemetry
 * Returns quality report with pass/fail status
 */
function data_quality_check($auth_stats, $telemetry_stats) {
    log_message('Running Data Quality Check...');

    $quality = [
        'passed' => true,
        'checks' => [],
        'summary' => ''
    ];

    // Criterion 1: Successful logins (last_sign_in_at vs auth_success)
    $auth_successes = $auth_stats['successful_logins'];
    $telemetry_successes = $telemetry_stats['auth_success'];

    $check1 = [
        'name' => 'Successful Logins',
        'auth_value' => $auth_successes,
        'telemetry_value' => $telemetry_successes,
        'difference' => abs($auth_successes - $telemetry_successes),
        'passed' => true
    ];

    if ($auth_successes > 0) {
        $capture_rate = round(($telemetry_successes / $auth_successes) * 100, 1);
        $check1['capture_rate'] = $capture_rate;

        if ($capture_rate < 80) {
            $check1['passed'] = false;
            $quality['passed'] = false;
        }
    } else {
        $check1['capture_rate'] = 'N/A (no logins in period)';
    }

    $quality['checks'][] = $check1;

    // Criterion 2: Waiting users (MagicLink incomplete)
    // Auth: users created but never logged in
    // Telemetry: magic_link_requested - auth_success (simplified)
    $auth_waiting = $auth_stats['waiting_users'];
    $telemetry_incomplete = $telemetry_stats['magic_link_requested'] - $telemetry_stats['auth_success'];
    if ($telemetry_incomplete < 0) $telemetry_incomplete = 0;

    $check2 = [
        'name' => 'Incomplete MagicLink',
        'auth_value' => $auth_waiting,
        'telemetry_value' => $telemetry_incomplete,
        'difference' => abs($auth_waiting - $telemetry_incomplete),
        'passed' => true
    ];

    // Allow some tolerance (±2 or 30%)
    if ($auth_waiting > 0 && $check2['difference'] > max(2, $auth_waiting * 0.3)) {
        $check2['passed'] = false;
        // Don't fail overall for this - it's informational
    }

    $quality['checks'][] = $check2;

    // Summary
    if ($quality['passed']) {
        $quality['summary'] = 'Telemetry is working correctly. Data matches Supabase Auth.';
    } else {
        $quality['summary'] = 'Telemetry has issues! Some events are being lost.';
    }

    log_message("Data Quality Check: " . ($quality['passed'] ? 'PASSED' : 'FAILED'));

    return $quality;
}

/**
 * Analyze data with Claude API
 */
function analyze_with_claude($auth_stats, $telemetry_stats, $quality_check, $telemetry_data, $claude_api_key, $claude_api_url, $claude_model, $hours) {
    log_message("Analyzing data with Claude API...");

    $event_count = $telemetry_stats['total_events'];

    if ($event_count === 0 && $auth_stats['successful_logins'] === 0) {
        log_message("No data in last $hours hour(s) - skipping analysis");
        return null;
    }

    // Build data summary
    $auth_summary = json_encode($auth_stats, JSON_PRETTY_PRINT);
    $telemetry_summary = json_encode($telemetry_stats, JSON_PRETTY_PRINT);
    $quality_summary = json_encode($quality_check, JSON_PRETTY_PRINT);

    // Build failure details for analysis
    $failures_json = json_encode(
        array_filter($telemetry_data, function($e) { return ($e['event'] ?? '') === 'auth_failure'; }),
        JSON_PRETTY_PRINT
    );

    $prompt = "You are an expert in authentication systems. Analyze this data from a WordPress + Supabase auth system.

**IMPORTANT: This analysis has TWO goals:**
1. First, validate that telemetry code is working correctly
2. Only if telemetry is correct, analyze auth failure causes

## Data Sources (Last $hours hour)

### Source 1: Supabase Auth (Source of Truth)
```json
$auth_summary
```

### Source 2: Telemetry (Client-side tracking)
```json
$telemetry_summary
```

### Data Quality Check Results
```json
$quality_summary
```

### Auth Failure Events (for root cause analysis)
```json
$failures_json
```

## Analysis Tasks

### Step 0: Data Quality Validation (CRITICAL - DO THIS FIRST!)

Compare Supabase Auth vs Telemetry:
- **Criterion 1:** successful_logins (Auth) vs auth_success (Telemetry) - MUST match closely
- **Criterion 2:** waiting_users (Auth) vs incomplete requests (Telemetry)

If data doesn't match:
- Telemetry code has bugs
- Focus report on fixing telemetry code FIRST
- Do NOT analyze failure causes until telemetry is fixed

### Step 1: Failure Analysis (only if Step 0 passes)

If telemetry is working correctly, analyze:
- Why are users failing?
- Breakdown by error_code
- MagicLink vs OAuth failures
- Patterns and root causes
- Actionable recommendations

## Output Format

Provide a markdown report with these sections:

### 🔍 Data Quality Check
- Criterion 1: Successful Logins Match (Auth vs Telemetry)
- Criterion 2: Incomplete Requests Match
- Overall: PASS ✅ or FAIL ⚠️
- If FAIL: What's wrong with telemetry code?

### 📊 Statistics Summary (if quality passed)
- Success rate, failure rate
- Breakdown by provider

### 🔴 Issues Found
- If quality failed: Focus on telemetry code issues
- If quality passed: Focus on auth failure causes

### 💡 Recommendations
- Prioritized action items

Be concise and actionable.";

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
**Analyzer Version:** v0.12.0
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
log_message('Starting telemetry analysis (v0.12.0)...');
log_message("Analysis window: $analysis_window_hours hour(s)");

// Check credentials
check_credentials($supabase_url, $supabase_anon_key, $claude_api_key);

// Step 1: Fetch data from both sources
log_message('--- Step 1: Fetching data from both sources ---');

// Source 1: Supabase Auth (Source of Truth)
$auth_stats = fetch_auth_stats($supabase_url, $supabase_service_key, $analysis_window_hours);

// Source 2: Telemetry
$telemetry_data = fetch_telemetry_data($supabase_url, $supabase_anon_key, $analysis_window_hours);
$telemetry_stats = calculate_telemetry_stats($telemetry_data);

log_message("Telemetry: {$telemetry_stats['total_events']} events, {$telemetry_stats['auth_success']} successes, {$telemetry_stats['auth_failure']} failures");

// Check if we have any data
if ($telemetry_stats['total_events'] === 0 && $auth_stats['successful_logins'] === 0) {
    log_message("No data in last $analysis_window_hours hour(s) - exiting");
    exit(0);
}

// Step 2: Data Quality Check
log_message('--- Step 2: Data Quality Check ---');
$quality_check = data_quality_check($auth_stats, $telemetry_stats);

// Step 3: Analyze with Claude
log_message('--- Step 3: Claude Analysis ---');
$analysis = analyze_with_claude($auth_stats, $telemetry_stats, $quality_check, $telemetry_data, $claude_api_key, $claude_api_url, $claude_model, $analysis_window_hours);

if ($analysis === null) {
    exit(0);
}

// Save report
$report_file = save_report($analysis, $telemetry_stats['total_events'], $analysis_window_hours, $report_dir);

// Get status
$status = get_status($report_file);

log_message("Analysis complete! Status: $status");
log_message("Report: $report_file");
