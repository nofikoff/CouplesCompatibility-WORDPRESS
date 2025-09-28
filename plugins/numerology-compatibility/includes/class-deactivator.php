<?php
// includes/class-deactivator.php

namespace NC;

class Deactivator {

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('nc_daily_cleanup');

        // Clear rewrite rules
        flush_rewrite_rules();

        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nc_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_nc_%'");

        // Log deactivation
        error_log('Numerology Compatibility Plugin deactivated');
    }
}