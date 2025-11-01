<?php
// plugins/numerology-compatibility/includes/class-plugin.php
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
		require_once NC_PLUGIN_DIR . 'api/class-api-calculations.php';

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

		// AJAX handlers for calculations - новые endpoints
		$this->loader->add_action('wp_ajax_nc_calculate_free', $ajax_handler, 'handle_free_calculation');
		$this->loader->add_action('wp_ajax_nopriv_nc_calculate_free', $ajax_handler, 'handle_free_calculation');

		$this->loader->add_action('wp_ajax_nc_calculate_paid', $ajax_handler, 'handle_paid_calculation');
		$this->loader->add_action('wp_ajax_nopriv_nc_calculate_paid', $ajax_handler, 'handle_paid_calculation');

		// DEPRECATED: Старые endpoints для обратной совместимости
		$this->loader->add_action('wp_ajax_nc_calculate', $ajax_handler, 'handle_calculation');
		$this->loader->add_action('wp_ajax_nopriv_nc_calculate', $ajax_handler, 'handle_calculation');

		$this->loader->add_action('wp_ajax_nc_create_payment', $ajax_handler, 'handle_payment');
		$this->loader->add_action('wp_ajax_nopriv_nc_create_payment', $ajax_handler, 'handle_payment');

		// GDPR handlers
		$this->loader->add_action('wp_ajax_nc_export_data', $ajax_handler, 'export_data');
		$this->loader->add_action('wp_ajax_nopriv_nc_export_data', $ajax_handler, 'export_data');

		$this->loader->add_action('wp_ajax_nc_delete_data', $ajax_handler, 'delete_data');
		$this->loader->add_action('wp_ajax_nopriv_nc_delete_data', $ajax_handler, 'delete_data');
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