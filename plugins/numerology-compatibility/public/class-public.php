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
				// General messages
				'loading' => __('Loading...', 'numerology-compatibility'),
				'error' => __('An error occurred', 'numerology-compatibility'),
				'success' => __('Success!', 'numerology-compatibility'),
				'redirecting' => __('Redirecting to payment...', 'numerology-compatibility'),

				// Form validation
				'birth_date_required' => __('Birth date is required', 'numerology-compatibility'),
				'birth_date_future' => __('Birth date cannot be in the future', 'numerology-compatibility'),
				'birth_date_invalid' => __('Please enter a valid birth date', 'numerology-compatibility'),
				'email_required' => __('Email is required', 'numerology-compatibility'),
				'email_invalid' => __('Please enter a valid email address', 'numerology-compatibility'),

				// Consent messages
				'consent_data_required' => __('You must confirm you have permission to use this data', 'numerology-compatibility'),
				'consent_harm_required' => __('You must agree not to use this information to harm others', 'numerology-compatibility'),
				'consent_entertainment_required' => __('You must acknowledge this is for entertainment purposes', 'numerology-compatibility'),

				// Processing messages
				'calculating' => __('Calculating your compatibility...', 'numerology-compatibility'),
				'please_wait' => __('Please wait...', 'numerology-compatibility'),
				'creating_payment' => __('Creating payment session...', 'numerology-compatibility'),
				'redirect_to_payment' => __('Please wait, you will be redirected to payment page...', 'numerology-compatibility'),

				// Package names
				'package_free' => __('Free Compatibility Report', 'numerology-compatibility'),
				'package_standard' => __('Standard Package Report', 'numerology-compatibility'),
				'package_premium' => __('Premium Package Report', 'numerology-compatibility'),
				'package_default' => __('Compatibility Report', 'numerology-compatibility'),

				// Error messages
				'calculation_failed' => __('Failed to complete calculation. Please try again.', 'numerology-compatibility'),
				'payment_failed' => __('Failed to create payment session. Please try again.', 'numerology-compatibility'),
				'payment_cancelled' => __('Payment was cancelled. Please try again.', 'numerology-compatibility'),
				'payment_timeout' => __('Payment verification timeout. If you completed the payment, please contact support with your payment confirmation.', 'numerology-compatibility'),
				'payment_status_unknown' => __('Unable to determine payment status. Please contact support with your payment confirmation.', 'numerology-compatibility'),
				'payment_verify_failed' => __('Unable to verify payment status. Please contact support with your payment confirmation.', 'numerology-compatibility'),

				// Success messages
				'payment_success_pdf_ready' => __('Payment successful! Your PDF report is ready.', 'numerology-compatibility'),
				'payment_success_generating' => __('Payment successful! Your PDF report is being generated...', 'numerology-compatibility'),
				'pdf_generating' => __('Your PDF report is being generated. This usually takes 5-10 seconds.', 'numerology-compatibility'),
				'pdf_url_missing' => __('PDF URL not available. Please contact support.', 'numerology-compatibility'),

				// Email sending
				'email_invalid_alert' => __('Please enter a valid email address', 'numerology-compatibility'),
				'secret_code_missing' => __('Secret code not found. Please recalculate.', 'numerology-compatibility'),
				'email_sending' => __('Sending...', 'numerology-compatibility'),
				'email_sent' => __('Sent!', 'numerology-compatibility'),
				'email_failed' => __('Failed to send email. Please try again.', 'numerology-compatibility'),

				// Payment polling
				'polling_started' => __('Starting payment polling for payment_id:', 'numerology-compatibility'),
				'polling_stopped' => __('Polling stopped (flag was set to false)', 'numerology-compatibility'),
				'payment_status_check' => __('Payment status check attempt', 'numerology-compatibility'),
				'payment_completed' => __('Payment completed!', 'numerology-compatibility'),
				'payment_failed_cancelled' => __('Payment failed or cancelled', 'numerology-compatibility'),
				'payment_pending' => __('Payment still pending, will check again in 3 seconds...', 'numerology-compatibility'),
				'payment_status_unknown_retry' => __('Unknown status', 'numerology-compatibility'),
				'payment_success_email' => __('Your payment was successful! Check your email for the PDF report.', 'numerology-compatibility'),

				// Calculator reset
				'calculator_reset' => __('Calculator reset to initial state', 'numerology-compatibility'),
				'pdf_download_ready' => __('PDF download link is ready', 'numerology-compatibility')
			]
		]);
	}
}