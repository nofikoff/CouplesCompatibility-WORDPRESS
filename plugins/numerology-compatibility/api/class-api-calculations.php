<?php
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
        // Validate consent for using other person's data
        if (empty($data['data_consent']) || empty($data['harm_consent'])) {
            throw new \Exception(__('Required consents for calculation not provided', 'numerology-compatibility'));
        }

        if (empty($data['entertainment_consent'])) {
            throw new \Exception(__('You must acknowledge this is for entertainment purposes', 'numerology-compatibility'));
        }

        $this->client->set_token($this->client->get_token());

        // Prepare calculation data
        $request_data = [
            'person1_date' => sanitize_text_field($data['person1_date']),
            'person2_date' => sanitize_text_field($data['person2_date']),
            'person1_name' => sanitize_text_field($data['person1_name'] ?? ''),
            'person2_name' => sanitize_text_field($data['person2_name'] ?? ''),
            'person1_time' => sanitize_text_field($data['person1_time'] ?? ''),
            'person2_time' => sanitize_text_field($data['person2_time'] ?? ''),
            'person1_place' => sanitize_text_field($data['person1_place'] ?? ''),
            'person2_place' => sanitize_text_field($data['person2_place'] ?? ''),
            'package_type' => $package_type,
//            'language' => get_locale(),
            'language' => 'en', // TODO replace
            'format' => $data['format'] ?? 'json',
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
        $response = $this->client->request('/compatibility/calculate', 'POST', $request_data, true);

        if (!$response['success'] || !empty($response['data'])) {
            // Store calculation in database
            $this->store_calculation($response['data']);

            // Track usage
            $this->track_usage($package_type);

            return $response['data'];
        }

        throw new \Exception(__('Calculation failed', 'numerology-compatibility'));
    }

    /**
     * Quick calculation for free users
     */
    public function quick_calculate($data) {
        // For free/anonymous calculations
        $request_data = [
            'person1_date' => sanitize_text_field($data['person1_date']),
            'person2_date' => sanitize_text_field($data['person2_date']),
            'language' => get_locale()
        ];

        // Use anonymous API key for quick calculations
        $response = $this->client->request('/compatibility/quick', 'POST', $request_data);

        return $response;
    }

    /**
     * Get calculation history
     */
    public function get_calculation_history($page = 1, $limit = 10) {
        $this->client->set_token($this->client->get_token());

        $response = $this->client->request('/compatibility/history', 'GET', [
            'page' => $page,
            'limit' => $limit
        ], true);

        return $response;
    }

    /**
     * Get specific calculation
     */
    public function get_calculation($calculation_id) {
        $this->client->set_token($this->client->get_token());

        $response = $this->client->request('/compatibility/history/' . $calculation_id, 'GET', [], true);

        return $response;
    }

    /**
     * Delete calculation
     */
    public function delete_calculation($calculation_id) {
        $this->client->set_token($this->client->get_token());

        $response = $this->client->request('/compatibility/history/' . $calculation_id, 'DELETE', [], true);

        if (!empty($response['success'])) {
            // Also delete from local database
            $this->delete_local_calculation($calculation_id);
        }

        return $response;
    }

    /**
     * Download PDF report
     */
    public function download_pdf($calculation_id) {
        $this->client->set_token($this->client->get_token());

        // Get PDF URL from API
        $response = $this->client->request('/compatibility/history/' . $calculation_id . '/pdf', 'GET', [], true);

        if (!empty($response['pdf_url'])) {
            // Download PDF
            $pdf_response = wp_remote_get($response['pdf_url'], [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->client->get_token()
                ]
            ]);

            if (!is_wp_error($pdf_response)) {
                $pdf_content = wp_remote_retrieve_body($pdf_response);

                return [
                    'content' => $pdf_content,
                    'filename' => $response['filename'] ?? 'numerology-report.pdf',
                    'mime_type' => 'application/pdf'
                ];
            }
        }

        throw new \Exception(__('Failed to download PDF', 'numerology-compatibility'));
    }

    /**
     * Store calculation in local database
     */
    private function store_calculation($calculation) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nc_calculations';

        $wpdb->insert(
            $table_name,
            [
                'user_id' => get_current_user_id(),
                'calculation_id' => $calculation['id'],
                'package_type' => $calculation['package_type'],
                'person1_date' => $calculation['person1_date'],
                'person2_date' => $calculation['person2_date'],
                'person1_name' => $calculation['person1_name'] ?? '',
                'person2_name' => $calculation['person2_name'] ?? '',
                'result_summary' => json_encode($calculation['summary'] ?? []),
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Delete local calculation
     */
    private function delete_local_calculation($calculation_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nc_calculations';

        $wpdb->delete(
            $table_name,
            ['calculation_id' => $calculation_id],
            ['%s']
        );
    }

    /**
     * Track usage for analytics
     */
    private function track_usage($package_type) {
        $user_id = get_current_user_id();

        // Update user's calculation count
        $count = (int) get_user_meta($user_id, 'nc_calculation_count', true);
        update_user_meta($user_id, 'nc_calculation_count', $count + 1);

        // Update last calculation date
        update_user_meta($user_id, 'nc_last_calculation', current_time('mysql'));

        // Track package usage
        $package_stats = get_user_meta($user_id, 'nc_package_usage', true) ?: [];
        $package_stats[$package_type] = ($package_stats[$package_type] ?? 0) + 1;
        update_user_meta($user_id, 'nc_package_usage', $package_stats);
    }

    /**
     * Get analysis levels information
     */
    public function get_analysis_levels() {
        $response = $this->client->request('/compatibility/levels', 'GET');

        return $response['levels'] ?? [];
    }

    /**
     * Get usage statistics
     */
    public function get_usage_stats() {
        $this->client->set_token($this->client->get_token());

        $response = $this->client->request('/compatibility/stats', 'GET', [], true);

        return $response;
    }
}