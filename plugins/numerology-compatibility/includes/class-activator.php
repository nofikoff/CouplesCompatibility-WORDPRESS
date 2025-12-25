<?php
namespace NC;

class Activator {

    /**
     * Plugin activation
     */
    public static function activate() {
        error_log('NC Activator: Starting activation...');

        // 1. Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(NC_PLUGIN_FILE));
            wp_die('This plugin requires PHP 7.4 or higher.');
        }
        error_log('NC Activator: PHP version check passed');

        // 2. Set default options
        self::set_default_options();
        error_log('NC Activator: Options set');

        // 3. Create directories
        self::create_directories();
        error_log('NC Activator: Directories created');

        // 4. Flush rewrite rules
        flush_rewrite_rules();
        error_log('NC Activator: Rewrite rules flushed');

        // 5. Set redirect flag
        set_transient('nc_activation_redirect', true, 30);
        error_log('NC Activator: Activation completed successfully');
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = [
            'nc_environment' => 'production',
            'nc_api_url' => 'https://api.your-domain.com',
            'nc_terms_url' => '/terms',
            'nc_privacy_url' => '/privacy',
            'nc_debug_mode' => 0,
            'nc_cache_duration' => 3600,
            'nc_rate_limit' => 10,
            'nc_delete_on_uninstall' => 0
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create required directories
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();

        $directories = [
            $upload_dir['basedir'] . '/numerology-compatibility',
            $upload_dir['basedir'] . '/nc-logs'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                error_log('NC Activator: Created directory - ' . $dir);
            }
        }

        // Protect logs directory
        $htaccess = $upload_dir['basedir'] . '/nc-logs/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, 'Deny from all');
        }
    }
}