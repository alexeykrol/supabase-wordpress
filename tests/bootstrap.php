<?php
/**
 * PHPUnit Bootstrap
 *
 * Loads plugin functions for unit testing without full WordPress environment
 */

// Autoload Composer dependencies (PHPUnit)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Mock WordPress functions that plugin uses
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {}
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {}
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {}
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.com' . $path;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        // Mock implementation for tests
        global $wp_options_mock;
        return $wp_options_mock[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $wp_options_mock;
        $wp_options_mock[$option] = $value;
        return true;
    }
}

if (!function_exists('get_site_url')) {
    function get_site_url() {
        return 'https://example.com';
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_js')) {
    function esc_js($text) {
        return addslashes($text);
    }
}

// Initialize mock options storage
global $wp_options_mock;
$wp_options_mock = [];

// Load only the functions we want to test (not the full plugin)
// We'll create a separate file with testable functions

echo "✓ PHPUnit bootstrap loaded\n";
