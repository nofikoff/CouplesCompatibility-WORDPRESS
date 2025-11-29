<?php
namespace NC\Admin;

use NC\Api\ApiClient;

class Settings {

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General settings
        register_setting('nc_settings_general', 'nc_result_page_url');
        register_setting('nc_settings_general', 'nc_price_standard');
        register_setting('nc_settings_general', 'nc_price_premium');

        // API settings
        register_setting('nc_settings_api', 'nc_api_url');
        register_setting('nc_settings_api', 'nc_api_key');

        // Advanced settings
        register_setting('nc_settings_advanced', 'nc_delete_on_uninstall');
    }

    /**
     * Test API connection
     */
    public function test_api_connection() {
        if (!check_ajax_referer('nc_test_api', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'numerology-compatibility')]);
        }

        $client = new ApiClient();
        $result = $client->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Validate settings before save
     */
    public function validate_settings($input) {
        $validated = [];

        // Validate API URL
        if (isset($input['nc_api_url'])) {
            $validated['nc_api_url'] = esc_url_raw($input['nc_api_url']);
        }

        // Validate other fields
        foreach ($input as $key => $value) {
            if (!isset($validated[$key])) {
                $validated[$key] = sanitize_text_field($value);
            }
        }

        return $validated;
    }

    /**
     * Get default settings
     */
    public static function get_defaults() {
        return [
            // General settings
            'nc_result_page_url' => '',
            'nc_price_standard' => '9.99',
            'nc_price_premium' => '19.99',

            // API settings
            'nc_api_url' => 'https://api.your-domain.com',

            // Advanced settings
            'nc_delete_on_uninstall' => 0
        ];
    }
}