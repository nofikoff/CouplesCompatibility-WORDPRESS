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
        register_setting('nc_settings_general', 'nc_require_auth');
        register_setting('nc_settings_general', 'nc_email_verification');
        register_setting('nc_settings_general', 'nc_terms_url');
        register_setting('nc_settings_general', 'nc_privacy_url');

        // API settings
        register_setting('nc_settings_api', 'nc_api_url');
        register_setting('nc_settings_api', 'nc_api_key');
        register_setting('nc_settings_api', 'nc_api_secret');
        register_setting('nc_settings_api', 'nc_webhook_secret'); // Webhook secret для проверки подписи

        // Pricing settings
        register_setting('nc_settings_pricing', 'nc_currency');
        register_setting('nc_settings_pricing', 'nc_free_enabled');
        register_setting('nc_settings_pricing', 'nc_price_light');
        register_setting('nc_settings_pricing', 'nc_price_pro');

        // Payment settings
        register_setting('nc_settings_payment', 'nc_stripe_mode');
        register_setting('nc_settings_payment', 'nc_stripe_webhook_secret');
        register_setting('nc_settings_payment', 'nc_stripe_publishable_key');

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

        // Validate prices
        if (isset($input['nc_price_light'])) {
            $validated['nc_price_light'] = floatval($input['nc_price_light']);
        }

        if (isset($input['nc_price_pro'])) {
            $validated['nc_price_pro'] = floatval($input['nc_price_pro']);
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
            'nc_environment' => 'production',
            'nc_require_auth' => 1,
            'nc_email_verification' => 0,
            'nc_terms_url' => '/terms',
            'nc_privacy_url' => '/privacy',
            'nc_api_url' => 'https://api.your-domain.com',
            'nc_currency' => 'USD',
            'nc_free_enabled' => 1,
            'nc_price_light' => 19,
            'nc_price_pro' => 49,
            'nc_stripe_mode' => 'test',
            'nc_default_language' => get_locale(),
            'nc_multilanguage' => 1,
            'nc_auto_currency' => 1,
            'nc_date_format' => 'Y-m-d',
            'nc_debug_mode' => 0,
            'nc_cache_duration' => 3600,
            'nc_rate_limit' => 10,
            'nc_delete_on_uninstall' => 0
        ];
    }
}