<?php
// plugins/numerology-compatibility/api/class-api-client.php
namespace NC\Api;

class ApiClient {

	private $api_url;
	private $api_key;
	private $timeout = 30;

	public function __construct() {
		$this->api_url = get_option('nc_api_url', 'https://api.your-domain.com');
		$this->api_key = get_option('nc_api_key', '');
	}

	/**
	 * Make API request
	 */
	public function request($endpoint, $method = 'GET', $data = []) {
		$url = $this->api_url . '/api/v1' . $endpoint;

		$headers = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'X-API-Key' => $this->api_key,
			'X-Request-ID' => $this->generate_request_id(),
			'X-Client-Version' => NC_VERSION,
		];

		$args = [
			'method' => $method,
			'timeout' => $this->timeout,
			'headers' => $headers,
			'sslverify' => true,
		];

		if (!empty($data)) {
			if ($method === 'GET') {
				$url = add_query_arg($data, $url);
			} else {
				$args['body'] = json_encode($data);
			}
		}

		// Make request with retry logic
		$response = $this->request_with_retry($url, $args);

		if (is_wp_error($response)) {
			throw new \Exception('API request failed: ' . $response->get_error_message());
		}

		$status_code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		// Handle different status codes
		if ($status_code >= 200 && $status_code < 300) {
			return $data;
		}

		// Handle specific error codes
		switch ($status_code) {
			case 402:
				throw new \Exception(__('Payment required', 'numerology-compatibility'));

			case 429:
				$retry_after = wp_remote_retrieve_header($response, 'Retry-After') ?: 60;
				throw new \Exception(sprintf(
					__('Rate limit exceeded. Try again in %d seconds', 'numerology-compatibility'),
					$retry_after
				));

			case 422:
				$errors = $data['errors'] ?? [__('Validation failed', 'numerology-compatibility')];
				throw new \Exception(__('Validation error: ', 'numerology-compatibility') . json_encode($errors));

			default:
				$message = $data['message'] ?? __('Unknown error occurred', 'numerology-compatibility');
				throw new \Exception(__('API error: ', 'numerology-compatibility') . $message);
		}
	}

	/**
	 * Request with retry logic
	 */
	private function request_with_retry($url, $args, $attempts = 3) {
		$last_error = null;

		for ($i = 0; $i < $attempts; $i++) {
			if ($i > 0) {
				// Exponential backoff
				sleep(pow(2, $i));
			}

			$response = wp_remote_request($url, $args);

			if (!is_wp_error($response)) {
				return $response;
			}

			$last_error = $response;

			// Don't retry on certain errors
			if (in_array($response->get_error_code(), ['http_request_failed'])) {
				break;
			}
		}

		return $last_error;
	}

	/**
	 * Generate unique request ID for tracking
	 */
	private function generate_request_id() {
		return wp_generate_uuid4();
	}

	/**
	 * Test API connection
	 */
	public function test_connection() {
		try {
			$response = $this->request('/test', 'GET');
			return [
				'success' => true,
				'message' => $response['message'] ?? __('Connection successful', 'numerology-compatibility'),
				'version' => $response['version'] ?? 'Unknown'
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
	}
}