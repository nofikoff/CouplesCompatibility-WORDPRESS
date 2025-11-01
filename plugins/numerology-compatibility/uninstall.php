<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package NumerologyCompatibility
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has chosen to delete all data
$delete_on_uninstall = get_option('nc_delete_on_uninstall', false);

if ($delete_on_uninstall) {
    // Delete plugin options
    $options = [
        // General settings
        'nc_environment',
        'nc_terms_url',
        'nc_privacy_url',

        // API settings
        'nc_api_url',
        'nc_api_key',

        // Localization settings
        'nc_default_language',
        'nc_multilanguage',
        'nc_auto_currency',
        'nc_date_format',

        // Advanced settings
        'nc_debug_mode',
        'nc_cache_duration',
        'nc_rate_limit',
        'nc_delete_on_uninstall'
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    // Delete transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nc_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nc_%'");

    // Delete uploaded files
    $upload_dir = wp_upload_dir();
    $nc_dir = $upload_dir['basedir'] . '/numerology-compatibility';
    if (is_dir($nc_dir)) {
        nc_delete_directory($nc_dir);
    }

    // Delete logs
    $log_dir = $upload_dir['basedir'] . '/nc-logs';
    if (is_dir($log_dir)) {
        nc_delete_directory($log_dir);
    }

    // Clear rewrite rules
    flush_rewrite_rules();
}

/**
 * Recursively delete a directory
 */
function nc_delete_directory($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? nc_delete_directory($path) : unlink($path);
    }

    rmdir($dir);
}