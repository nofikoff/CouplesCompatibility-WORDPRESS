<?php
// plugins/numerology-compatibility/public/class-public.php
namespace NC\PublicSite;

class PublicClass {

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register public styles
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			NC_PLUGIN_URL . 'public/assets/css/public.css',
			[],
			$this->version,
			'all'
		);
	}

	/**
	 * Register public scripts
	 */
	public function enqueue_scripts() {
		// Main plugin script
		wp_enqueue_script(
			$this->plugin_name . '-calculator',
			NC_PLUGIN_URL . 'public/assets/js/calculator.js',
			['jquery'],
			$this->version,
			true
		);

		// Localize script
		wp_localize_script($this->plugin_name . '-calculator', 'nc_public', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('nc_ajax_nonce'),
			'i18n' => [
				'loading' => __('Loading...', 'numerology-compatibility'),
				'error' => __('An error occurred', 'numerology-compatibility'),
				'success' => __('Success!', 'numerology-compatibility'),
				'redirecting' => __('Redirecting to payment...', 'numerology-compatibility')
			]
		]);
	}
}