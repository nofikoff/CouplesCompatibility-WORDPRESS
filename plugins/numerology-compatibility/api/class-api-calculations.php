<?php
// plugins/numerology-compatibility/api/class-api-calculations.php
namespace NC\Api;

/**
 * Numerology API calculations class
 * Interacts with backend according to OpenAPI specification
 */
class ApiCalculations {

	private $client;

	public function __construct() {
		$this->client = new ApiClient();
	}

	/**
	 * Free calculation
	 * POST /api/v1/calculate/free
	 *
	 * - Email is NOT required at this step (requested after calculation)
	 * - Backend returns secret_code for calculation access
	 * - Backend returns pdf_url for PDF download
	 *
	 * @param array $data Form data (person1_date, person2_date)
	 * @return array Calculation result with secret_code and pdf_url
	 * @throws \Exception
	 */
	public function calculate_free($data) {
		$this->validate_calculation_data($data);

		$locale = $this->get_current_locale();

		// Prepare data according to API specification
		$request_data = [
			'person1_date' => sanitize_text_field($data['person1_date']),
			'person2_date' => sanitize_text_field($data['person2_date']),
			'locale' => $locale,
		];

		// Send request to backend
		$response = $this->client->request('/calculate/free', 'POST', $request_data);

		// Laravel API returns data in format {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response)) {
			return $data_response;
		}

		throw new \Exception(__('Calculation failed', 'numerology-compatibility'));
	}

	/**
	 * Paid calculation - create Checkout Session
	 * POST /api/v1/calculate/paid
	 *
	 * - Email is NOT required at this step (requested AFTER payment)
	 * - Backend returns secret_code for calculation access
	 * - After payment user can provide email for PDF + receipt delivery
	 *
	 * @param array $data Form data (person1_date, person2_date)
	 * @param string $tier Tier type (standard|premium)
	 * @return array {checkout_url, calculation_id, secret_code} for payment redirect
	 * @throws \Exception
	 */
	public function calculate_paid($data, $tier) {
		$this->validate_calculation_data($data);
		$this->validate_tier($tier);

		$locale = $this->get_current_locale();

		// Get result page URL with locale support
		$result_page_url = $this->get_localized_result_url($locale);

		// Backend will add payment_id and calculation_id to success_url automatically
		$success_url = $result_page_url;
		$cancel_url = $result_page_url;

		$request_data = [
			'person1_date' => sanitize_text_field($data['person1_date']),
			'person2_date' => sanitize_text_field($data['person2_date']),
			'tier' => $tier,
			'locale' => $locale,
			'success_url' => $success_url,
			'cancel_url' => $cancel_url,
		];

		// Send request to create Checkout Session
		$response = $this->client->request('/calculate/paid', 'POST', $request_data);

		// Laravel API returns data in format {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response['checkout_url'])) {
			return $data_response;
		}

		throw new \Exception(__('Failed to create payment session', 'numerology-compatibility'));
	}

	/**
	 * Get calculation information
	 * GET /api/v1/calculations/{id}
	 *
	 * @param string $calculation_id Calculation ID
	 * @return array Calculation information
	 * @throws \Exception
	 */
	public function get_calculation($calculation_id) {
		if (empty($calculation_id)) {
			throw new \Exception(__('Calculation ID is required', 'numerology-compatibility'));
		}

		$response = $this->client->request('/calculations/' . $calculation_id, 'GET');

		// Laravel API returns data in format {success, data}
		return $response['data'] ?? [];
	}

	/**
	 * Get PDF download URL
	 * GET /api/v1/calculations/{id}/pdf
	 *
	 * @param string $calculation_id Calculation ID
	 * @return string PDF download URL
	 */
	public function get_pdf_url($calculation_id) {
		if (empty($calculation_id)) {
			throw new \Exception(__('Calculation ID is required', 'numerology-compatibility'));
		}

		$api_url = get_option('nc_api_url', 'https://api.your-domain.com');
		return $api_url . '/api/v1/calculations/' . $calculation_id . '/pdf';
	}

	/**
	 * Validate calculation data
	 * Email is NOT validated as it's ALWAYS absent on first step
	 *
	 * @param array $data Form data (person1_date, person2_date)
	 * @throws \Exception
	 */
	private function validate_calculation_data($data) {
		// Validate birth dates
		if (empty($data['person1_date']) || empty($data['person2_date'])) {
			throw new \Exception(__('Both birth dates are required', 'numerology-compatibility'));
		}

		// Check date format
		$date1 = \DateTime::createFromFormat('Y-m-d', $data['person1_date']);
		$date2 = \DateTime::createFromFormat('Y-m-d', $data['person2_date']);

		if (!$date1 || !$date2) {
			throw new \Exception(__('Invalid date format. Required: Y-m-d', 'numerology-compatibility'));
		}

		// Check that dates are not in the future
		$today = new \DateTime();
		if ($date1 > $today || $date2 > $today) {
			throw new \Exception(__('Birth dates cannot be in the future', 'numerology-compatibility'));
		}
	}

	/**
	 * Validate tier
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
	 * Get localized result page URL
	 * Supports Polylang multilingual sites
	 *
	 * @param string $locale Language code (en, ru, uk)
	 * @return string Localized URL
	 */
	private function get_localized_result_url($locale) {
		$base_url = get_option('nc_result_page_url', '');

		// If URL is not set, use referer or home
		if (empty($base_url)) {
			$referer = wp_get_referer();
			if ($referer) {
				return strtok($referer, '?');
			}
			return home_url('/');
		}

		// Polylang: get localized page version
		if (function_exists('pll_get_post')) {
			$post_id = url_to_postid($base_url);
			if ($post_id) {
				$translated_id = pll_get_post($post_id, $locale);
				if ($translated_id) {
					return get_permalink($translated_id);
				}
			}
		}

		return $base_url;
	}

	/**
	 * Get calculation information by secret code
	 * GET /api/v1/calculations/by-code/{secret_code}
	 *
	 * @param string $secret_code Secret code (32 characters)
	 * @return array Calculation info with pdf_url
	 * @throws \Exception
	 */
	public function get_calculation_by_code($secret_code) {
		// Validate secret code
		if (empty($secret_code) || strlen($secret_code) !== 32) {
			throw new \Exception(__('Invalid secret code', 'numerology-compatibility'));
		}

		$response = $this->client->request('/calculations/by-code/' . $secret_code, 'GET');

		// Laravel API returns data in format {success, data}
		return $response['data'] ?? [];
	}

	/**
	 * Send PDF report to email by secret code
	 * POST /api/v1/calculations/send-email
	 *
	 * @param string $secret_code Secret code
	 * @param string $email Email address
	 * @return array Send result
	 * @throws \Exception
	 */
	public function send_email($secret_code, $email) {
		// Validate secret code
		if (empty($secret_code) || strlen($secret_code) !== 32) {
			throw new \Exception(__('Invalid secret code', 'numerology-compatibility'));
		}

		// Validate email
		if (empty($email) || !is_email($email)) {
			throw new \Exception(__('Valid email is required', 'numerology-compatibility'));
		}

		// Prepare data
		$request_data = [
			'secret_code' => sanitize_text_field($secret_code),
			'email' => sanitize_email($email),
		];

		// Send request to backend
		$response = $this->client->request('/calculations/send-email', 'POST', $request_data);

		// Laravel API returns data in format {success, message, data}
		$data_response = $response['data'] ?? [];

		if (!empty($data_response)) {
			return $data_response;
		}

		throw new \Exception(__('Failed to send email', 'numerology-compatibility'));
	}
}