<?php
/**
 * Result page template for [numerology_result] shortcode
 * Displays calculation result and PDF download link
 *
 * URL parameters:
 * - ?code={secret_code} - access calculation by secret code
 * - ?payment_success=1&payment_id={id} - after successful payment
 * - ?payment_cancelled=1 - payment was cancelled
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

$secret_code = sanitize_text_field($_GET['code'] ?? '');
$payment_success = isset($_GET['payment_success']) && $_GET['payment_success'] === '1';
$payment_id = sanitize_text_field($_GET['payment_id'] ?? '');
$calculation_id = sanitize_text_field($_GET['calculation_id'] ?? '');
$payment_cancelled = isset($_GET['payment_cancelled']) && $_GET['payment_cancelled'] === '1';

// Determine initial state
$show_pending = $payment_success && $payment_id;
$show_cancelled = $payment_cancelled;
$show_result = !empty($secret_code) && !$show_pending && !$show_cancelled;
$show_empty = !$show_pending && !$show_cancelled && empty($secret_code);
?>

<div id="nc-result-wrapper" class="nc-result-wrapper"
     data-secret-code="<?php echo esc_attr($secret_code); ?>"
     data-payment-id="<?php echo esc_attr($payment_id); ?>"
     data-calculation-id="<?php echo esc_attr($calculation_id); ?>"
     data-payment-success="<?php echo $payment_success ? '1' : '0'; ?>">

    <!-- Loading State -->
    <div class="nc-result-state nc-result-loading <?php echo ($show_pending || $show_result) ? '' : 'nc-hidden'; ?>">
        <div class="nc-pending">
            <div class="nc-spinner"></div>
            <h2><?php echo $show_pending
                ? __('Verifying Payment...', 'numerology-compatibility')
                : __('Loading...', 'numerology-compatibility'); ?></h2>
            <p class="nc-pending-message">
                <?php echo $show_pending
                    ? __('Please wait while we confirm your payment. This usually takes a few seconds.', 'numerology-compatibility')
                    : __('Loading your calculation result...', 'numerology-compatibility'); ?>
            </p>
            <p><small><?php _e('Do not close this page.', 'numerology-compatibility'); ?></small></p>
        </div>
    </div>

    <!-- Success State: PDF Ready -->
    <div class="nc-result-state nc-result-success nc-hidden">
        <div class="nc-success">
            <div class="nc-success-icon">✓</div>
            <h2><?php _e('Your Report is Ready!', 'numerology-compatibility'); ?></h2>
            <p class="nc-success-message"><?php _e('Your numerology compatibility report has been generated.', 'numerology-compatibility'); ?></p>

            <!-- PDF Download Link -->
            <div class="nc-pdf-download">
                <a href="#" id="nc-result-pdf-link" class="nc-btn nc-btn-primary nc-btn-large" target="_blank">
                    📄 <?php _e('Download PDF Report', 'numerology-compatibility'); ?>
                </a>
            </div>

            <!-- Tier Badge -->
            <div class="nc-tier-badge nc-hidden" id="nc-tier-badge">
                <span class="nc-tier-label"></span>
            </div>

            <!-- Email Form -->
            <div class="nc-email-form nc-mt-2">
                <h3 class="nc-email-form-title"><?php _e('Send Report to Email?', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Optionally, receive this report via email:', 'numerology-compatibility'); ?></p>

                <form id="nc-result-email-form" class="nc-form">
                    <div class="nc-field">
                        <label for="nc-result-email"><?php _e('Email Address', 'numerology-compatibility'); ?></label>
                        <input type="email" id="nc-result-email" name="email"
                               placeholder="your@email.com"
                               autocomplete="email">
                        <span class="nc-error-message nc-hidden"></span>
                    </div>

                    <button type="submit" class="nc-btn nc-btn-secondary nc-mt-1">
                        📧 <?php _e('Send to Email', 'numerology-compatibility'); ?>
                    </button>

                    <p class="nc-email-sent-message nc-hidden">
                        ✓ <?php _e('Email sent successfully!', 'numerology-compatibility'); ?>
                    </p>
                </form>
            </div>

            <!-- New Calculation Link -->
            <div class="nc-form-actions nc-mt-2">
                <?php
                $calculator_url = home_url('/');
                ?>
                <a href="<?php echo esc_url($calculator_url); ?>" class="nc-btn nc-btn-outline">
                    <?php _e('Calculate Another', 'numerology-compatibility'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- PDF Generating State -->
    <div class="nc-result-state nc-result-generating nc-hidden">
        <div class="nc-success nc-generating">
            <div class="nc-generating-icon">⏳</div>
            <h2><?php _e('Generating PDF...', 'numerology-compatibility'); ?></h2>
            <p class="nc-success-message"><?php _e('Your PDF report is being generated. This usually takes a few seconds.', 'numerology-compatibility'); ?></p>

            <div class="nc-pdf-progress">
                <div class="nc-spinner-small"></div>
                <span><?php _e('Please wait...', 'numerology-compatibility'); ?></span>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div class="nc-result-state nc-result-error nc-hidden">
        <div class="nc-error-page">
            <div class="nc-error-icon">✕</div>
            <h2><?php _e('Oops! Something Went Wrong', 'numerology-compatibility'); ?></h2>
            <p class="nc-error-message"><?php _e('We could not load your calculation result.', 'numerology-compatibility'); ?></p>

            <div class="nc-error-details">
                <p><?php _e('If you completed a payment, please contact our support team with your payment confirmation.', 'numerology-compatibility'); ?></p>
            </div>

            <div class="nc-form-actions">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="nc-btn nc-btn-primary">
                    <?php _e('Go to Homepage', 'numerology-compatibility'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Cancelled State -->
    <div class="nc-result-state nc-result-cancelled <?php echo $show_cancelled ? '' : 'nc-hidden'; ?>">
        <div class="nc-error-page">
            <div class="nc-error-icon" style="background: var(--nc-secondary, #F59E0B);">⚠</div>
            <h2><?php _e('Payment Cancelled', 'numerology-compatibility'); ?></h2>
            <p class="nc-error-message"><?php _e('Your payment was cancelled. No charges were made.', 'numerology-compatibility'); ?></p>

            <div class="nc-form-actions">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="nc-btn nc-btn-primary">
                    <?php _e('Try Again', 'numerology-compatibility'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Empty State (no code provided) -->
    <div class="nc-result-state nc-result-empty <?php echo $show_empty ? '' : 'nc-hidden'; ?>">
        <div class="nc-error-page">
            <div class="nc-error-icon" style="background: var(--nc-secondary, #F59E0B);">?</div>
            <h2><?php _e('No Result Found', 'numerology-compatibility'); ?></h2>
            <p class="nc-error-message"><?php _e('Please complete a calculation first to see your results.', 'numerology-compatibility'); ?></p>

            <div class="nc-form-actions">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="nc-btn nc-btn-primary">
                    <?php _e('Start Calculation', 'numerology-compatibility'); ?>
                </a>
            </div>
        </div>
    </div>

</div>
