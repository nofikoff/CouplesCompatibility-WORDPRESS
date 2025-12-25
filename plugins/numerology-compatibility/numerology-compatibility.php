<?php
/**
 * Plugin Name: Numerology Compatibility Calculator
 * Plugin URI: https://couplescompatibility.com/
 * Description: Professional numerology compatibility calculator with PDF reports and payment integration
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Ruslan Novikov
 * Author URI: https://novikov.ua
 * License: GPL v2 or later
 * Text Domain: numerology-compatibility
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NC_VERSION', '1.0.1');
define('NC_PLUGIN_FILE', __FILE__);
define('NC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NC_MIN_PHP_VERSION', '7.4');
define('NC_MIN_WP_VERSION', '5.8');

if (version_compare(PHP_VERSION, NC_MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php printf(
                    __('Numerology Compatibility requires PHP %s or higher. Your version: %s', 'numerology-compatibility'),
                    NC_MIN_PHP_VERSION,
                    PHP_VERSION
                ); ?></p>
        </div>
        <?php
    });
    return;
}

$autoloader_path = NC_PLUGIN_DIR . 'vendor/autoload.php';
if (!file_exists($autoloader_path)) {
    add_action('admin_notices', function() use ($autoloader_path) {
        ?>
        <div class="notice notice-error">
            <p><strong>Numerology Compatibility Error:</strong> Autoloader not found!</p>
            <p>Please run <code>composer install</code> in the plugin directory.</p>
            <p>Expected path: <code><?php echo esc_html($autoloader_path); ?></code></p>
        </div>
        <?php
    });

    require_once NC_PLUGIN_DIR . 'includes/class-activator.php';
    require_once NC_PLUGIN_DIR . 'includes/class-deactivator.php';
} else {
    require_once $autoloader_path;
}

if (!class_exists('NC\Activator')) {
    $required_files = [
        'includes/class-activator.php',
        'includes/class-deactivator.php',
        'includes/class-plugin.php',
        'includes/class-loader.php',
        'includes/class-i18n.php'
    ];

    foreach ($required_files as $file) {
        $file_path = NC_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log('NC Plugin: Missing file - ' . $file_path);
        }
    }
}

register_activation_hook(__FILE__, function() {
    error_log('NC Plugin: Activation hook triggered');

    if (class_exists('NC\Activator')) {
        NC\Activator::activate();
        error_log('NC Plugin: Activation successful');
    } else {
        error_log('NC Plugin: Activator class not found');
        wp_die('Plugin activation failed: Activator class not found. Please check the plugin files.');
    }
});

register_deactivation_hook(__FILE__, function() {
    if (class_exists('NC\Deactivator')) {
        NC\Deactivator::deactivate();
    }
});

add_action('plugins_loaded', function() {
    if (class_exists('NC\Plugin')) {
        $plugin = new \NC\Plugin();
        $plugin->run();
    } else {
        error_log('NC Plugin: Plugin class not found during initialization');

        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>Numerology Compatibility Error:</strong> Plugin class not found!</p>
                <p>The plugin cannot initialize properly. Please check the installation.</p>
            </div>
            <?php
        });
    }
});

if (!function_exists('nc_debug')) {
    function nc_debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NC Debug: ' . $message);
            if ($data !== null) {
                error_log('NC Debug Data: ' . print_r($data, true));
            }
        }
    }
}