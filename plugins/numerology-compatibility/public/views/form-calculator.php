<?php
// plugins/numerology-compatibility/public/views/form-calculator.php
/**
 * Calculator form template
 */

// Security check
if (!defined('ABSPATH')) {
	exit;
}

$package_type = $attributes['package'] ?? 'auto';
$show_prices = $attributes['show_prices'] ?? true;
?>

<div id="nc-calculator-wrapper" class="nc-calculator" data-package="<?php echo esc_attr($package_type); ?>">

    <!-- Step 1: Input Form -->
    <div class="nc-step nc-step-1 nc-active" data-step="1">
        <form id="nc-calculator-form" class="nc-form">

            <!-- Email поле убрано - теперь запрашивается опционально после расчета -->

            <div class="nc-form-row">
                <!-- Partner 1 -->
                <div class="nc-form-group nc-partner-1">
                    <h3><?php _e('Partner 1', 'numerology-compatibility'); ?></h3>

                    <div class="nc-field nc-required">
                        <label for="person1_date"><?php _e('Date of Birth', 'numerology-compatibility'); ?></label>
                        <input type="date" id="person1_date" name="person1_date" required
                               max="<?php echo date('Y-m-d'); ?>"
                               min="1900-01-01">
                        <span class="nc-error-message nc-hidden"></span>
                    </div>
                </div>

                <!-- Partner 2 -->
                <div class="nc-form-group nc-partner-2">
                    <h3><?php _e('Partner 2', 'numerology-compatibility'); ?></h3>

                    <div class="nc-field nc-required">
                        <label for="person2_date"><?php _e('Date of Birth', 'numerology-compatibility'); ?></label>
                        <input type="date" id="person2_date" name="person2_date" required
                               max="<?php echo date('Y-m-d'); ?>"
                               min="1900-01-01">
                        <span class="nc-error-message nc-hidden"></span>
                    </div>
                </div>
            </div>
            <!-- Consent checkboxes for calculation -->
            <div class="nc-consent-group">
                <div class="nc-checkbox-group">
                    <label class="nc-checkbox">
                        <input type="checkbox" name="harm_consent" id="harm_consent" required>
                        <span><?php _e("I will not use this information to harm, harass, or make decisions affecting this person without their knowledge", 'numerology-compatibility'); ?> *</span>
                    </label>

                    <label class="nc-checkbox">
                        <input type="checkbox" name="entertainment_consent" id="entertainment_consent" required>
                        <span><?php _e("I understand this is for entertainment and self-reflection purposes only", 'numerology-compatibility'); ?> *</span>
                    </label>
                </div>
            </div>

            <div class="nc-form-actions">
                <button type="submit" class="nc-btn nc-btn-primary nc-btn-large">
					<?php _e('Continue', 'numerology-compatibility'); ?> →
                </button>
            </div>
        </form>
    </div>

    <!-- Step 2: Package Selection -->
	<?php if ($package_type === 'auto' && $show_prices): ?>
        <div class="nc-step nc-step-2 nc-hidden" data-step="2">
            <h2><?php _e('Choose Your Report Type', 'numerology-compatibility'); ?></h2>

            <div class="nc-packages">
                <div class="nc-package" data-package="free" data-tier="free">
                    <div class="nc-package-header">
                        <h3><?php _e('Free', 'numerology-compatibility'); ?></h3>
                        <div class="nc-price"><?php _e('Free', 'numerology-compatibility'); ?></div>
                    </div>
                    <ul class="nc-features">
                        <li><?php _e('Basic compatibility score', 'numerology-compatibility'); ?></li>
                        <li><?php _e('Key personality insights', 'numerology-compatibility'); ?></li>
                        <li><?php _e('PDF report via email', 'numerology-compatibility'); ?></li>
                    </ul>
                    <button class="nc-btn nc-btn-outline nc-select-package" data-package="free" data-tier="free">
						<?php _e('Get Free Report', 'numerology-compatibility'); ?>
                    </button>
                </div>

                <div class="nc-package nc-package-featured" data-package="standard" data-tier="standard">
                    <div class="nc-badge"><?php _e('Most Popular', 'numerology-compatibility'); ?></div>
                    <div class="nc-package-header">
                        <h3><?php _e('Standard', 'numerology-compatibility'); ?></h3>
                        <div class="nc-price"><?php _e('Paid', 'numerology-compatibility'); ?></div>
                    </div>
                    <ul class="nc-features">
                        <li><?php _e('Full compatibility analysis', 'numerology-compatibility'); ?></li>
                        <li><?php _e('Relationship dynamics', 'numerology-compatibility'); ?></li>
                        <li><?php _e('1-2 year forecast', 'numerology-compatibility'); ?></li>
                        <li><?php _e('PDF download + email', 'numerology-compatibility'); ?></li>
                    </ul>
                    <button class="nc-btn nc-btn-primary nc-select-package" data-package="standard" data-tier="standard">
						<?php _e('Get Standard Report', 'numerology-compatibility'); ?>
                    </button>
                </div>

                <div class="nc-package" data-package="premium" data-tier="premium">
                    <div class="nc-package-header">
                        <h3><?php _e('Premium', 'numerology-compatibility'); ?></h3>
                        <div class="nc-price"><?php _e('Paid', 'numerology-compatibility'); ?></div>
                    </div>
                    <ul class="nc-features">
                        <li><?php _e('Everything in Standard', 'numerology-compatibility'); ?></li>
                        <li><?php _e('Karmic connections', 'numerology-compatibility'); ?></li>
                        <li><?php _e('10-20 year forecast', 'numerology-compatibility'); ?></li>
                        <li><?php _e('Action recommendations', 'numerology-compatibility'); ?></li>
                        <li><?php _e('Priority support', 'numerology-compatibility'); ?></li>
                    </ul>
                    <button class="nc-btn nc-btn-primary nc-select-package" data-package="premium" data-tier="premium">
						<?php _e('Get Premium Report', 'numerology-compatibility'); ?>
                    </button>
                </div>
            </div>
        </div>
	<?php endif; ?>

    <!-- Step 3: Processing -->
    <div class="nc-step nc-step-3 nc-hidden" data-step="3">
        <div class="nc-processing">
            <div class="nc-spinner"></div>
            <h2 class="nc-processing-title"><?php _e('Processing...', 'numerology-compatibility'); ?></h2>
            <p class="nc-processing-message"><?php _e('Please wait...', 'numerology-compatibility'); ?></p>
        </div>
    </div>

    <!-- Step 4: Payment Pending (Verifying Payment) -->
    <div class="nc-step nc-step-4 nc-hidden" data-step="4">
        <div class="nc-pending">
            <div class="nc-spinner"></div>
            <h2><?php _e('Verifying Payment...', 'numerology-compatibility'); ?></h2>
            <p class="nc-pending-message"><?php _e('Please wait while we confirm your payment. This usually takes a few seconds.', 'numerology-compatibility'); ?></p>

            <div class="nc-pending-details">
                <p><small><?php _e('Do not close this page. You will be redirected automatically.', 'numerology-compatibility'); ?></small></p>
            </div>
        </div>
    </div>

    <!-- Step 5: PDF Generation In Progress -->
    <div class="nc-step nc-step-5 nc-hidden" data-step="5">
        <div class="nc-success nc-generating">
            <div class="nc-generating-icon">⏳</div>
            <h2><?php _e('In Progress!', 'numerology-compatibility'); ?></h2>
            <p class="nc-success-message"><?php _e('Your calculation is complete! PDF report is being generated and will be ready soon.', 'numerology-compatibility'); ?></p>

            <!-- PDF Download Link -->
            <div class="nc-pdf-download">
                <a href="#" id="nc-pdf-download-link" class="nc-btn nc-btn-primary nc-btn-large nc-hidden" target="_blank">
                    📄 <?php _e('Download PDF Report', 'numerology-compatibility'); ?>
                </a>
                <p class="nc-pdf-generating">
                    <?php _e('PDF is being generated... Please wait.', 'numerology-compatibility'); ?>
                </p>
            </div>

            <!-- Email Form (Optional) - показывается только когда PDF готов -->
            <div class="nc-email-form nc-hidden">
                <h3><?php _e('Send Report to Email?', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Optionally, you can receive this report via email:', 'numerology-compatibility'); ?></p>

                <form id="nc-send-email-form" class="nc-form">
                    <div class="nc-field">
                        <label for="email-after-calc"><?php _e('Email Address', 'numerology-compatibility'); ?></label>
                        <input type="email" id="email-after-calc" name="email"
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

            <div class="nc-form-actions nc-mt-2">
                <button type="button" class="nc-btn nc-btn-outline nc-btn-restart">
					<?php _e('Calculate Another', 'numerology-compatibility'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Step 6: Error -->
    <div class="nc-step nc-step-6 nc-hidden" data-step="6">
        <div class="nc-error-page">
            <div class="nc-error-icon">✕</div>
            <h2><?php _e('Oops! Something Went Wrong', 'numerology-compatibility'); ?></h2>
            <p class="nc-error-message"><?php _e('An error occurred. Please try again.', 'numerology-compatibility'); ?></p>

            <div class="nc-error-details">
                <p><?php _e('If the problem persists, please contact our support team with your payment confirmation.', 'numerology-compatibility'); ?></p>
            </div>

            <div class="nc-form-actions">
                <button type="button" class="nc-btn nc-btn-primary nc-btn-restart">
					<?php _e('Try Again', 'numerology-compatibility'); ?>
                </button>
            </div>
        </div>
    </div>
</div>