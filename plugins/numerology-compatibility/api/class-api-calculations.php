<?php
// plugins/numerology-compatibility/api/class-api-calculations.php
namespace NC\Api;

/**
 * Класс для работы с API расчетов нумерологии
 * Взаимодействует с бэкендом согласно спецификации OpenAPI
 */
class ApiCalculations {

	private $client;

	public function __construct() {
		$this->client = new ApiClient();
	}

	/**
	 * Бесплатный расчет
	 * POST /api/v1/calculate/free
	 *
	 * - Email ОТСУТСТВУЕТ на этом шаге (запрашивается после расчета)
	 * - Бэкенд возвращает secret_code для доступа к расчету
	 * - Бэкенд возвращает pdf_url для скачивания PDF
	 *
	 * @param array $data Данные формы (person1_date, person2_date)
	 * @return array Результат расчета с secret_code и pdf_url
	 * @throws \Exception
	 */
	public function calculate_free($data) {
		$this->validate_calculation_data($data);

		$locale = $this->get_current_locale();

		// Подготавливаем данные согласно API спецификации
		$request_data = [
			'person1_date' => sanitize_text_field($data['person1_date']),
			'person2_date' => sanitize_text_field($data['person2_date']),
			'locale' => $locale,
		];

		// Отправляем запрос на бэкенд
		$response = $this->client->request('/calculate/free', 'POST', $request_data);

		// Laravel API возвращает данные в формате {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response)) {
			return $data_response;
		}

		throw new \Exception(__('Calculation failed', 'numerology-compatibility'));
	}

	/**
	 * Платный расчет - создание Checkout Session
	 * POST /api/v1/calculate/paid
	 *
	 * - Email ОТСУТСТВУЕТ на этом шаге (запрашивается ПОСЛЕ оплаты)
	 * - Бэкенд возвращает secret_code для доступа к расчету
	 * - После оплаты пользователь может указать email для отправки PDF + чека
	 *
	 * @param array $data Данные формы (person1_date, person2_date)
	 * @param string $tier Тип тарифа (standard|premium)
	 * @return array {checkout_url, calculation_id, secret_code} для редиректа на оплату
	 * @throws \Exception
	 */
	public function calculate_paid($data, $tier) {
		$this->validate_calculation_data($data);
		$this->validate_tier($tier);

		$locale = $this->get_current_locale();

		$request_data = [
			'person1_date' => sanitize_text_field($data['person1_date']),
			'person2_date' => sanitize_text_field($data['person2_date']),
			'tier' => $tier,
			'locale' => $locale,
		];

		// Отправляем запрос на создание Checkout Session
		$response = $this->client->request('/calculate/paid', 'POST', $request_data);

		// Laravel API возвращает данные в формате {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response['checkout_url'])) {
			return $data_response;
		}

		throw new \Exception(__('Failed to create payment session', 'numerology-compatibility'));
	}

	/**
	 * Получить информацию о расчете
	 * GET /api/v1/calculations/{id}
	 *
	 * @param string $calculation_id ID расчета
	 * @return array Информация о расчете
	 * @throws \Exception
	 */
	public function get_calculation($calculation_id) {
		if (empty($calculation_id)) {
			throw new \Exception(__('Calculation ID is required', 'numerology-compatibility'));
		}

		$response = $this->client->request('/calculations/' . $calculation_id, 'GET');

		// Laravel API возвращает данные в формате {success, data}
		return $response['data'] ?? [];
	}

	/**
	 * Получить URL для скачивания PDF
	 * GET /api/v1/calculations/{id}/pdf
	 *
	 * @param string $calculation_id ID расчета
	 * @return string URL для скачивания PDF
	 */
	public function get_pdf_url($calculation_id) {
		if (empty($calculation_id)) {
			throw new \Exception(__('Calculation ID is required', 'numerology-compatibility'));
		}

		$api_url = get_option('nc_api_url', 'https://api.your-domain.com');
		return $api_url . '/api/v1/calculations/' . $calculation_id . '/pdf';
	}

	/**
	 * Валидация данных для расчета
	 * Email НЕ проверяется, т.к. он ВСЕГДА отсутствует на первом шаге
	 *
	 * @param array $data Данные формы (person1_date, person2_date)
	 * @throws \Exception
	 */
	private function validate_calculation_data($data) {
		// Валидация дат рождения
		if (empty($data['person1_date']) || empty($data['person2_date'])) {
			throw new \Exception(__('Both birth dates are required', 'numerology-compatibility'));
		}

		// Проверка формата дат
		$date1 = \DateTime::createFromFormat('Y-m-d', $data['person1_date']);
		$date2 = \DateTime::createFromFormat('Y-m-d', $data['person2_date']);

		if (!$date1 || !$date2) {
			throw new \Exception(__('Invalid date format. Required: Y-m-d', 'numerology-compatibility'));
		}

		// Проверка, что даты не в будущем
		$today = new \DateTime();
		if ($date1 > $today || $date2 > $today) {
			throw new \Exception(__('Birth dates cannot be in the future', 'numerology-compatibility'));
		}
	}

	/**
	 * Валидация тарифа
	 *
	 * @param string $tier
	 * @throws \Exception
	 */
	private function validate_tier($tier) {
		$allowed_tiers = ['standard', 'premium'];

		if (!in_array($tier, $allowed_tiers)) {
			throw new \Exception(
				sprintf(
					__('Invalid tier. Allowed: %s', 'numerology-compatibility'),
					implode(', ', $allowed_tiers)
				)
			);
		}
	}

	/**
	 * Получить текущую локаль для API
	 * Конвертирует WordPress локаль в формат API: en|ru|uk
	 *
	 * @return string
	 */
	private function get_current_locale() {
		$lang = substr(get_locale(), 0, 2);

		return $lang ?: 'en';
	}

	/**
	 * Отправить PDF отчет на email по секретному коду
	 * POST /api/v1/calculations/send-email
	 *
	 * НОВЫЙ ENDPOINT для отправки PDF на email после расчета
	 *
	 * @param string $secret_code Секретный код расчета
	 * @param string $email Email для отправки
	 * @return array Результат отправки
	 * @throws \Exception
	 */
	public function send_email($secret_code, $email) {
		// Валидация секретного кода
		if (empty($secret_code) || strlen($secret_code) !== 32) {
			throw new \Exception(__('Invalid secret code', 'numerology-compatibility'));
		}

		// Валидация email
		if (empty($email) || !is_email($email)) {
			throw new \Exception(__('Valid email is required', 'numerology-compatibility'));
		}

		// Подготавливаем данные
		$request_data = [
			'secret_code' => sanitize_text_field($secret_code),
			'email' => sanitize_email($email),
		];

		// Отправляем запрос на бэкенд
		$response = $this->client->request('/calculations/send-email', 'POST', $request_data);

		// Laravel API возвращает данные в формате {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response)) {
			return $data_response;
		}

		throw new \Exception(__('Failed to send email', 'numerology-compatibility'));
	}
}