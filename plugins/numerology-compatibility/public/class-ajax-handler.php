<?php
// plugins/numerology-compatibility/public/class-ajax-handler.php
namespace NC\PublicSite;

use NC\Api\ApiCalculations;
use NC\Api\ApiPayments;

class AjaxHandler {

	/**
	 * Обработка бесплатного расчета
	 * AJAX action: nc_calculate_free
	 *
	 * НОВОЕ ПОВЕДЕНИЕ:
	 * - Email НЕ требуется на этом шаге (убран из формы)
	 * - Возвращает secret_code и pdf_url
	 * - Email НЕ отправляется автоматически
	 */
	public function handle_free_calculation() {
		try {
			// Проверка nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Проверка consent (проверяем что значение равно '1' или true)
			$harm_consent = isset($_POST['harm_consent']) && ($_POST['harm_consent'] === '1' || $_POST['harm_consent'] === 'true' || $_POST['harm_consent'] === true);
			$entertainment_consent = isset($_POST['entertainment_consent']) && ($_POST['entertainment_consent'] === '1' || $_POST['entertainment_consent'] === 'true' || $_POST['entertainment_consent'] === true);

			if (!$harm_consent || !$entertainment_consent) {
				wp_send_json_error(['message' => __('All consent checkboxes must be accepted', 'numerology-compatibility')]);
			}

			// Выполняем бесплатный расчет (БЕЗ email)
			$calc = new ApiCalculations();
			$result = $calc->calculate_free($_POST);

			// Возвращаем результат с secret_code и pdf_url
			// ПРИМЕЧАНИЕ: calculation_id и другие данные уже в $result от backend
			wp_send_json_success([
				'calculation_id' => $result['calculation_id'] ?? null,
				'secret_code' => $result['secret_code'] ?? null,
				'pdf_url' => $result['pdf_url'] ?? null, // ВАЖНО: не заменяем на пустую строку!
				'type' => $result['type'] ?? 'free',
				'message' => __('Calculation completed! PDF report is being generated and will be available shortly.', 'numerology-compatibility')
			]);

		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	/**
	 * Обработка платного расчета
	 * AJAX action: nc_calculate_paid
	 *
	 * Возвращает checkout_url для редиректа на страницу оплаты
	 */
	public function handle_paid_calculation() {
		try {
			// Проверка nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Проверка consent (проверяем что значение равно '1' или true)
			$harm_consent = isset($_POST['harm_consent']) && ($_POST['harm_consent'] === '1' || $_POST['harm_consent'] === 'true' || $_POST['harm_consent'] === true);
			$entertainment_consent = isset($_POST['entertainment_consent']) && ($_POST['entertainment_consent'] === '1' || $_POST['entertainment_consent'] === 'true' || $_POST['entertainment_consent'] === true);

			if (!$harm_consent || !$entertainment_consent) {
				wp_send_json_error(['message' => __('All consent checkboxes must be accepted', 'numerology-compatibility')]);
			}

			// Получаем тип тарифа (standard или premium)
			$tier = $_POST['tier'] ?? 'standard';

			// Создаем Checkout Session на бэкенде
			$calc = new ApiCalculations();
			$result = $calc->calculate_paid($_POST, $tier);

			// Возвращаем checkout_url для редиректа
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
	 * DEPRECATED: Старый метод, оставлен для обратной совместимости
	 * Используйте handle_free_calculation() или handle_paid_calculation()
	 */
	public function handle_calculation() {
		$this->handle_free_calculation();
	}

	/**
	 * DEPRECATED: Старый метод, оставлен для обратной совместимости
	 * Используйте handle_paid_calculation()
	 */
	public function handle_payment() {
		$this->handle_paid_calculation();
	}

	/**
	 * Получить расчет по секретному коду
	 * AJAX action: nc_get_calculation
	 *
	 * Используется на странице результата [numerology_result]
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
	 * Отправить PDF отчет на email
	 * AJAX action: nc_send_email
	 *
	 * НОВЫЙ ОБРАБОТЧИК для отправки PDF на email после расчета
	 * Принимает secret_code и email, отправляет PDF на указанный адрес
	 */
	public function handle_send_email() {
		try {
			// Проверка nonce
			if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
				wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
			}

			// Получаем данные
			$secret_code = sanitize_text_field($_POST['secret_code'] ?? '');
			$email = sanitize_email($_POST['email'] ?? '');

			// Валидация
			if (empty($secret_code)) {
				wp_send_json_error(['message' => __('Secret code is required', 'numerology-compatibility')]);
			}

			if (empty($email) || !is_email($email)) {
				wp_send_json_error(['message' => __('Valid email is required', 'numerology-compatibility')]);
			}

			// Отправляем email через API
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