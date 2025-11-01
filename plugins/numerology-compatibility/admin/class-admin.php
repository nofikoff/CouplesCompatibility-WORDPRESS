<?php
namespace NC\Admin;

class Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register admin menu
     */
    public function add_admin_menu() {
        // Main menu - Settings
        add_menu_page(
            __('Numerology Calculator', 'numerology-compatibility'),
            __('Numerology', 'numerology-compatibility'),
            'manage_options',
            'nc-settings',
            [$this, 'display_settings_page'],
            'dashicons-star-filled',
            30
        );
    }


    /**
     * Display settings page
     */
    public function display_settings_page() {
        include NC_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'nc-') === false && strpos($hook, 'numerology') === false) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name . '-admin',
            NC_PLUGIN_URL . 'admin/assets/css/admin.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'nc-') === false && strpos($hook, 'numerology') === false) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name . '-admin',
            NC_PLUGIN_URL . 'admin/assets/js/admin.js',
            ['jquery'],
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name . '-admin', 'nc_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nc_admin_nonce'),
            'i18n' => [
                'confirm_delete' => __('Are you sure you want to delete this?', 'numerology-compatibility'),
                'loading' => __('Loading...', 'numerology-compatibility'),
                'error' => __('An error occurred', 'numerology-compatibility')
            ]
        ]);
    }

}