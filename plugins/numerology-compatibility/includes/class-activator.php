<?php
// includes/class-activator.php

namespace NC;

use NC\Database\Database;
use NC\Admin\Settings;

class Activator {

    /**
     * Plugin activation
     */
    public static function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, NC_MIN_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(NC_PLUGIN_FILE));
            wp_die(sprintf(
                'This plugin requires PHP %s or higher. Your version: %s',
                NC_MIN_PHP_VERSION,
                PHP_VERSION
            ));
        }

        // Create database tables
        $db = Database::getInstance();
        $db->create_tables();

        // Set default options
        $defaults = Settings::get_defaults();
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Create upload directories
        $upload_dir = wp_upload_dir();
        $nc_dir = $upload_dir['basedir'] . '/numerology-compatibility';
        $log_dir = $upload_dir['basedir'] . '/nc-logs';

        if (!file_exists($nc_dir)) {
            wp_mkdir_p($nc_dir);
        }

        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);

            // Add .htaccess to protect logs
            $htaccess = $log_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, 'Deny from all');
            }
        }

        // Create rewrite rules
        add_rewrite_rule(
            '^dashboard/?$',
            'index.php?nc_dashboard=1',
            'top'
        );

        // Flush rewrite rules
        flush_rewrite_rules();

        // Schedule cron events
        if (!wp_next_scheduled('nc_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'nc_daily_cleanup');
        }

        // Set activation flag
        set_transient('nc_activation_redirect', true, 30);
    }
}