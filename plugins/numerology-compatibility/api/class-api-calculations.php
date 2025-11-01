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
	 * @param array $data Данные формы (email, person1_date, person2_date)
	 * @return array Результат расчета
	 * @throws \Exception
	 */
	public function calculate_free($data) {
		$this->validate_calculation_data($data);

		$locale = $this->get_current_locale();

		// Подготавливаем данные согласно API спецификации
		$request_data = [
			'email' => sanitize_email($data['email']),
			'person1_date' => sanitize_text_field($data['person1_date']),
			'person2_date' => sanitize_text_field($data['person2_date']),
			'locale' => $locale,
		];

		// Отправляем запрос на бэкенд
		$response = $this->client->request('/calculate/free', 'POST', $request_data);

		// Laravel API возвращает данные в формате {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response)) {
			// Сохраняем расчет локально
			$this->store_calculation($data_response, $data['email'], 'free');

			// Трекаем использование
			$this->track_usage('free', $data['email']);

			return $data_response;
		}

		throw new \Exception(__('Calculation failed', 'numerology-compatibility'));
	}

	/**
	 * Платный расчет - создание Checkout Session
	 * POST /api/v1/calculate/paid
	 *
	 * @param array $data Данные формы
	 * @param string $tier Тип тарифа (standard|premium)
	 * @return array {checkout_url, calculation_id} для редиректа на оплату
	 * @throws \Exception
	 */
	public function calculate_paid($data, $tier) {
		$this->validate_calculation_data($data);
		$this->validate_tier($tier);

		$locale = $this->get_current_locale();

		// Формируем success и cancel URLs для редиректа после оплаты
		// Laravel сам добавит payment_success, payment_id и calculation_id
		$current_url = $this->get_current_page_url();
		$success_url = $current_url; // Laravel добавит параметры
		$cancel_url = add_query_arg(['payment_cancelled' => '1'], $current_url);

		// Подготавливаем данные согласно API спецификации
		$request_data = [
			'email' => sanitize_email($data['email']),
			'person1_date' => sanitize_text_field($data['person1_date']),
			'person2_date' => sanitize_text_field($data['person2_date']),
			'tier' => $tier,
			'locale' => $locale,
			'success_url' => $success_url,
			'cancel_url' => $cancel_url,
		];

		// Отправляем запрос на создание Checkout Session
		$response = $this->client->request('/calculate/paid', 'POST', $request_data);

		// Laravel API возвращает данные в формате {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response['checkout_url'])) {
			// Сохраняем информацию о начале платного расчета
			$this->track_usage('paid_initiated', $data['email'], [
				'tier' => $tier,
				'calculation_id' => $data_response['calculation_id'] ?? null
			]);

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
	 *
	 * @param array $data
	 * @throws \Exception
	 */
	private function validate_calculation_data($data) {
		// Валидация email
		if (empty($data['email']) || !is_email($data['email'])) {
			throw new \Exception(__('Valid email is required', 'numerology-compatibility'));
		}

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
	 *
	 * @return string
	 */
	private function get_current_locale() {
		$wp_locale = get_locale();

		// Конвертируем WordPress локаль в формат API (en|ru)
		if (strpos($wp_locale, 'ru') === 0) {
			return 'ru';
		}

		return 'en';
	}

	/**
	 * Получить URL текущей страницы
	 *
	 * @return string
	 */
	private function get_current_page_url() {
		// Используем реальный URL из HTTP запроса вместо home_url()
		// Это позволяет избежать проблем с неправильными настройками WordPress
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];
		$request_uri = $_SERVER['REQUEST_URI'];

		// Убираем query string если есть
		$uri = strtok($request_uri, '?');

		// Формируем базовый URL без параметров
		$current_url = $protocol . '://' . $host . $uri;

		return $current_url;
	}

	/**
	 * Сохранить расчет в локальной БД
	 *
	 * @param array $calculation Данные расчета от API
	 * @param string $email Email пользователя
	 * @param string $tier Тип расчета
	 */
	private function store_calculation($calculation, $email, $tier) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nc_calculations';

		$wpdb->insert(
			$table_name,
			[
				'email' => $email,
				'calculation_id' => $calculation['id'] ?? null,
				'package_type' => $tier,
				'person1_date' => $calculation['person1_date'] ?? null,
				'person2_date' => $calculation['person2_date'] ?? null,
				'person1_name' => '',
				'person2_name' => '',
				'result_summary' => json_encode($calculation),
				'pdf_sent' => 1,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
		);
	}

	/**
	 * Трекинг использования для аналитики
	 *
	 * @param string $event_type Тип события
	 * @param string $email Email пользователя
	 * @param array $extra_data Дополнительные данные
	 */
	private function track_usage($event_type, $email, $extra_data = []) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nc_analytics';

		$wpdb->insert(
			$table_name,
			[
				'email' => $email,
				'event_type' => $event_type,
				'event_data' => json_encode($extra_data),
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%s']
		);
	}
}