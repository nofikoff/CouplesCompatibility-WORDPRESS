<?php
namespace NC\Api;

class ApiClient {

    private $api_url;
    private $api_key;
    private $api_secret;
    private $timeout = 30;
    private $token = null;

    public function __construct() {
        $this->api_url = get_option('nc_api_url', 'https://api.your-domain.com');
        $this->api_key = get_option('nc_api_key', '');
        $this->api_secret = get_option('nc_api_secret', '');
    }

    /**
     * Make API request
     */
    public function request($endpoint, $method = 'GET', $data = [], $auth_required = false) {
        $url = $this->api_url . '/api/v1' . $endpoint;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->api_key,
            'X-Request-ID' => $this->generate_request_id(),
            'X-Client-Version' => NC_VERSION,
        ];

        // Add auth token if required
        if ($auth_required && $this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        // Sign request if secret is available
        if ($this->api_secret) {
            $timestamp = time();
            $signature = $this->sign_request($method, $endpoint, $timestamp);
            $headers['X-Timestamp'] = $timestamp;
            $headers['X-Signature'] = $signature;
        }

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
            case 401:
                // Try to refresh token if available
                if ($auth_required && $this->can_refresh_token()) {
                    $this->refresh_token();
                    return $this->request($endpoint, $method, $data, $auth_required);
                }
                throw new \Exception('Authentication failed');

            case 402:
                throw new \Exception('Payment required');

            case 429:
                $retry_after = wp_remote_retrieve_header($response, 'Retry-After') ?: 60;
                throw new \Exception('Rate limit exceeded. Try again in ' . $retry_after . ' seconds');

            case 422:
                $errors = $data['errors'] ?? ['Validation failed'];
                throw new \Exception('Validation error: ' . json_encode($errors));

            default:
                $message = $data['message'] ?? 'Unknown error occurred';
                throw new \Exception('API error: ' . $message);
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
     * Sign request for security
     */
    private function sign_request($method, $endpoint, $timestamp) {
        $message = $method . $endpoint . $timestamp;
        return hash_hmac('sha256', $message, $this->api_secret);
    }

    /**
     * Generate unique request ID for tracking
     */
    private function generate_request_id() {
        return wp_generate_uuid4();
    }

    /**
     * Set auth token
     */
    public function set_token($token) {
        $this->token = $token;
        // Store in transient for 1 hour
        set_transient('nc_api_token_' . get_current_user_id(), $token, HOUR_IN_SECONDS);
    }

    /**
     * Get stored token
     */
    public function get_token() {
        if (!$this->token) {
            $this->token = get_transient('nc_api_token_' . get_current_user_id());
        }
        return $this->token;
    }

    /**
     * Check if we can refresh token
     */
    private function can_refresh_token() {
        return !empty(get_user_meta(get_current_user_id(), 'nc_refresh_token', true));
    }

    /**
     * Refresh auth token
     */
    private function refresh_token() {
        $refresh_token = get_user_meta(get_current_user_id(), 'nc_refresh_token', true);

        if (empty($refresh_token)) {
            throw new \Exception('No refresh token available');
        }

        $response = $this->request('/auth/refresh', 'POST', [
            'refresh_token' => $refresh_token
        ]);

        if (!empty($response['access_token'])) {
            $this->set_token($response['access_token']);

            if (!empty($response['refresh_token'])) {
                update_user_meta(get_current_user_id(), 'nc_refresh_token', $response['refresh_token']);
            }
        }
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            $response = $this->request('/test', 'GET');
            return [
                'success' => true,
                'message' => $response['message'] ?? 'Connection successful',
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