<?php
namespace NC;

class Plugin {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'numerology-compatibility';
        $this->version = NC_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }

    private function load_dependencies() {
        // Core classes
        require_once NC_PLUGIN_DIR . 'includes/class-loader.php';
        require_once NC_PLUGIN_DIR . 'includes/class-i18n.php';

        // Admin classes
        require_once NC_PLUGIN_DIR . 'admin/class-admin.php';
        require_once NC_PLUGIN_DIR . 'admin/class-settings.php';

        // Public classes
        require_once NC_PLUGIN_DIR . 'public/class-public.php';
        require_once NC_PLUGIN_DIR . 'public/class-shortcodes.php';
        require_once NC_PLUGIN_DIR . 'public/class-ajax-handler.php';

        // API classes
        require_once NC_PLUGIN_DIR . 'api/class-api-client.php';
        require_once NC_PLUGIN_DIR . 'api/class-api-auth.php';
        require_once NC_PLUGIN_DIR . 'api/class-api-calculations.php';
        require_once NC_PLUGIN_DIR . 'api/class-api-payments.php';

        // Database
        require_once NC_PLUGIN_DIR . 'database/class-database.php';

        $this->loader = new Loader();
    }

    private function set_locale() {
        $plugin_i18n = new I18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new Admin\Admin($this->get_plugin_name(), $this->get_version());
        $plugin_settings = new Admin\Settings();

        // Admin menu and pages
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Settings
        $this->loader->add_action('admin_init', $plugin_settings, 'register_settings');

        // AJAX handlers for admin
        $this->loader->add_action('wp_ajax_nc_test_api_connection', $plugin_settings, 'test_api_connection');
        $this->loader->add_action('wp_ajax_nc_get_statistics', $plugin_admin, 'get_statistics');
    }

    private function define_public_hooks() {
        $plugin_public = new PublicSite\PublicClass($this->get_plugin_name(), $this->get_version());
        $shortcodes = new PublicSite\Shortcodes();
        $ajax_handler = new PublicSite\AjaxHandler();

        // Styles and scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Shortcodes
        $this->loader->add_action('init', $shortcodes, 'register_shortcodes');

        // AJAX handlers for frontend
        $this->loader->add_action('wp_ajax_nc_register', $ajax_handler, 'handle_registration');
        $this->loader->add_action('wp_ajax_nopriv_nc_register', $ajax_handler, 'handle_registration');

        $this->loader->add_action('wp_ajax_nc_login', $ajax_handler, 'handle_login');
        $this->loader->add_action('wp_ajax_nopriv_nc_login', $ajax_handler, 'handle_login');

        $this->loader->add_action('wp_ajax_nc_google_auth', $ajax_handler, 'handle_google_auth');
        $this->loader->add_action('wp_ajax_nopriv_nc_google_auth', $ajax_handler, 'handle_google_auth');

        $this->loader->add_action('wp_ajax_nc_calculate', $ajax_handler, 'handle_calculation');
        $this->loader->add_action('wp_ajax_nc_create_payment', $ajax_handler, 'handle_payment');

        $this->loader->add_action('wp_ajax_nc_get_history', $ajax_handler, 'get_calculation_history');
        $this->loader->add_action('wp_ajax_nc_delete_calculation', $ajax_handler, 'delete_calculation');
        $this->loader->add_action('wp_ajax_nc_download_pdf', $ajax_handler, 'download_pdf');

        $this->loader->add_action('wp_ajax_nc_delete_account', $ajax_handler, 'handle_account_deletion');

        // Add rewrite rules for dashboard
        $this->loader->add_action('init', $plugin_public, 'add_rewrite_rules');
        $this->loader->add_filter('query_vars', $plugin_public, 'add_query_vars');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_dashboard_template');
    }

    private function define_api_hooks() {
        // REST API endpoints for webhooks
        add_action('rest_api_init', function() {
            register_rest_route('numerology/v1', '/stripe-webhook', [
                'methods' => 'POST',
                'callback' => [new Api\ApiPayments(), 'handle_stripe_webhook'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}