<?php
/**
 * Testable Functions
 *
 * Extracted from supabase-bridge.php for unit testing
 * These are copies of the actual functions to allow isolated testing
 */

// ========== VALIDATION FUNCTIONS ==========

/**
 * Validate email address
 */
function sb_test_validate_email($email) {
    if (!is_string($email)) {
        return false;
    }

    $email = trim($email);

    if (empty($email) || strlen($email) > 254) {
        return false;
    }

    // Basic format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // XSS protection: reject emails with HTML/script tags
    if (preg_match('/<|>|script|javascript|on\w+=/i', $email)) {
        return false;
    }

    return true;
}

/**
 * Validate URL path
 */
function sb_test_validate_url_path($path) {
    if (!is_string($path)) {
        return false;
    }

    $path = trim($path);

    if (empty($path) || strlen($path) > 2000) {
        return false;
    }

    // Must start with /
    if ($path[0] !== '/') {
        return false;
    }

    // XSS protection: reject paths with HTML/script tags
    if (preg_match('/<|>|script|javascript|on\w+=/i', $path)) {
        return false;
    }

    // Reject paths with dangerous characters
    if (preg_match('/["\']|%00|\\x00/i', $path)) {
        return false;
    }

    return $path;
}

/**
 * Validate UUID
 */
function sb_test_validate_uuid($uuid) {
    if (!is_string($uuid)) {
        return false;
    }

    $uuid = trim($uuid);

    // UUID v4 format: 8-4-4-4-12 hex digits
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    if (!preg_match($pattern, $uuid)) {
        return false;
    }

    return $uuid;
}

/**
 * Validate site URL
 */
function sb_test_validate_site_url($url) {
    if (!is_string($url)) {
        return false;
    }

    $url = trim($url);

    if (empty($url) || strlen($url) > 2000) {
        return false;
    }

    // Must be valid URL with http/https scheme
    $parsed = parse_url($url);

    if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
        return false;
    }

    if (!in_array($parsed['scheme'], ['http', 'https'])) {
        return false;
    }

    // XSS protection
    if (preg_match('/<|>|script|javascript|on\w+=/i', $url)) {
        return false;
    }

    return $url;
}

// ========== REGISTRATION PAIRS FUNCTIONS ==========

/**
 * Find Thank You page URL for a registration page
 */
function sb_test_get_thankyou_url_for_registration($registration_url, $pairs = []) {
    // Validate registration_url
    $validated_reg_url = sb_test_validate_url_path($registration_url);
    if (!$validated_reg_url) {
        return null;
    }

    // Find matching pair by registration_url
    foreach ($pairs as $pair) {
        if ($pair['registration_page_url'] === $validated_reg_url) {
            // Return absolute URL for thank you page
            return 'https://example.com' . $pair['thankyou_page_url'];
        }
    }

    // No matching pair found
    return null;
}

/**
 * Mock WordPress options for testing
 */
function sb_test_set_option($option, $value) {
    global $wp_options_mock;
    $wp_options_mock[$option] = $value;
}

function sb_test_get_option($option, $default = false) {
    global $wp_options_mock;
    return $wp_options_mock[$option] ?? $default;
}

function sb_test_reset_options() {
    global $wp_options_mock;
    $wp_options_mock = [];
}
