<?php
// plugins/numerology-compatibility/public/class-ajax-handler.php
namespace NC\PublicSite;

use NC\Api\ApiCalculations;

class AjaxHandler {

	/**
	 * Handle free calculation
	 * AJAX action: nc_calculate_free
	 *
	 * - Email is NOT required at this step
	 * - Returns secret_code and pdf_url
	 * - Email is NOT sent automatically
	 */
	public function handle_free_calculation() {
		try {
			// Verify nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Validate consent checkboxes
			$harm_consent = isset($_POST['harm_consent']) && ($_POST['harm_consent'] === '1' || $_POST['harm_consent'] === 'true' || $_POST['harm_consent'] === true);
			$entertainment_consent = isset($_POST['entertainment_consent']) && ($_POST['entertainment_consent'] === '1' || $_POST['entertainment_consent'] === 'true' || $_POST['entertainment_consent'] === true);

			if (!$harm_consent || !$entertainment_consent) {
				wp_send_json_error(['message' => __('All consent checkboxes must be accepted', 'numerology-compatibility')]);
			}

			// Execute free calculation (without email)
			$calc = new ApiCalculations();
			$result = $calc->calculate_free($_POST);

			// Return result with secret_code and pdf_url
			wp_send_json_success([
				'calculation_id' => $result['calculation_id'] ?? null,
				'secret_code' => $result['secret_code'] ?? null,
				'pdf_url' => $result['pdf_url'] ?? null,
				'type' => $result['type'] ?? 'free',
				'message' => __('Calculation completed! PDF report is being generated and will be available shortly.', 'numerology-compatibility')
			]);

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	/**
	 * Handle paid calculation
	 * AJAX action: nc_calculate_paid
	 *
	 * Returns checkout_url for redirect to payment page
	 */
	public function handle_paid_calculation() {
		try {
			// Verify nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Validate consent checkboxes
			$harm_consent = isset($_POST['harm_consent']) && ($_POST['harm_consent'] === '1' || $_POST['harm_consent'] === 'true' || $_POST['harm_consent'] === true);
			$entertainment_consent = isset($_POST['entertainment_consent']) && ($_POST['entertainment_consent'] === '1' || $_POST['entertainment_consent'] === 'true' || $_POST['entertainment_consent'] === true);

			if (!$harm_consent || !$entertainment_consent) {
				wp_send_json_error(['message' => __('All consent checkboxes must be accepted', 'numerology-compatibility')]);
			}

			// Get tier type (standard or premium)
			$tier = $_POST['tier'] ?? 'standard';

			// Create Checkout Session on backend
			$calc = new ApiCalculations();
			$result = $calc->calculate_paid($_POST, $tier);

			// Return checkout_url for redirect
			wp_send_json_success([
				'checkout_url' => $result['checkout_url'],
				'calculation_id' => $result['calculation_id'] ?? null,
				'message' => __('Redirecting to payment...', 'numerology-compatibility')
			]);

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	/**
	 * DEPRECATED: Legacy method for backward compatibility
	 * Use handle_free_calculation() or handle_paid_calculation()
	 */
	public function handle_calculation() {
		$this->handle_free_calculation();
	}

	/**
	 * DEPRECATED: Legacy method for backward compatibility
	 * Use handle_paid_calculation()
	 */
	public function handle_payment() {
		$this->handle_paid_calculation();
	}

	/**
	 * Get calculation by secret code
	 * AJAX action: nc_get_calculation
	 *
	 * Used on result page [numerology_result]
	 */
	public function handle_get_calculation() {
		try {
			// Check nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			$secret_code = sanitize_text_field($_POST['secret_code'] ?? '');

			if (empty($secret_code)) {
				wp_send_json_error(['message' => __('Secret code is required', 'numerology-compatibility')]);
			}

			// Get calculation from API
			$calc = new ApiCalculations();
			$result = $calc->get_calculation_by_code($secret_code);

			wp_send_json_success([
				'calculation_id' => $result['calculation_id'] ?? null,
				'secret_code' => $result['secret_code'] ?? $secret_code,
				'pdf_url' => $result['pdf_url'] ?? null,
				'pdf_ready' => $result['pdf_ready'] ?? false,
				'tier' => $result['tier'] ?? 'free',
				'is_paid' => $result['is_paid'] ?? false,
				'status' => $result['status'] ?? 'completed'
			]);

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	/**
	 * Send PDF report to email
	 * AJAX action: nc_send_email
	 *
	 * Accepts secret_code and email, sends PDF to specified address
	 */
	public function handle_send_email() {
		try {
			// Verify nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Get data
			$secret_code = sanitize_text_field($_POST['secret_code'] ?? '');
			$email = sanitize_email($_POST['email'] ?? '');

			// Validation
			if (empty($secret_code)) {
				wp_send_json_error(['message' => __('Secret code is required', 'numerology-compatibility')]);
			}

			if (empty($email) || !is_email($email)) {
				wp_send_json_error(['message' => __('Valid email is required', 'numerology-compatibility')]);
			}

			// Send email via API
			$calc = new ApiCalculations();
			$result = $calc->send_email($secret_code, $email);

			wp_send_json_success([
				'message' => __('PDF report will be sent to your email shortly!', 'numerology-compatibility'),
				'data' => $result
			]);

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	/**
	 * Export user data (GDPR)
	 */
	public function export_data() {
		try {
			// Verify nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			$email = sanitize_email($_POST['email'] ?? '');

			if (empty($email) || !is_email($email)) {
				wp_send_json_error(['message' => __('Valid email is required', 'numerology-compatibility')]);
			}

			global $wpdb;
			$calculations_table = $wpdb->prefix . 'nc_calculations';

			// Get all calculations for this email
			$calculations = $wpdb->get_results($wpdb->prepare(
				"SELECT * FROM $calculations_table WHERE email = %s",
				$email
			), ARRAY_A);

			if (empty($calculations)) {
				wp_send_json_error(['message' => __('No data found for this email', 'numerology-compatibility')]);
			}

			// Prepare export data
			$export_data = [
				'email' => $email,
				'export_date' => current_time('mysql'),
				'calculations' => $calculations
			];

			// Create JSON file
			$filename = 'numerology-data-' . sanitize_file_name($email) . '-' . time() . '.json';
			$json_data = json_encode($export_data, JSON_PRETTY_PRINT);

			// Send file
			header('Content-Type: application/json');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . strlen($json_data));

			echo $json_data;
			exit;

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	/**
	 * Delete user data (GDPR)
	 */
	public function delete_data() {
		try {
			// Verify nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			$email = sanitize_email($_POST['email'] ?? '');
			$confirmation = $_POST['confirmation'] ?? '';

			if (empty($email) || !is_email($email)) {
				wp_send_json_error(['message' => __('Valid email is required', 'numerology-compatibility')]);
			}

			if ($confirmation !== 'DELETE') {
				wp_send_json_error(['message' => __('Please type DELETE to confirm', 'numerology-compatibility')]);
			}

			global $wpdb;

			// Delete from calculations
			$calculations_table = $wpdb->prefix . 'nc_calculations';
			$wpdb->delete($calculations_table, ['email' => $email], ['%s']);

			// Delete from consents
			$consents_table = $wpdb->prefix . 'nc_consents';
			$wpdb->delete($consents_table, ['email' => $email], ['%s']);

			// Delete from analytics
			$analytics_table = $wpdb->prefix . 'nc_analytics';
			$wpdb->delete($analytics_table, ['email' => $email], ['%s']);

			// Delete from transactions
			$transactions_table = $wpdb->prefix . 'nc_transactions';
			$wpdb->delete($transactions_table, ['email' => $email], ['%s']);

			wp_send_json_success([
				'message' => __('All your data has been permanently deleted', 'numerology-compatibility')
			]);

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}
}