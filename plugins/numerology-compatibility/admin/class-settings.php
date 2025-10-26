<?php
namespace NC\Admin;

use NC\Api\ApiClient;

class Settings {

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General settings
        register_setting('nc_settings_general', 'nc_environment');
        register_setting('nc_settings_general', 'nc_terms_url');
        register_setting('nc_settings_general', 'nc_privacy_url');

        // API settings
        register_setting('nc_settings_api', 'nc_api_url');
        register_setting('nc_settings_api', 'nc_api_key');
        register_setting('nc_settings_api', 'nc_api_secret');
        register_setting('nc_settings_api', 'nc_webhook_secret'); // Webhook secret для проверки подписи

        // ПРИМЕЧАНИЕ: Pricing и Payment Gateway настройки убраны
        // Все цены и платежные шлюзы управляются на бэкенде Laravel

        // Localization settings
        register_setting('nc_settings_localization', 'nc_default_language');
        register_setting('nc_settings_localization', 'nc_multilanguage');
        register_setting('nc_settings_localization', 'nc_auto_currency');
        register_setting('nc_settings_localization', 'nc_date_format');

        // Advanced settings
        register_setting('nc_settings_advanced', 'nc_debug_mode');
        register_setting('nc_settings_advanced', 'nc_cache_duration');
        register_setting('nc_settings_advanced', 'nc_rate_limit');
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
            'nc_environment' => 'production',
            'nc_terms_url' => '/terms',
            'nc_privacy_url' => '/privacy',

            // API settings
            'nc_api_url' => 'https://api.your-domain.com',

            // Localization settings
            'nc_default_language' => get_locale(),
            'nc_multilanguage' => 1,
            'nc_auto_currency' => 1,
            'nc_date_format' => 'Y-m-d',

            // Advanced settings
            'nc_debug_mode' => 0,
            'nc_cache_duration' => 3600,
            'nc_rate_limit' => 10,
            'nc_delete_on_uninstall' => 0
        ];
    }
}