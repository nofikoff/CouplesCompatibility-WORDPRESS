<?php
/**
 * Statistics page template
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

use NC\Database\Database;

$db = Database::getInstance();
$stats = $db->get_statistics();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get detailed statistics
global $wpdb;
$calculations_table = $db->get_table('calculations');
$transactions_table = $db->get_table('transactions');
$analytics_table = $db->get_table('analytics');

// Daily calculations
$daily_stats = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(created_at) as date, COUNT(*) as count 
     FROM $calculations_table 
     WHERE created_at BETWEEN %s AND %s
     GROUP BY DATE(created_at) 
     ORDER BY date ASC",
    $start_date . ' 00:00:00',
    $end_date . ' 23:59:59'
));

// Revenue by day
$daily_revenue = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(created_at) as date, SUM(amount) as revenue 
     FROM $transactions_table 
     WHERE status = 'completed' 
     AND created_at BETWEEN %s AND %s
     GROUP BY DATE(created_at) 
     ORDER BY date ASC",
    $start_date . ' 00:00:00',
    $end_date . ' 23:59:59'
));

// Top events
$top_events = $wpdb->get_results(
    "SELECT event_type, COUNT(*) as count 
     FROM $analytics_table 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY event_type 
     ORDER BY count DESC 
     LIMIT 10"
);
?>

<div class="wrap">
    <h1><?php _e('Statistics & Analytics', 'numerology-compatibility'); ?></h1>

    <!-- Date Range Filter -->
    <div class="nc-stats-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="nc-statistics">

            <label for="start_date"><?php _e('From:', 'numerology-compatibility'); ?></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">

            <label for="end_date"><?php _e('To:', 'numerology-compatibility'); ?></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">

            <button type="submit" class="button"><?php _e('Filter', 'numerology-compatibility'); ?></button>

            <div class="nc-quick-filters">
                <a href="?page=nc-statistics&start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>" class="button">
                    <?php _e('Last 7 days', 'numerology-compatibility'); ?>
                </a>
                <a href="?page=nc-statistics&start_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>" class="button">
                    <?php _e('Last 30 days', 'numerology-compatibility'); ?>
                </a>
                <a href="?page=nc-statistics&start_date=<?php echo date('Y-m-d', strtotime('-90 days')); ?>" class="button">
                    <?php _e('Last 90 days', 'numerology-compatibility'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="nc-metrics-grid">
        <div class="nc-metric-card">
            <h3><?php _e('Total Revenue', 'numerology-compatibility'); ?></h3>
            <div class="nc-metric-value">
                $<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?>
            </div>
            <div class="nc-metric-change positive">
                +12.5% <?php _e('vs last period', 'numerology-compatibility'); ?>
            </div>
        </div>

        <div class="nc-metric-card">
            <h3><?php _e('Conversion Rate', 'numerology-compatibility'); ?></h3>
            <div class="nc-metric-value">
                <?php
                $unique_customers = $stats['unique_customers'] ?? 0;
                $total_revenue = $stats['total_revenue'] ?? 0;
                $conversion_rate = $unique_customers > 0
                    ? round(($total_revenue / 19) / $unique_customers * 100, 2)
                    : 0;
                echo $conversion_rate;
                ?>%
            </div>
            <div class="nc-metric-change negative">
                -2.3% <?php _e('vs last period', 'numerology-compatibility'); ?>
            </div>
        </div>

        <div class="nc-metric-card">
            <h3><?php _e('Average Order Value', 'numerology-compatibility'); ?></h3>
            <div class="nc-metric-value">
                $<?php
                $completed_orders = $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table WHERE status = 'completed'");
                $total_revenue = $stats['total_revenue'] ?? 0;
                $aov = $completed_orders > 0 ? $total_revenue / $completed_orders : 0;
                echo number_format($aov, 2);
                ?>
            </div>
            <div class="nc-metric-change positive">
                +5.8% <?php _e('vs last period', 'numerology-compatibility'); ?>
            </div>
        </div>

        <div class="nc-metric-card">
            <h3><?php _e('Active Users', 'numerology-compatibility'); ?></h3>
            <div class="nc-metric-value">
                <?php
                $active_users = $wpdb->get_var(
                    "SELECT COUNT(DISTINCT user_id) 
                     FROM $calculations_table 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
                );
                echo number_format($active_users);
                ?>
            </div>
            <div class="nc-metric-change positive">
                +18.2% <?php _e('vs last period', 'numerology-compatibility'); ?>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="nc-charts-container">
        <!-- Calculations Chart -->
        <div class="nc-chart-box">
            <h2><?php _e('Daily Calculations', 'numerology-compatibility'); ?></h2>
            <canvas id="nc-calculations-chart"></canvas>
        </div>

        <!-- Revenue Chart -->
        <div class="nc-chart-box">
            <h2><?php _e('Daily Revenue', 'numerology-compatibility'); ?></h2>
            <canvas id="nc-revenue-chart"></canvas>
        </div>
    </div>

    <!-- Package Distribution -->
    <div class="nc-charts-container">
        <div class="nc-chart-box nc-half">
            <h2><?php _e('Package Distribution', 'numerology-compatibility'); ?></h2>
            <canvas id="nc-package-pie-chart"></canvas>
        </div>

        <!-- Top Events -->
        <div class="nc-chart-box nc-half">
            <h2><?php _e('Top Events', 'numerology-compatibility'); ?></h2>
            <table class="wp-list-table widefat">
                <thead>
                <tr>
                    <th><?php _e('Event', 'numerology-compatibility'); ?></th>
                    <th><?php _e('Count', 'numerology-compatibility'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($top_events as $event): ?>
                    <tr>
                        <td><?php echo esc_html($event->event_type); ?></td>
                        <td><?php echo number_format($event->count); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Cohorts -->
    <div class="nc-cohort-analysis">
        <h2><?php _e('User Retention Cohort', 'numerology-compatibility'); ?></h2>
        <table class="nc-cohort-table">
            <thead>
            <tr>
                <th><?php _e('Cohort', 'numerology-compatibility'); ?></th>
                <th><?php _e('Users', 'numerology-compatibility'); ?></th>
                <th><?php _e('Day 1', 'numerology-compatibility'); ?></th>
                <th><?php _e('Day 7', 'numerology-compatibility'); ?></th>
                <th><?php _e('Day 14', 'numerology-compatibility'); ?></th>
                <th><?php _e('Day 30', 'numerology-compatibility'); ?></th>
            </tr>
            </thead>
            <tbody>
            <!-- Cohort data would be populated here -->
            </tbody>
        </table>
    </div>

    <!-- Export Options -->
    <div class="nc-export-section">
        <h2><?php _e('Export Data', 'numerology-compatibility'); ?></h2>
        <button class="button button-primary" id="nc-export-csv">
            <?php _e('Export to CSV', 'numerology-compatibility'); ?>
        </button>
        <button class="button" id="nc-export-pdf">
            <?php _e('Export to PDF', 'numerology-compatibility'); ?>
        </button>
    </div>
</div>

<script>
    // Prepare data for charts
    var dailyStatsData = <?php echo json_encode($daily_stats); ?>;
    var dailyRevenueData = <?php echo json_encode($daily_revenue); ?>;
    var packageData = <?php echo json_encode($stats['packages'] ?? []); ?>;

    // This will be initialized in admin.js
</script>