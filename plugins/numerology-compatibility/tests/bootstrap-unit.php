<?php
// tests/bootstrap-unit.php
// Bootstrap for unit tests with Brain\Monkey (no WordPress required)

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define plugin constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!defined('NC_PLUGIN_DIR')) {
    define('NC_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!defined('NC_PLUGIN_URL')) {
    define('NC_PLUGIN_URL', 'http://localhost/wp-content/plugins/numerology-compatibility/');
}

if (!defined('NC_VERSION')) {
    define('NC_VERSION', '1.0.0');
}
