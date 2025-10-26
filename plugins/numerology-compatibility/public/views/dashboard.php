<?php
// plugins/numerology-compatibility/public/views/dashboard.php
/**
 * User dashboard template
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

use NC\Database\Database;
use NC\Api\ApiCalculations;
use NC\Api\ApiPayments;

$current_user = wp_get_current_user();
$db = Database::getInstance();
$calc_api = new ApiCalculations();
$payment_api = new ApiPayments();

// Get user's calculations
$calculations = $db->get_user_calculations($current_user->ID, 10, 0);

// Get user's subscription status
$subscription = get_user_meta($current_user->ID, 'nc_subscription_type', true) ?: 'free';
$credits = get_user_meta($current_user->ID, 'nc_credits_remaining', true) ?: 0;

// Handle GDPR actions
$show_delete_form = isset($_GET['action']) && $_GET['action'] === 'delete-account';
?>

<div class="nc-dashboard-wrapper">
    <!-- Header -->
    <div class="nc-dashboard-header">
        <h1><?php printf(__('Hello, %s', 'numerology-compatibility'), esc_html($current_user->display_name)); ?></h1>
        <p><?php _e('Welcome to your numerology dashboard', 'numerology-compatibility'); ?></p>
    </div>

    <!-- Quick Stats -->
    <div class="nc-stats-row">
        <div class="nc-stat-box">
            <div class="nc-stat-label"><?php _e('Account Type', 'numerology-compatibility'); ?></div>
            <div class="nc-stat-value"><?php echo ucfirst(esc_html($subscription)); ?></div>
        </div>

        <div class="nc-stat-box">
            <div class="nc-stat-label"><?php _e('Credits Remaining', 'numerology-compatibility'); ?></div>
            <div class="nc-stat-value"><?php echo esc_html($credits); ?></div>
        </div>

        <div class="nc-stat-box">
            <div class="nc-stat-label"><?php _e('Total Calculations', 'numerology-compatibility'); ?></div>
            <div class="nc-stat-value"><?php echo count($calculations); ?></div>
        </div>

        <div class="nc-stat-box">
            <div class="nc-stat-label"><?php _e('Member Since', 'numerology-compatibility'); ?></div>
            <div class="nc-stat-value"><?php echo date('M Y', strtotime($current_user->user_registered)); ?></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="nc-dashboard-tabs">
        <ul class="nc-tab-nav">
            <li class="nc-tab-item active">
                <a href="#calculations" data-tab="calculations"><?php _e('My Calculations', 'numerology-compatibility'); ?></a>
            </li>
            <li class="nc-tab-item">
                <a href="#profile" data-tab="profile"><?php _e('Profile Settings', 'numerology-compatibility'); ?></a>
            </li>
            <li class="nc-tab-item">
                <a href="#billing" data-tab="billing"><?php _e('Billing', 'numerology-compatibility'); ?></a>
            </li>
            <li class="nc-tab-item">
                <a href="#privacy" data-tab="privacy"><?php _e('Privacy', 'numerology-compatibility'); ?></a>
            </li>
        </ul>

        <!-- Calculations Tab -->
        <div class="nc-tab-panel nc-tab-calculations active">
            <h2><?php _e('Recent Calculations', 'numerology-compatibility'); ?></h2>

            <?php if ($calculations): ?>
                <div class="nc-calculations-grid">
                    <?php foreach ($calculations as $calc): ?>
                        <div class="nc-calculation-card">
                            <div class="nc-calc-header">
                                <span class="nc-calc-type nc-badge-<?php echo esc_attr($calc->package_type); ?>">
                                    <?php echo ucfirst(esc_html($calc->package_type)); ?>
                                </span>
                                <span class="nc-calc-date">
                                    <?php echo human_time_diff(strtotime($calc->created_at), current_time('timestamp')); ?> ago
                                </span>
                            </div>

                            <div class="nc-calc-body">
                                <div class="nc-calc-partners">
                                    <div class="nc-partner">
                                        <strong><?php echo esc_html($calc->person1_name ?: 'Partner 1'); ?></strong>
                                        <small><?php echo esc_html($calc->person1_date); ?></small>
                                    </div>
                                    <div class="nc-compatibility-icon">❤️</div>
                                    <div class="nc-partner">
                                        <strong><?php echo esc_html($calc->person2_name ?: 'Partner 2'); ?></strong>
                                        <small><?php echo esc_html($calc->person2_date); ?></small>
                                    </div>
                                </div>

                                <?php if ($calc->result_summary):
                                    $summary = json_decode($calc->result_summary, true);
                                    ?>
                                    <div class="nc-calc-summary">
                                        <?php echo wp_kses_post($summary['excerpt'] ?? ''); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="nc-calc-actions">
                                <a href="#" class="nc-btn nc-btn-small nc-view-calculation" data-id="<?php echo esc_attr($calc->calculation_id); ?>">
                                    <?php _e('View', 'numerology-compatibility'); ?>
                                </a>
                                <?php if ($calc->pdf_url): ?>
                                    <a href="<?php echo esc_url($calc->pdf_url); ?>" class="nc-btn nc-btn-small" download>
                                        <?php _e('Download PDF', 'numerology-compatibility'); ?>
                                    </a>
                                <?php endif; ?>
                                <button class="nc-btn nc-btn-small nc-btn-danger nc-delete-calculation" data-id="<?php echo esc_attr($calc->calculation_id); ?>">
                                    <?php _e('Delete', 'numerology-compatibility'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="nc-pagination">
                    <!-- Pagination would go here -->
                </div>
            <?php else: ?>
                <div class="nc-empty-state">
                    <p><?php _e('No calculations yet. Start your first compatibility analysis!', 'numerology-compatibility'); ?></p>
                    <a href="<?php echo home_url('/calculator'); ?>" class="nc-btn nc-btn-primary">
                        <?php _e('Start New Calculation', 'numerology-compatibility'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Profile Tab -->
        <div class="nc-tab-panel nc-tab-profile">
            <h2><?php _e('Profile Settings', 'numerology-compatibility'); ?></h2>

            <form id="nc-profile-form" class="nc-settings-form">
                <div class="nc-form-group">
                    <label for="display_name"><?php _e('Display Name', 'numerology-compatibility'); ?></label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>">
                </div>

                <div class="nc-form-group">
                    <label for="email"><?php _e('Email', 'numerology-compatibility'); ?></label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" readonly>
                    <small><?php _e('Email cannot be changed', 'numerology-compatibility'); ?></small>
                </div>

                <div class="nc-form-group">
                    <label for="language"><?php _e('Preferred Language', 'numerology-compatibility'); ?></label>
                    <select id="language" name="language">
                        <option value="en_US">English</option>
                        <option value="es_ES">Español</option>
                        <option value="fr_FR">Français</option>
                        <option value="de_DE">Deutsch</option>
                        <option value="ru_RU">Русский</option>
                    </select>
                </div>

                <div class="nc-form-group">
                    <label for="timezone"><?php _e('Timezone', 'numerology-compatibility'); ?></label>
                    <?php echo wp_timezone_choice(get_user_meta($current_user->ID, 'nc_timezone', true)); ?>
                </div>

                <div class="nc-form-group">
                    <label for="currency"><?php _e('Preferred Currency', 'numerology-compatibility'); ?></label>
                    <select id="currency" name="currency">
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                    </select>
                </div>

                <div class="nc-form-group">
                    <h3><?php _e('Email Preferences', 'numerology-compatibility'); ?></h3>
                    <label class="nc-checkbox">
                        <input type="checkbox" name="email_reports" <?php checked(get_user_meta($current_user->ID, 'nc_email_reports', true)); ?>>
                        <span><?php _e('Send me calculation reports via email', 'numerology-compatibility'); ?></span>
                    </label>
                    <label class="nc-checkbox">
                        <input type="checkbox" name="email_marketing" <?php checked(get_user_meta($current_user->ID, 'nc_email_marketing', true)); ?>>
                        <span><?php _e('Send me numerology insights and special offers', 'numerology-compatibility'); ?></span>
                    </label>
                </div>

                <button type="submit" class="nc-btn nc-btn-primary">
                    <?php _e('Save Changes', 'numerology-compatibility'); ?>
                </button>
            </form>
        </div>

        <!-- Billing Tab -->
        <div class="nc-tab-panel nc-tab-billing">
            <h2><?php _e('Billing & Payments', 'numerology-compatibility'); ?></h2>

            <div class="nc-billing-info">
                <h3><?php _e('Current Plan', 'numerology-compatibility'); ?></h3>
                <div class="nc-plan-card">
                    <div class="nc-plan-name"><?php echo ucfirst(esc_html($subscription)); ?> Plan</div>
                    <?php if ($subscription !== 'free'): ?>
                        <div class="nc-plan-details">
                            <p><?php _e('Next billing date:', 'numerology-compatibility'); ?> <?php echo date('F j, Y', strtotime('+30 days')); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($subscription === 'free'): ?>
                    <div class="nc-upgrade-prompt">
                        <h3><?php _e('Upgrade Your Plan', 'numerology-compatibility'); ?></h3>
                        <p><?php _e('Unlock more features with a paid plan', 'numerology-compatibility'); ?></p>
                        <a href="<?php echo home_url('/pricing'); ?>" class="nc-btn nc-btn-primary">
                            <?php _e('View Plans', 'numerology-compatibility'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <h3><?php _e('Purchase History', 'numerology-compatibility'); ?></h3>
                <table class="nc-table">
                    <thead>
                    <tr>
                        <th><?php _e('Date', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Description', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Amount', 'numerology-compatibility'); ?></th>
                        <th><?php _e('Status', 'numerology-compatibility'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Purchase history would be populated here -->
                    <tr>
                        <td colspan="4" class="nc-no-data">
                            <?php _e('No purchases yet', 'numerology-compatibility'); ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Privacy Tab -->
        <div class="nc-tab-panel nc-tab-privacy">
            <h2><?php _e('Privacy Settings', 'numerology-compatibility'); ?></h2>

            <div class="nc-privacy-section">
                <h3><?php _e('Data Export', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Download all your data in a machine-readable format', 'numerology-compatibility'); ?></p>
                <button class="nc-btn nc-btn-secondary" id="nc-export-data">
                    <?php _e('Export My Data', 'numerology-compatibility'); ?>
                </button>
            </div>

            <div class="nc-privacy-section nc-danger-zone">
                <h3><?php _e('Delete Account', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Permanently delete your account and all associated data', 'numerology-compatibility'); ?></p>

                <?php if ($show_delete_form): ?>
                    <div class="nc-delete-form">
                        <div class="nc-alert nc-alert-danger">
                            <strong><?php _e('Warning!', 'numerology-compatibility'); ?></strong>
                            <?php _e('This action cannot be undone. All your data will be permanently deleted.', 'numerology-compatibility'); ?>
                        </div>

                        <form id="nc-delete-account-form">
                            <div class="nc-form-group">
                                <label for="delete_confirmation">
                                    <?php _e('Type DELETE to confirm', 'numerology-compatibility'); ?>
                                </label>
                                <input type="text" id="delete_confirmation" name="confirmation" required>
                            </div>

                            <button type="submit" class="nc-btn nc-btn-danger">
                                <?php _e('Delete My Account', 'numerology-compatibility'); ?>
                            </button>
                            <a href="<?php echo remove_query_arg('action'); ?>" class="nc-btn nc-btn-secondary">
                                <?php _e('Cancel', 'numerology-compatibility'); ?>
                            </a>
                        </form>
                    </div>
                <?php else: ?>
                    <a href="<?php echo add_query_arg('action', 'delete-account'); ?>" class="nc-btn nc-btn-danger">
                        <?php _e('Delete My Account', 'numerology-compatibility'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Tab switching
        $('.nc-tab-nav a').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');

            $('.nc-tab-item').removeClass('active');
            $(this).parent().addClass('active');

            $('.nc-tab-panel').removeClass('active');
            $('.nc-tab-' + tab).addClass('active');
        });

        // Delete calculation
        $('.nc-delete-calculation').on('click', function() {
            if (confirm('<?php _e('Are you sure you want to delete this calculation?', 'numerology-compatibility'); ?>')) {
                var calculationId = $(this).data('id');
                // AJAX call to delete
            }
        });

        // Profile form submission
        $('#nc-profile-form').on('submit', function(e) {
            e.preventDefault();
            // AJAX call to save profile
        });

        // Delete account form
        $('#nc-delete-account-form').on('submit', function(e) {
            e.preventDefault();
            if ($('#delete_confirmation').val() !== 'DELETE') {
                alert('<?php _e('Please type DELETE to confirm', 'numerology-compatibility'); ?>');
                return;
            }
            // AJAX call to delete account
        });
    });
</script>