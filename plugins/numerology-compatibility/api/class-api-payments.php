<?php
// plugins/numerology-compatibility/api/class-api-payments.php
namespace NC\Api;

/**
 * Класс для работы с webhook'ами платежей
 * Все управление платежами происходит на бэкенде
 * Этот класс только принимает уведомления от бэкенда
 */
class ApiPayments {

	private $client;

	public function __construct() {
		$this->client = new ApiClient();
	}

	/**
	 * Обработка webhook от бэкенда
	 * POST /wp-json/numerology/v1/webhook/{gateway}
	 *
	 * Бэкенд отправляет уведомления о статусе платежа
	 * Webhook подписывается с помощью HMAC SHA256
	 *
	 * @param string $gateway Название платежного шлюза (stripe, paypal и т.д.)
	 * @return array|\WP_Error
	 */
	public function handle_webhook($gateway = 'stripe') {
		// Получаем payload и подпись
		$payload = @file_get_contents('php://input');
		$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

		// Проверяем подпись webhook
		if (!$this->verify_webhook_signature($payload, $signature)) {
			error_log('NC Webhook: Invalid signature');
			return new \WP_Error('invalid_signature', 'Invalid webhook signature', ['status' => 401]);
		}

		// Парсим данные
		$data = json_decode($payload, true);

		if (empty($data)) {
			error_log('NC Webhook: Invalid payload');
			return new \WP_Error('invalid_payload', 'Invalid webhook payload', ['status' => 400]);
		}

		// Логируем webhook
		error_log('NC Webhook received: ' . $gateway . ' | Event: ' . ($data['event_type'] ?? 'unknown'));

		// Обрабатываем различные события согласно спецификации
		$event_type = $data['event_type'] ?? '';

		switch ($event_type) {
			case 'payment.succeeded':
				$this->handle_payment_success($data);
				break;

			case 'payment.failed':
				$this->handle_payment_failure($data);
				break;

			case 'pdf.generated':
				$this->handle_pdf_generated($data);
				break;

			default:
				error_log('NC Webhook: Unknown event type - ' . $event_type);
		}

		return ['success' => true];
	}

	/**
	 * Проверка подписи webhook
	 *
	 * @param string $payload Тело запроса
	 * @param string $signature Подпись из заголовка
	 * @return bool
	 */
	private function verify_webhook_signature($payload, $signature) {
		$secret = get_option('nc_webhook_secret');

		if (empty($secret)) {
			error_log('NC Webhook: Secret not configured');
			return false;
		}

		$expected = hash_hmac('sha256', $payload, $secret);

		return hash_equals($expected, $signature);
	}

	/**
	 * Обработка успешного платежа
	 * Формат согласно спецификации:
	 * {
	 *   "event_type": "payment.succeeded",
	 *   "email": "user@example.com",
	 *   "calculation_id": 123,
	 *   "payment_id": 456,
	 *   "amount": 999,
	 *   "currency": "usd",
	 *   "tier": "standard",
	 *   "gateway": "stripe",
	 *   "gateway_payment_id": "pi_xxxxx",
	 *   "paid_at": "2025-10-26T10:30:00Z"
	 * }
	 *
	 * @param array $data Данные из webhook
	 */
	private function handle_payment_success($data) {
		$email = $data['email'] ?? null;
		$calculation_id = $data['calculation_id'] ?? null;
		$payment_id = $data['payment_id'] ?? null;
		$amount = $data['amount'] ?? 0;
		$tier = $data['tier'] ?? 'unknown';

		if (!$email) {
			error_log('NC Webhook payment.succeeded: No email in data');
			return;
		}

		// Логируем успех
		error_log(sprintf(
			'NC Webhook payment.succeeded: email=%s, calc_id=%s, payment_id=%s, tier=%s, amount=%s',
			$email,
			$calculation_id,
			$payment_id,
			$tier,
			$amount
		));

		// Обновляем локальную БД
		if ($calculation_id) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'nc_calculations';

			$wpdb->update(
				$table_name,
				['pdf_sent' => 1],
				['calculation_id' => $calculation_id],
				['%d'],
				['%s']
			);

			// Трекаем успешную оплату
			$analytics_table = $wpdb->prefix . 'nc_analytics';
			$wpdb->insert(
				$analytics_table,
				[
					'email' => $email,
					'event_type' => 'payment_completed',
					'event_data' => json_encode($data),
					'ip_address' => null,
					'user_agent' => null,
					'created_at' => current_time('mysql')
				],
				['%s', '%s', '%s', '%s', '%s', '%s']
			);
		}

		// Вызываем WordPress action для дополнительной обработки
		do_action('numerology_payment_succeeded', $data);

		// Отправляем email с подтверждением (опционально)
		$this->send_success_email($email, $calculation_id, $tier);
	}

	/**
	 * Обработка неудачного платежа
	 * Формат согласно спецификации:
	 * {
	 *   "event_type": "payment.failed",
	 *   "email": "user@example.com",
	 *   "calculation_id": 123,
	 *   "payment_id": 456,
	 *   "gateway": "stripe",
	 *   "gateway_payment_id": "pi_xxxxx",
	 *   "failure_reason": "Card declined",
	 *   "failed_at": "2025-10-26T10:30:00Z"
	 * }
	 *
	 * @param array $data Данные из webhook
	 */
	private function handle_payment_failure($data) {
		$email = $data['email'] ?? null;
		$calculation_id = $data['calculation_id'] ?? null;
		$reason = $data['failure_reason'] ?? 'Unknown error';

		if (!$email) {
			error_log('NC Webhook payment.failed: No email in data');
			return;
		}

		// Логируем ошибку
		error_log(sprintf(
			'NC Webhook payment.failed: email=%s, calc_id=%s, reason=%s',
			$email,
			$calculation_id,
			$reason
		));

		// Трекаем неудачную попытку оплаты
		global $wpdb;
		$analytics_table = $wpdb->prefix . 'nc_analytics';
		$wpdb->insert(
			$analytics_table,
			[
				'email' => $email,
				'event_type' => 'payment_failed',
				'event_data' => json_encode($data),
				'ip_address' => null,
				'user_agent' => null,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%s']
		);

		// Вызываем WordPress action для дополнительной обработки
		do_action('numerology_payment_failed', $data);

		// Отправляем email с уведомлением (опционально)
		$this->send_failure_email($email, $reason);
	}

	/**
	 * Обработка события генерации PDF
	 * Формат согласно спецификации:
	 * {
	 *   "event_type": "pdf.generated",
	 *   "email": "user@example.com",
	 *   "calculation_id": 123,
	 *   "type": "standard",
	 *   "pdf_url": "https://api.example.com/api/v1/calculations/123/pdf"
	 * }
	 *
	 * @param array $data Данные из webhook
	 */
	private function handle_pdf_generated($data) {
		$email = $data['email'] ?? null;
		$calculation_id = $data['calculation_id'] ?? null;
		$pdf_url = $data['pdf_url'] ?? null;

		if (!$email || !$calculation_id) {
			error_log('NC Webhook pdf.generated: Missing required fields');
			return;
		}

		// Логируем
		error_log(sprintf(
			'NC Webhook pdf.generated: email=%s, calc_id=%s, pdf_url=%s',
			$email,
			$calculation_id,
			$pdf_url
		));

		// Вызываем WordPress action
		do_action('numerology_pdf_ready', $data);

		// Можно отправить дополнительный email с прямой ссылкой на PDF
		// (опционально, если бэкенд еще не отправил)
	}

	/**
	 * Отправка email об успешной оплате
	 *
	 * @param string $email Email получателя
	 * @param string|null $calculation_id ID расчета
	 * @param string $tier Тип тарифа
	 */
	private function send_success_email($email, $calculation_id = null, $tier = '') {
		$subject = __('Payment Successful - Numerology Report', 'numerology-compatibility');

		$tier_name = ucfirst($tier);

		$message = __('Your payment has been processed successfully!', 'numerology-compatibility') . "\n\n";

		if ($tier) {
			$message .= sprintf(
				__('You have purchased the %s compatibility report.', 'numerology-compatibility'),
				$tier_name
			) . "\n\n";
		}

		$message .= __('Your compatibility report will be sent to this email address shortly.', 'numerology-compatibility') . "\n\n";

		if ($calculation_id) {
			$message .= sprintf(
				__('Calculation ID: %s', 'numerology-compatibility'),
				$calculation_id
			) . "\n\n";
		}

		$message .= __('Thank you for using our service!', 'numerology-compatibility');

		wp_mail($email, $subject, $message);
	}

	/**
	 * Отправка email о неудачной оплате
	 *
	 * @param string $email Email получателя
	 * @param string $reason Причина ошибки
	 */
	private function send_failure_email($email, $reason) {
		$subject = __('Payment Failed - Numerology Report', 'numerology-compatibility');

		$message = __('Unfortunately, your payment could not be processed.', 'numerology-compatibility') . "\n\n";
		$message .= sprintf(
			__('Reason: %s', 'numerology-compatibility'),
			$reason
		) . "\n\n";
		$message .= __('Please try again or contact our support team.', 'numerology-compatibility');

		wp_mail($email, $subject, $message);
	}
}