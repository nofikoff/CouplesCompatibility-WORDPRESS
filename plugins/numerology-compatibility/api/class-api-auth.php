<?php
namespace NC\Api;

class ApiAuth {

    private $client;

    public function __construct() {
        $this->client = new ApiClient();
    }

    /**
     * Register new user
     */
    public function register($data) {
        // Validate required consents
        if (empty($data['age_consent']) || empty($data['terms_consent'])) {
            throw new \Exception(__('Required consents not provided', 'numerology-compatibility'));
        }

        $request_data = [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'email' => sanitize_email($data['email']),
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
            'age_consent' => (bool)$data['age_consent'],
            'terms_consent' => (bool)$data['terms_consent'],
            'marketing_consent' => (bool)($data['marketing_consent'] ?? false),
            'language' => get_locale(),
            'timezone' => wp_timezone_string(),
            'source' => 'wordpress_plugin',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        $response = $this->client->request('/auth/register', 'POST', $request_data);

        if (!empty($response['user']) && !empty($response['access_token'])) {
            // Create or update WordPress user
            $wp_user_id = $this->create_wp_user($response['user']);

            // Store tokens
            $this->store_tokens($wp_user_id, $response);

            // Store consent records
            $this->store_consents($wp_user_id, $data);

            // Auto-login
            wp_set_auth_cookie($wp_user_id);

            return [
                'success' => true,
                'user' => $response['user'],
                'redirect' => get_option('nc_redirect_after_register', home_url('/dashboard'))
            ];
        }

        throw new \Exception(__('Registration failed', 'numerology-compatibility'));
    }

    /**
     * Login user
     */
    public function login($email, $password, $remember = false) {
        $response = $this->client->request('/auth/login', 'POST', [
            'email' => sanitize_email($email),
            'password' => $password,
            'remember' => $remember
        ]);

        if (!empty($response['user']) && !empty($response['access_token'])) {
            // Create or update WordPress user
            $wp_user_id = $this->create_wp_user($response['user']);

            // Store tokens
            $this->store_tokens($wp_user_id, $response);

            // WordPress login
            wp_set_auth_cookie($wp_user_id, $remember);

            return [
                'success' => true,
                'user' => $response['user'],
                'redirect' => get_option('nc_redirect_after_login', home_url('/dashboard'))
            ];
        }

        throw new \Exception(__('Invalid credentials', 'numerology-compatibility'));
    }

    /**
     * Google OAuth login
     */
    public function google_auth($token) {
        $response = $this->client->request('/auth/google/token', 'POST', [
            'token' => $token,
            'source' => 'wordpress_plugin'
        ]);

        if (!empty($response['user']) && !empty($response['access_token'])) {
            // Create or update WordPress user
            $wp_user_id = $this->create_wp_user($response['user']);

            // Store tokens
            $this->store_tokens($wp_user_id, $response);

            // Store Google connection
            update_user_meta($wp_user_id, 'nc_google_connected', true);

            // WordPress login
            wp_set_auth_cookie($wp_user_id);

            return [
                'success' => true,
                'user' => $response['user'],
                'redirect' => get_option('nc_redirect_after_login', home_url('/dashboard'))
            ];
        }

        throw new \Exception(__('Google authentication failed', 'numerology-compatibility'));
    }

    /**
     * Logout user
     */
    public function logout() {
        $user_id = get_current_user_id();

        if ($user_id) {
            // Try to logout from API
            try {
                $this->client->set_token($this->client->get_token());
                $this->client->request('/auth/logout', 'POST', [], true);
            } catch (\Exception $e) {
                // Ignore API errors on logout
            }

            // Clear tokens
            delete_transient('nc_api_token_' . $user_id);
            delete_user_meta($user_id, 'nc_refresh_token');
            delete_user_meta($user_id, 'nc_api_user_id');

            // WordPress logout
            wp_logout();
        }

        return ['success' => true];
    }

    /**
     * Delete user account
     */
    public function delete_account($user_id, $confirmation_text) {
        // Verify confirmation
        if ($confirmation_text !== 'DELETE') {
            throw new \Exception(__('Invalid confirmation', 'numerology-compatibility'));
        }

        // Get user email for verification
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            throw new \Exception(__('User not found', 'numerology-compatibility'));
        }

        // Delete from API
        $this->client->set_token($this->client->get_token());
        $response = $this->client->request('/auth/delete-account', 'DELETE', [
            'email' => $user->user_email,
            'confirmation' => $confirmation_text
        ], true);

        if (!empty($response['success'])) {
            // Schedule WordPress user deletion (30 days grace period)
            wp_schedule_single_event(
                time() + (30 * DAY_IN_SECONDS),
                'nc_delete_user_permanently',
                [$user_id]
            );

            // Mark account for deletion
            update_user_meta($user_id, 'nc_account_deletion_requested', time());

            // Logout user
            $this->logout();

            return [
                'success' => true,
                'message' => __('Account deletion scheduled. You have 30 days to cancel this request.', 'numerology-compatibility')
            ];
        }

        throw new \Exception(__('Account deletion failed', 'numerology-compatibility'));
    }

    /**
     * Create or update WordPress user
     */
    private function create_wp_user($api_user) {
        $email = sanitize_email($api_user['email']);
        $user = get_user_by('email', $email);

        if ($user) {
            // Update existing user
            wp_update_user([
                'ID' => $user->ID,
                'display_name' => sanitize_text_field($api_user['name'] ?? '')
            ]);
            $wp_user_id = $user->ID;
        } else {
            // Create new user
            $wp_user_id = wp_create_user(
                $email,
                wp_generate_password(),
                $email
            );

            if (is_wp_error($wp_user_id)) {
                throw new \Exception($wp_user_id->get_error_message());
            }

            wp_update_user([
                'ID' => $wp_user_id,
                'display_name' => sanitize_text_field($api_user['name'] ?? ''),
                'first_name' => sanitize_text_field($api_user['first_name'] ?? ''),
                'last_name' => sanitize_text_field($api_user['last_name'] ?? '')
            ]);
        }

        // Store API user ID
        update_user_meta($wp_user_id, 'nc_api_user_id', $api_user['id']);

        // Store subscription info if available
        if (!empty($api_user['subscription'])) {
            update_user_meta($wp_user_id, 'nc_subscription_type', $api_user['subscription']['type'] ?? 'free');
            update_user_meta($wp_user_id, 'nc_subscription_status', $api_user['subscription']['status'] ?? 'inactive');
        }

        return $wp_user_id;
    }

    /**
     * Store auth tokens
     */
    private function store_tokens($user_id, $response) {
        // Store access token in transient (1 hour)
        if (!empty($response['access_token'])) {
            set_transient(
                'nc_api_token_' . $user_id,
                $response['access_token'],
                HOUR_IN_SECONDS
            );
        }

        // Store refresh token in user meta
        if (!empty($response['refresh_token'])) {
            update_user_meta($user_id, 'nc_refresh_token', $response['refresh_token']);
        }
    }

    /**
     * Store consent records
     */
    private function store_consents($user_id, $data) {
        $consents = [
            'age_consent' => [
                'value' => (bool)$data['age_consent'],
                'timestamp' => current_time('mysql'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            'terms_consent' => [
                'value' => (bool)$data['terms_consent'],
                'timestamp' => current_time('mysql'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            'marketing_consent' => [
                'value' => (bool)($data['marketing_consent'] ?? false),
                'timestamp' => current_time('mysql'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]
        ];

        update_user_meta($user_id, 'nc_consents', $consents);
        update_user_meta($user_id, 'nc_consents_version', '1.0');
    }
}