<?php
namespace NC\PublicSite;

class PublicClass {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            NC_PLUGIN_URL . 'public/assets/css/public.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register public scripts
     */
    public function enqueue_scripts() {
        // Main plugin script
        wp_enqueue_script(
            $this->plugin_name . '-calculator',
            NC_PLUGIN_URL . 'public/assets/js/calculator.js',
            ['jquery'],
            $this->version,
            true
        );

        // Auth script
        wp_enqueue_script(
            $this->plugin_name . '-auth',
            NC_PLUGIN_URL . 'public/assets/js/auth.js',
            ['jquery'],
            $this->version,
            true
        );

        // Stripe.js
        if (get_option('nc_stripe_publishable_key')) {
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/v3/',
                [],
                null,
                true
            );
        }

        // Localize script
        wp_localize_script($this->plugin_name . '-calculator', 'nc_public', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nc_ajax_nonce'),
            'is_logged_in' => is_user_logged_in(),
            'require_auth' => get_option('nc_require_auth', true),
            'stripe_key' => get_option('nc_stripe_publishable_key'),
            'google_client_id' => get_option('nc_google_client_id'),
            'dashboard_url' => home_url('/dashboard'),
            'login_url' => wp_login_url(),
            'return_url' => home_url('/dashboard'),
            'i18n' => [
                'loading' => __('Loading...', 'numerology-compatibility'),
                'error' => __('An error occurred', 'numerology-compatibility'),
                'confirm_delete' => __('Are you sure?', 'numerology-compatibility')
            ]
        ]);
    }

    /**
     * Add rewrite rules for dashboard
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^dashboard/?$',
            'index.php?nc_dashboard=1',
            'top'
        );

        add_rewrite_rule(
            '^dashboard/([^/]+)/?$',
            'index.php?nc_dashboard=1&nc_tab=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'nc_dashboard';
        $vars[] = 'nc_tab';
        return $vars;
    }

    /**
     * Handle dashboard template
     */
    public function handle_dashboard_template() {
        if (get_query_var('nc_dashboard')) {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/dashboard')));
                exit;
            }

            // Load dashboard template
            $template = NC_PLUGIN_DIR . 'public/views/dashboard.php';

            if (file_exists($template)) {
                // Set page title
                add_filter('wp_title', function() {
                    return __('My Dashboard', 'numerology-compatibility') . ' | ';
                });

                // Include header
                get_header();

                // Include dashboard
                include $template;

                // Include footer
                get_footer();

                exit;
            }
        }
    }
}