<?php
// plugins/numerology-compatibility/public/class-ajax-handler.php
namespace NC\PublicSite;

use NC\Api\ApiCalculations;
use NC\Api\ApiPayments;

class AjaxHandler {

	/**
	 * Create payment intent
	 */
	public function handle_payment() {
		try {
			// Verify nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Validate email
			$email = sanitize_email($_POST['email'] ?? '');
			if (empty($email) || !is_email($email)) {
				wp_send_json_error(['message' => __('Valid email is required', 'numerology-compatibility')]);
			}

			// Validate dates
			$person1_date = $_POST['person1_date'] ?? '';
			$person2_date = $_POST['person2_date'] ?? '';

			if (empty($person1_date) || empty($person2_date)) {
				wp_send_json_error(['message' => __('Both birth dates are required', 'numerology-compatibility')]);
			}

			// Validate date format
			$date1 = \DateTime::createFromFormat('Y-m-d', $person1_date);
			$date2 = \DateTime::createFromFormat('Y-m-d', $person2_date);

			if (!$date1 || !$date2) {
				wp_send_json_error(['message' => __('Invalid date format', 'numerology-compatibility')]);
			}

			// Check dates are not in future
			$today = new \DateTime();
			if ($date1 > $today || $date2 > $today) {
				wp_send_json_error(['message' => __('Birth dates cannot be in the future', 'numerology-compatibility')]);
			}

			// Check consent
			if (empty($_POST['data_consent']) || empty($_POST['harm_consent']) || empty($_POST['entertainment_consent'])) {
				wp_send_json_error(['message' => __('All consent checkboxes must be accepted', 'numerology-compatibility')]);
			}

			$package_type = $_POST['package_type'] ?? 'free';

			// Create payment intent
			$payments = new ApiPayments();
			$payment_result = $payments->create_payment_intent($package_type, $_POST);

			wp_send_json_success($payment_result);

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	/**
	 * Handle calculation request
	 */
	public function handle_calculation() {
		try {
			// Verify nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Validate email
			$email = sanitize_email($_POST['email'] ?? '');
			if (empty($email) || !is_email($email)) {
				wp_send_json_error(['message' => __('Valid email is required', 'numerology-compatibility')]);
			}

			// Validate dates
			$person1_date = $_POST['person1_date'] ?? '';
			$person2_date = $_POST['person2_date'] ?? '';

			if (empty($person1_date) || empty($person2_date)) {
				wp_send_json_error(['message' => __('Both birth dates are required', 'numerology-compatibility')]);
			}

			// Validate date format
			$date1 = \DateTime::createFromFormat('Y-m-d', $person1_date);
			$date2 = \DateTime::createFromFormat('Y-m-d', $person2_date);

			if (!$date1 || !$date2) {
				wp_send_json_error(['message' => __('Invalid date format', 'numerology-compatibility')]);
			}

			// Check dates are not in future
			$today = new \DateTime();
			if ($date1 > $today || $date2 > $today) {
				wp_send_json_error(['message' => __('Birth dates cannot be in the future', 'numerology-compatibility')]);
			}

			// Check consent
			if (empty($_POST['data_consent']) || empty($_POST['harm_consent']) || empty($_POST['entertainment_consent'])) {
				wp_send_json_error(['message' => __('All consent checkboxes must be accepted', 'numerology-compatibility')]);
			}

			$package_type = $_POST['package_type'] ?? 'free';

			// Process calculation
			$calc = new ApiCalculations();
			$result = $calc->calculate($_POST, $package_type);

			wp_send_json_success([
				'calculation' => $result,
				'message' => sprintf(
					__('Success! Your %s compatibility report has been sent to %s', 'numerology-compatibility'),
					ucfirst($package_type),
					$email
				)
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