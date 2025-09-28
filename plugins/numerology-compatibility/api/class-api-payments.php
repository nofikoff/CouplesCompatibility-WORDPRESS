<?php
namespace NC\Api;

class ApiPayments
{

    private $client;

    public function __construct()
    {
        $this->client = new ApiClient();
    }

    /**
     * Get available plans
     */
    public function get_plans()
    {
        $response = $this->client->request('/payment/plans', 'GET');

        // Add localized prices if configured
        if (!empty($response['plans'])) {
            $currency = get_option('nc_currency', 'USD');
            foreach ($response['plans'] as &$plan) {
                $plan['display_price'] = $this->format_price($plan['price'], $currency);
                $plan['currency'] = $currency;
            }
        }

        return $response['plans'] ?? [];
    }

    /**
     * Create payment intent for one-time calculation
     */
    public function create_payment_intent($package_type, $calculation_data)
    {
        $this->client->set_token($this->client->get_token());

        // Get package price from settings
        $price = $this->get_package_price($package_type);

        if ($price <= 0 && $package_type !== 'free') {
            throw new \Exception(__('Invalid package type', 'numerology-compatibility'));
        }

        $response = $this->client->request('/payment/intent', 'POST', [
            'amount' => $price * 100, // Convert to cents
            'currency' => strtolower(get_option('nc_currency', 'USD')),
            'package_type' => $package_type,
            'metadata' => [
                'calculation_data' => json_encode($calculation_data),
                'user_id' => get_current_user_id(),
                'site_url' => home_url()
            ],
            'description' => sprintf(
                __('Numerology %s Report', 'numerology-compatibility'),
                ucfirst($package_type)
            )
        ], true);

        if (!empty($response['client_secret'])) {
            // Store payment intent for later verification
            set_transient(
                'nc_payment_intent_' . get_current_user_id(),
                [
                    'intent_id' => $response['id'],
                    'package_type' => $package_type,
                    'calculation_data' => $calculation_data
                ],
                HOUR_IN_SECONDS
            );

            return [
                'success' => true,
                'client_secret' => $response['client_secret'],
                'publishable_key' => get_option('nc_stripe_publishable_key'),
                'amount' => $price,
                'currency' => get_option('nc_currency', 'USD')
            ];
        }

        throw new \Exception(__('Failed to create payment intent', 'numerology-compatibility'));
    }

    /**
     * Confirm payment and process calculation
     */
    public function confirm_payment($payment_intent_id)
    {
        $this->client->set_token($this->client->get_token());

        // Get stored payment data
        $payment_data = get_transient('nc_payment_intent_' . get_current_user_id());

        if (empty($payment_data) || $payment_data['intent_id'] !== $payment_intent_id) {
            throw new \Exception(__('Invalid payment session', 'numerology-compatibility'));
        }

        // Confirm with API
        $response = $this->client->request('/payment/confirm', 'POST', [
            'payment_intent_id' => $payment_intent_id
        ], true);

        if (!empty($response['success'])) {
            // Clear transient
            delete_transient('nc_payment_intent_' . get_current_user_id());

            // Process calculation
            $calc_api = new ApiCalculations();
            $calculation = $calc_api->calculate(
                $payment_data['calculation_data'],
                $payment_data['package_type']
            );

            return [
                'success' => true,
                'calculation' => $calculation,
                'redirect' => add_query_arg(
                    'calculation_id',
                    $calculation['id'],
                    home_url('/dashboard')
                )
            ];
        }

        throw new \Exception(__('Payment confirmation failed', 'numerology-compatibility'));
    }

    /**
     * Get user's payment history
     */
    public function get_payment_history($page = 1, $limit = 10)
    {
        $this->client->set_token($this->client->get_token());

        $response = $this->client->request('/payment/history', 'GET', [
            'page' => $page,
            'limit' => $limit
        ], true);

        return $response;
    }

    /**
     * Handle Stripe webhook
     */
    public function handle_stripe_webhook()
    {
        // Get webhook payload
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        // Verify webhook signature
        $endpoint_secret = get_option('nc_stripe_webhook_secret');

        if (empty($endpoint_secret)) {
            return new \WP_Error('config_error', 'Webhook secret not configured', ['status' => 500]);
        }

        try {
            // Forward to Laravel backend for processing
            $response = $this->client->request('/webhooks/stripe', 'POST', [
                'payload' => $payload,
                'signature' => $sig_header
            ]);

            // Handle specific events locally if needed
            $event = json_decode($payload, true);

            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handle_payment_success($event['data']['object']);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handle_payment_failure($event['data']['object']);
                    break;
            }

            return ['success' => true];

        } catch (\Exception $e) {
            return new \WP_Error('webhook_error', $e->getMessage(), ['status' => 400]);
        }
    }

    /**
     * Get package price from settings
     */
    private function get_package_price($package_type)
    {
        switch ($package_type) {
            case 'free':
                return 0;
            case 'light':
                return (float)get_option('nc_price_light', 19);
            case 'pro':
                return (float)get_option('nc_price_pro', 49);
            default:
                throw new \Exception(__('Invalid package type', 'numerology-compatibility'));
        }
    }

    /**
     * Format price for display
     */
    private function format_price($amount, $currency)
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'RUB' => '₽'
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';

        return $symbol . number_format($amount, 2);
    }

    /**
     * Handle successful payment
     */
    private function handle_payment_success($payment_intent)
    {
        // Log success
        error_log('Payment successful: ' . $payment_intent['id']);

        // Send confirmation email
        $metadata = $payment_intent['metadata'] ?? [];
        if (!empty($metadata['user_id'])) {
            $user = get_user_by('ID', $metadata['user_id']);
            if ($user) {
                wp_mail(
                    $user->user_email,
                    __('Payment Successful', 'numerology-compatibility'),
                    sprintf(
                        __('Your payment for %s has been processed successfully. Your report is now available in your dashboard.', 'numerology-compatibility'),
                        $payment_intent['description']
                    )
                );
            }
        }
    }

    /**
     * Handle failed payment
     */
    private function handle_payment_failure($payment_intent)
    {
        // Log failure
        error_log('Payment failed: ' . $payment_intent['id']);

        // Send notification email
        $metadata = $payment_intent['metadata'] ?? [];
        if (!empty($metadata['user_id'])) {
            $user = get_user_by('ID', $metadata['user_id']);
            if ($user) {
                wp_mail(
                    $user->user_email,
                    __('Payment Failed', 'numerology-compatibility'),
                    __('Your payment could not be processed. Please try again or contact support.', 'numerology-compatibility')
                );
            }
        }
    }
}