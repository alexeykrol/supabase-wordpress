<?php
/**
 * LearnDash Free Course Banner Patch
 *
 * Removes the "NOT ENROLLED / Free / Take this Course" banner from free courses.
 *
 * Run this script after each LearnDash update:
 *   php patch-learndash-free-banner.php
 *
 * Or via WordPress admin (copy to theme or plugin):
 *   add_action('init', function() { include 'patch-learndash-free-banner.php'; });
 *
 * @version 1.0.0
 * @author Supabase Bridge
 */

// Find LearnDash plugin directory
$possible_paths = [
    __DIR__ . '/../wp-local/wp-content/plugins/sfwd-lms',           // Local dev
    dirname(__DIR__) . '/wp-content/plugins/sfwd-lms',              // WordPress root
    '/var/www/html/wp-content/plugins/sfwd-lms',                    // Docker/Linux
    'C:/xampp/htdocs/wp-content/plugins/sfwd-lms',                  // XAMPP Windows
];

// Also check if WP_CONTENT_DIR is defined
if (defined('WP_CONTENT_DIR')) {
    array_unshift($possible_paths, WP_CONTENT_DIR . '/plugins/sfwd-lms');
}

$learndash_path = null;
foreach ($possible_paths as $path) {
    if (is_dir($path)) {
        $learndash_path = $path;
        break;
    }
}

if (!$learndash_path) {
    echo "❌ LearnDash plugin not found. Checked paths:\n";
    foreach ($possible_paths as $path) {
        echo "   - $path\n";
    }
    exit(1);
}

echo "✅ LearnDash found at: $learndash_path\n";

// Target file
$target_file = $learndash_path . '/themes/ld30/templates/modules/infobar/course.php';

if (!file_exists($target_file)) {
    echo "❌ Target file not found: $target_file\n";
    exit(1);
}

echo "📄 Target file: $target_file\n";

// Read file content
$content = file_get_contents($target_file);

// Pattern to find (original code OR previously patched code)
$original_pattern = "<?php elseif ( 'open' !== \$course_pricing['type'] ) : ?>";
$previously_patched = "<?php elseif ( ! in_array( \$course_pricing['type'], array( 'open', 'free' ), true ) ) : /* PATCHED: Hide banner for free courses */ ?>";

// Replacement (completely disable the banner)
$patched_code = "<?php elseif ( false ) : /* PATCHED: Banner completely disabled - access controlled via MemberPress/Elementor */ ?>";

// Check if NEW patch already applied
if (strpos($content, 'Banner completely disabled') !== false) {
    echo "✅ Already patched with latest version! Nothing to do.\n";
    exit(0);
}

// Determine what to replace (original OR old patch)
$pattern_to_replace = null;
if (strpos($content, $previously_patched) !== false) {
    echo "🔄 Found old patch version - upgrading to full banner removal...\n";
    $pattern_to_replace = $previously_patched;
} elseif (strpos($content, $original_pattern) !== false) {
    echo "🔄 Found original code - applying full banner removal patch...\n";
    $pattern_to_replace = $original_pattern;
} else {
    echo "⚠️ Neither original nor old patch found. File may have been modified.\n";
    echo "\nLooking for patterns:\n";
    echo "  Original: $original_pattern\n";
    echo "  Old patch: $previously_patched\n";

    // Try to find similar patterns
    if (preg_match("/elseif.*'open'.*!==.*course_pricing/", $content, $matches)) {
        echo "\nFound similar pattern:\n" . $matches[0] . "\n";
    }
    exit(1);
}

// Create backup
$backup_file = $target_file . '.backup.' . date('Y-m-d-His');
if (copy($target_file, $backup_file)) {
    echo "💾 Backup created: $backup_file\n";
} else {
    echo "⚠️ Could not create backup, continuing anyway...\n";
}

// Apply patch
$patched_content = str_replace($pattern_to_replace, $patched_code, $content);

// Write patched file
if (file_put_contents($target_file, $patched_content)) {
    echo "✅ Patch applied successfully!\n";
    echo "\n";
    echo "📋 What changed:\n";
    echo "   BEFORE: Show banner for all courses except 'open'\n";
    echo "   AFTER:  Show banner for all courses except 'open' AND 'free'\n";
    echo "\n";
    echo "🎉 Done! The 'NOT ENROLLED / Free / Take this Course' banner is now hidden for free courses.\n";
} else {
    echo "❌ Failed to write patched file. Check file permissions.\n";
    exit(1);
}
