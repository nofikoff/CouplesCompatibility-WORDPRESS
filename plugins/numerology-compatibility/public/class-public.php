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
			'api_base_url' => rtrim(get_option('nc_api_url', 'http://localhost:8088'), '/') . '/api/v1',
			'i18n' => [
				'loading' => __('Loading...', 'numerology-compatibility'),
				'error' => __('An error occurred', 'numerology-compatibility'),
				'success' => __('Success!', 'numerology-compatibility'),
				'redirecting' => __('Redirecting to payment...', 'numerology-compatibility'),
				'birth_date_required' => __('Birth date is required', 'numerology-compatibility'),
				'email_required' => __('Email is required', 'numerology-compatibility'),
				'valid_email_required' => __('Please enter a valid email address', 'numerology-compatibility'),
				'harm_consent_required' => __('You must agree not to use this information to harm others', 'numerology-compatibility'),
				'entertainment_consent_required' => __('You must acknowledge this is for entertainment purposes', 'numerology-compatibility'),
				'payment_failed' => __('Payment failed. Please try again.', 'numerology-compatibility'),
				'payment_timeout' => __('Payment verification timed out. Please contact support if you were charged.', 'numerology-compatibility'),
				// HTML5 валидационные сообщения
				'field_required' => __('Please fill in this field', 'numerology-compatibility'),
				'checkbox_required' => __('Please check this box if you want to continue', 'numerology-compatibility'),
				// Сообщения процесса обработки
				'calculating_compatibility' => __('Calculating your compatibility...', 'numerology-compatibility'),
				'please_wait' => __('Please wait...', 'numerology-compatibility'),
				'creating_payment_session' => __('Creating payment session...', 'numerology-compatibility'),
				'redirecting_to_payment' => __('Please wait, you will be redirected to payment page...', 'numerology-compatibility'),
				'pdf_generating' => __('Your PDF report is being generated. This usually takes 5-10 seconds.', 'numerology-compatibility'),
				'pdf_not_available' => __('PDF URL not available. Please contact support.', 'numerology-compatibility'),
				'pdf_timeout' => __('PDF is taking longer than expected. Please try downloading in a moment.', 'numerology-compatibility'),
				'pdf_ready' => __('PDF is ready for download!', 'numerology-compatibility'),
			]
		]);
	}
}