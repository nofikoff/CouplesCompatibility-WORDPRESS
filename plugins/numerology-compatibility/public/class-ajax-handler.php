<?php
namespace NC\PublicSite;

use NC\Api\ApiAuth;
use NC\Api\ApiCalculations;
use NC\Api\ApiPayments;

class AjaxHandler {

    /**
     * Handle user registration
     */
    public function handle_registration() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            // Validate input
            $errors = [];

            if (empty($_POST['email'])) {
                $errors['email'] = __('Email is required', 'numerology-compatibility');
            } elseif (!is_email($_POST['email'])) {
                $errors['email'] = __('Invalid email address', 'numerology-compatibility');
            }

            if (empty($_POST['password'])) {
                $errors['password'] = __('Password is required', 'numerology-compatibility');
            } elseif (strlen($_POST['password']) < 8) {
                $errors['password'] = __('Password must be at least 8 characters', 'numerology-compatibility');
            }

            if ($_POST['password'] !== $_POST['password_confirmation']) {
                $errors['password_confirmation'] = __('Passwords do not match', 'numerology-compatibility');
            }

            // Check required consents
            if (empty($_POST['age_consent'])) {
                $errors['age_consent'] = __('You must confirm you are 18 or older', 'numerology-compatibility');
            }

            if (empty($_POST['terms_consent'])) {
                $errors['terms_consent'] = __('You must agree to the terms', 'numerology-compatibility');
            }

            if (!empty($errors)) {
                wp_send_json_error(['errors' => $errors]);
            }

            // Process registration
            $auth = new ApiAuth();
            $result = $auth->register($_POST);

            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle user login
     */
    public function handle_login() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            $email = sanitize_email($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = !empty($_POST['remember']);

            if (empty($email) || empty($password)) {
                wp_send_json_error(['message' => __('Email and password are required', 'numerology-compatibility')]);
            }

            $auth = new ApiAuth();
            $result = $auth->login($email, $password, $remember);

            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle Google authentication
     */
    public function handle_google_auth() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            $token = $_POST['token'] ?? '';

            if (empty($token)) {
                wp_send_json_error(['message' => __('Google token is required', 'numerology-compatibility')]);
            }

            $auth = new ApiAuth();
            $result = $auth->google_auth($token);

            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle calculation request
     */
    public function handle_calculation() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error([
                    'message' => __('Please sign in to continue', 'numerology-compatibility'),
                    'require_auth' => true
                ]);
            }

            // Validate dates
            $person1_date = $_POST['person1_date'] ?? '';
            $person2_date = $_POST['person2_date'] ?? '';

            if (empty($person1_date) || empty($person2_date)) {
                wp_send_json_error(['message' => __('Both birth dates are required', 'numerology-compatibility')]);
            }

            // Validate date format
            $date1 = \DateTime::createFromFormat('Y-m-d', $person1_date);
            $date2 = \DateTime::createFromFormat('Y-m-d', $person2_date);

            if (!$date1 || !$date2) {
                wp_send_json_error(['message' => __('Invalid date format', 'numerology-compatibility')]);
            }

            // Check dates are not in future
            $today = new \DateTime();
            if ($date1 > $today || $date2 > $today) {
                wp_send_json_error(['message' => __('Birth dates cannot be in the future', 'numerology-compatibility')]);
            }

            // Check consent
            if (empty($_POST['data_consent']) || empty($_POST['harm_consent']) || empty($_POST['entertainment_consent'])) {
                wp_send_json_error(['message' => __('All consent checkboxes must be accepted', 'numerology-compatibility')]);
            }

            $package_type = $_POST['package_type'] ?? 'free';

            // If paid package, create payment intent
            if ($package_type !== 'free') {
                $payments = new ApiPayments();
                $payment_result = $payments->create_payment_intent($package_type, $_POST);
                wp_send_json_success([
                    'require_payment' => true,
                    'payment_data' => $payment_result
                ]);
                return;
            }

            // Process free calculation
            $calc = new ApiCalculations();
            $result = $calc->calculate($_POST, $package_type);

            wp_send_json_success([
                'calculation' => $result,
                'redirect' => add_query_arg('calculation_id', $result['id'], home_url('/dashboard'))
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle payment creation
     */
    public function handle_payment() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => __('Please sign in to continue', 'numerology-compatibility')]);
            }

            $payment_intent_id = $_POST['payment_intent_id'] ?? '';

            if (empty($payment_intent_id)) {
                wp_send_json_error(['message' => __('Payment intent ID is required', 'numerology-compatibility')]);
            }

            $payments = new ApiPayments();
            $result = $payments->confirm_payment($payment_intent_id);

            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Get calculation history
     */
    public function get_calculation_history() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => __('Please sign in to continue', 'numerology-compatibility')]);
            }

            $page = intval($_POST['page'] ?? 1);
            $limit = intval($_POST['limit'] ?? 10);

            $calc = new ApiCalculations();
            $history = $calc->get_calculation_history($page, $limit);

            wp_send_json_success($history);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Delete calculation
     */
    public function delete_calculation() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => __('Please sign in to continue', 'numerology-compatibility')]);
            }

            $calculation_id = $_POST['calculation_id'] ?? '';

            if (empty($calculation_id)) {
                wp_send_json_error(['message' => __('Calculation ID is required', 'numerology-compatibility')]);
            }

            $calc = new ApiCalculations();
            $result = $calc->delete_calculation($calculation_id);

            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Download PDF report
     */
    public function download_pdf() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => __('Please sign in to continue', 'numerology-compatibility')]);
            }

            $calculation_id = $_GET['calculation_id'] ?? '';

            if (empty($calculation_id)) {
                wp_send_json_error(['message' => __('Calculation ID is required', 'numerology-compatibility')]);
            }

            $calc = new ApiCalculations();
            $pdf = $calc->download_pdf($calculation_id);

            // Send PDF headers
            header('Content-Type: ' . $pdf['mime_type']);
            header('Content-Disposition: attachment; filename="' . $pdf['filename'] . '"');
            header('Content-Length: ' . strlen($pdf['content']));

            // Output PDF content
            echo $pdf['content'];
            exit;

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle account deletion
     */
    public function handle_account_deletion() {
        try {
            // Verify nonce
            if (!check_ajax_referer('nc_ajax_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
            }

            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => __('Please sign in to continue', 'numerology-compatibility')]);
            }

            $confirmation = $_POST['confirmation'] ?? '';

            if ($confirmation !== 'DELETE') {
                wp_send_json_error(['message' => __('Please type DELETE to confirm', 'numerology-compatibility')]);
            }

            $auth = new ApiAuth();
            $result = $auth->delete_account(get_current_user_id(), $confirmation);

            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}