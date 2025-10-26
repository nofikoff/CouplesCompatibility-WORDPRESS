<?php
// plugins/numerology-compatibility/api/class-api-calculations.php
namespace NC\Api;

class ApiCalculations {

	private $client;

	public function __construct() {
		$this->client = new ApiClient();
	}

	/**
	 * Perform compatibility calculation
	 */
	public function calculate($data, $package_type = 'free') {
		// Validate email
		if (empty($data['email']) || !is_email($data['email'])) {
			throw new \Exception(__('Valid email is required', 'numerology-compatibility'));
		}

		// Validate consent for using other person's data
		if (empty($data['data_consent']) || empty($data['harm_consent'])) {
			throw new \Exception(__('Required consents for calculation not provided', 'numerology-compatibility'));
		}

		if (empty($data['entertainment_consent'])) {
			throw new \Exception(__('You must acknowledge this is for entertainment purposes', 'numerology-compatibility'));
		}

		// Prepare calculation data
		$request_data = [
			'email' => sanitize_email($data['email']),
			'person1_date' => sanitize_text_field($data['person1_date']),
			'person2_date' => sanitize_text_field($data['person2_date']),
			'person1_name' => sanitize_text_field($data['person1_name'] ?? ''),
			'person2_name' => sanitize_text_field($data['person2_name'] ?? ''),
			'person1_time' => sanitize_text_field($data['person1_time'] ?? ''),
			'person2_time' => sanitize_text_field($data['person2_time'] ?? ''),
			'person1_place' => sanitize_text_field($data['person1_place'] ?? ''),
			'person2_place' => sanitize_text_field($data['person2_place'] ?? ''),
			'package_type' => $package_type,
			'language' => 'en',
			'format' => 'pdf',
			'metadata' => [
				'source' => 'wordpress_plugin',
				'consent' => [
					'data_usage' => true,
					'no_harm' => true,
					'entertainment_only' => true
				],
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
				'timestamp' => current_time('mysql')
			]
		];

		// Make calculation request
		$response = $this->client->request('/compatibility/calculate', 'POST', $request_data);

		if (!empty($response['success']) && !empty($response['data'])) {
			// Store calculation in database
			$this->store_calculation($response['data'], $data['email']);

			// Track usage
			$this->track_usage($package_type, $data['email']);

			return $response['data'];
		}

		throw new \Exception(__('Calculation failed', 'numerology-compatibility'));
	}

	/**
	 * Get analysis levels information
	 */
	public function get_analysis_levels() {
		$response = $this->client->request('/compatibility/levels', 'GET');
		return $response['levels'] ?? [];
	}

	/**
	 * Store calculation in local database
	 */
	private function store_calculation($calculation, $email) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nc_calculations';

		$wpdb->insert(
			$table_name,
			[
				'email' => $email,
				'calculation_id' => $calculation['id'],
				'package_type' => $calculation['package_type'],
				'person1_date' => $calculation['person1_date'],
				'person2_date' => $calculation['person2_date'],
				'person1_name' => $calculation['person1_name'] ?? '',
				'person2_name' => $calculation['person2_name'] ?? '',
				'result_summary' => json_encode($calculation['summary'] ?? []),
				'pdf_sent' => 0,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
		);
	}

	/**
	 * Track usage for analytics
	 */
	private function track_usage($package_type, $email) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'nc_analytics';

		$wpdb->insert(
			$table_name,
			[
				'email' => $email,
				'event_type' => 'calculation_completed',
				'event_data' => json_encode(['package_type' => $package_type]),
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%s']
		);
	}
}