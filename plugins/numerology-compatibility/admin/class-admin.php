<?php
namespace NC\Admin;

use NC\Database\Database;

class Admin {

    private $plugin_name;
    private $version;
    private $db;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = Database::getInstance();
    }

    /**
     * Register admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Numerology Calculator', 'numerology-compatibility'),
            __('Numerology', 'numerology-compatibility'),
            'manage_options',
            'nc-dashboard',
            [$this, 'display_dashboard_page'],
            'dashicons-star-filled',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'nc-dashboard',
            __('Dashboard', 'numerology-compatibility'),
            __('Dashboard', 'numerology-compatibility'),
            'manage_options',
            'nc-dashboard',
            [$this, 'display_dashboard_page']
        );

        // Settings submenu
        add_submenu_page(
            'nc-dashboard',
            __('Settings', 'numerology-compatibility'),
            __('Settings', 'numerology-compatibility'),
            'manage_options',
            'nc-settings',
            [$this, 'display_settings_page']
        );

        // Statistics submenu
        add_submenu_page(
            'nc-dashboard',
            __('Statistics', 'numerology-compatibility'),
            __('Statistics', 'numerology-compatibility'),
            'manage_options',
            'nc-statistics',
            [$this, 'display_statistics_page']
        );

        // Calculations submenu
        add_submenu_page(
            'nc-dashboard',
            __('Calculations', 'numerology-compatibility'),
            __('Calculations', 'numerology-compatibility'),
            'manage_options',
            'nc-calculations',
            [$this, 'display_calculations_page']
        );

        // Users submenu
        add_submenu_page(
            'nc-dashboard',
            __('Users', 'numerology-compatibility'),
            __('Users', 'numerology-compatibility'),
            'manage_options',
            'nc-users',
            [$this, 'display_users_page']
        );

        // Logs submenu
        add_submenu_page(
            'nc-dashboard',
            __('Logs', 'numerology-compatibility'),
            __('Logs', 'numerology-compatibility'),
            'manage_options',
            'nc-logs',
            [$this, 'display_logs_page']
        );
    }

    /**
     * Display dashboard page
     */
    public function display_dashboard_page() {
        $stats = $this->db->get_statistics();
        ?>
        <div class="wrap">
            <h1><?php _e('Numerology Calculator Dashboard', 'numerology-compatibility'); ?></h1>

            <div class="nc-admin-dashboard">
                <!-- Quick Stats -->
                <div class="nc-stats-grid">
                    <div class="nc-stat-card">
                        <h3><?php _e('Total Users', 'numerology-compatibility'); ?></h3>
                        <div class="nc-stat-number"><?php echo number_format($stats['unique_customers'] ?? 0); ?></div>
                    </div>

                    <div class="nc-stat-card">
                        <h3><?php _e('Total Calculations', 'numerology-compatibility'); ?></h3>
                        <div class="nc-stat-number"><?php echo number_format($stats['total_calculations'] ?? 0); ?></div>
                    </div>

                    <div class="nc-stat-card">
                        <h3><?php _e('Revenue', 'numerology-compatibility'); ?></h3>
                        <div class="nc-stat-number">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                    </div>

                    <div class="nc-stat-card">
                        <h3><?php _e("Today's Calculations", 'numerology-compatibility'); ?></h3>
                        <div class="nc-stat-number"><?php echo number_format($stats['today_calculations'] ?? 0); ?></div>
                    </div>
                </div>

                <!-- Package Distribution Chart -->
                <div class="nc-chart-container">
                    <h2><?php _e('Package Distribution', 'numerology-compatibility'); ?></h2>
                    <canvas id="nc-package-chart"></canvas>
                </div>

                <!-- Recent Activity -->
                <div class="nc-recent-activity">
                    <h2><?php _e('Recent Activity', 'numerology-compatibility'); ?></h2>
                    <?php $this->display_recent_activity(); ?>
                </div>
            </div>
        </div>

        <script>
            // Initialize chart
            jQuery(document).ready(function($) {
                var packageData = <?php echo json_encode($stats['packages'] ?? []); ?>;
                // Chart.js code here
            });
        </script>
        <?php
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        include NC_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Display statistics page
     */
    public function display_statistics_page() {
        include NC_PLUGIN_DIR . 'admin/views/statistics.php';
    }

    /**
     * Display calculations page
     */
    public function display_calculations_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Calculations', 'numerology-compatibility'); ?></h1>

            <div class="nc-admin-calculations">
                <?php
                // Get calculations list
                $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $per_page = 20;
                $offset = ($page - 1) * $per_page;

                global $wpdb;
                $table = $this->db->get_table('calculations');

                $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $calculations = $wpdb->get_results(
                    "SELECT c.*, u.display_name 
                     FROM $table c 
                     LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
                     ORDER BY c.created_at DESC 
                     LIMIT $per_page OFFSET $offset"
                );
                ?>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                    <tr>
                        <th><?php _e('ID', 'numerology-compatibility'); ?></th>
                        <th><?php _e('User', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Package', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Partner 1', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Partner 2', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Date', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Actions', 'numerology-compatibility'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($calculations as $calc): ?>
                        <tr>
                            <td><?php echo esc_html($calc->id); ?></td>
                            <td><?php echo esc_html($calc->display_name); ?></td>
                            <td>
                                <span class="nc-badge nc-badge-<?php echo esc_attr($calc->package_type); ?>">
                                    <?php echo esc_html(ucfirst($calc->package_type)); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($calc->person1_name ?: 'N/A'); ?><br>
                                <small><?php echo esc_html($calc->person1_date); ?></small>
                            </td>
                            <td>
                                <?php echo esc_html($calc->person2_name ?: 'N/A'); ?><br>
                                <small><?php echo esc_html($calc->person2_date); ?></small>
                            </td>
                            <td><?php echo esc_html($calc->created_at); ?></td>
                            <td>
                                <a href="#" class="button button-small nc-view-calculation" data-id="<?php echo esc_attr($calc->id); ?>">
                                    <?php _e('View', 'numerology-compatibility'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                // Pagination
                $total_pages = ceil($total / $per_page);
                if ($total_pages > 1) {
                    echo '<div class="tablenav"><div class="tablenav-pages">';
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $page
                    ]);
                    echo '</div></div>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display users page
     */
    public function display_users_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Users', 'numerology-compatibility'); ?></h1>
            <!-- Users management interface -->
        </div>
        <?php
    }

    /**
     * Display logs page
     */
    public function display_logs_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('System Logs', 'numerology-compatibility'); ?></h1>
            <!-- Logs viewer interface -->
        </div>
        <?php
    }

    /**
     * Display recent activity
     */
    private function display_recent_activity() {
        global $wpdb;
        $table = $this->db->get_table('analytics');

        $events = $wpdb->get_results(
            "SELECT * FROM $table 
             ORDER BY created_at DESC 
             LIMIT 10"
        );

        if ($events) {
            echo '<ul class="nc-activity-list">';
            foreach ($events as $event) {
                $data = json_decode($event->event_data, true);
                echo '<li>';
                echo '<span class="nc-activity-type">' . esc_html($event->event_type) . '</span>';
                echo '<span class="nc-activity-time">' . human_time_diff(strtotime($event->created_at)) . ' ago</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('No recent activity', 'numerology-compatibility') . '</p>';
        }
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

    /**
     * Get statistics via AJAX
     */
    public function get_statistics() {
        if (!check_ajax_referer('nc_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'numerology-compatibility')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'numerology-compatibility')]);
        }

        $stats = $this->db->get_statistics();
        wp_send_json_success($stats);
    }
}