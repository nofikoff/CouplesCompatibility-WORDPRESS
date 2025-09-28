<?php
/**
 * Plugin Name: Numerology Compatibility Calculator
 * Plugin URI: https://CouplesCompatibility.com/
 * Description: Professional numerology compatibility calculator with PDF reports and payment integration
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Novikov
 * Author URI: https://CouplesCompatibility.com
 * License: GPL v2 or later
 * Text Domain: numerology-compatibility
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NC_VERSION', '1.0.0');
define('NC_PLUGIN_FILE', __FILE__);
define('NC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('NC_MIN_PHP_VERSION', '7.4');
define('NC_MIN_WP_VERSION', '5.8');

// Check requirements
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

// Autoloader
require_once NC_PLUGIN_DIR . 'vendor/autoload.php';

// Activation/Deactivation hooks
register_activation_hook(__FILE__, ['NC\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['NC\Deactivator', 'deactivate']);

// Initialize plugin
add_action('plugins_loaded', function() {
    $plugin = new \NC\Plugin();
    $plugin->run();
});