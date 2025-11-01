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
        <h2><?php _e('Discover Your Relationship Compatibility', 'numerology-compatibility'); ?></h2>
        <p class="nc-subtitle"><?php _e('Enter birth dates to reveal deep insights based on Numerology', 'numerology-compatibility'); ?></p>

        <form id="nc-calculator-form" class="nc-form">

            <!-- Email Field -->
            <div class="nc-form-group nc-email-group">
                <h3><?php _e('Your Email', 'numerology-compatibility'); ?></h3>
                <p class="nc-help-text"><?php _e('PDF report will be sent to this address', 'numerology-compatibility'); ?></p>

                <div class="nc-field nc-required">
                    <label for="email"><?php _e('Email Address', 'numerology-compatibility'); ?></label>
                    <input type="email" id="email" name="email" required
                           placeholder="your@email.com"
                           autocomplete="email">
                    <span class="nc-error-message"></span>
                </div>
            </div>

            <div class="nc-form-row">
                <!-- Partner 1 -->
                <div class="nc-form-group nc-partner-1">
                    <h3><?php _e('Partner 1', 'numerology-compatibility'); ?></h3>

                    <div class="nc-field nc-required">
                        <label for="person1_date"><?php _e('Date of Birth', 'numerology-compatibility'); ?></label>
                        <input type="date" id="person1_date" name="person1_date" required
                               max="<?php echo date('Y-m-d'); ?>"
                               min="1900-01-01">
                        <span class="nc-error-message"></span>
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
                        <span class="nc-error-message"></span>
                    </div>
                </div>
            </div>

            <!-- Consent checkboxes for calculation -->
            <div class="nc-consent-group">
                <div class="nc-alert nc-alert-warning">
                    <strong>⚠️ <?php _e("You're entering another person's data:", 'numerology-compatibility'); ?></strong>
                </div>

                <div class="nc-checkbox-group">
                    <label class="nc-checkbox">
                        <input type="checkbox" name="data_consent" id="data_consent" required>
                        <span><?php _e("I have permission to use this person's birth data for compatibility analysis", 'numerology-compatibility'); ?> *</span>
                    </label>

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
        <div class="nc-step nc-step-2" data-step="2" style="display:none;">
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
    <div class="nc-step nc-step-3" data-step="3" style="display:none;">
        <div class="nc-processing">
            <div class="nc-spinner"></div>
            <h2 class="nc-processing-title"><?php _e('Processing...', 'numerology-compatibility'); ?></h2>
            <p class="nc-processing-message"><?php _e('Please wait...', 'numerology-compatibility'); ?></p>
        </div>
    </div>

    <!-- Step 4: Payment Pending (Verifying Payment) -->
    <div class="nc-step nc-step-4" data-step="4" style="display:none;">
        <div class="nc-pending">
            <div class="nc-spinner"></div>
            <h2><?php _e('Verifying Payment...', 'numerology-compatibility'); ?></h2>
            <p class="nc-pending-message"><?php _e('Please wait while we confirm your payment. This usually takes a few seconds.', 'numerology-compatibility'); ?></p>

            <div class="nc-pending-details">
                <p><small><?php _e('Do not close this page. You will be redirected automatically.', 'numerology-compatibility'); ?></small></p>
            </div>
        </div>
    </div>

    <!-- Step 5: Success -->
    <div class="nc-step nc-step-5" data-step="5" style="display:none;">
        <div class="nc-success">
            <div class="nc-success-icon">✓</div>
            <h2><?php _e('Success!', 'numerology-compatibility'); ?></h2>
            <p class="nc-success-message"><?php _e('Your compatibility report has been generated and sent to your email.', 'numerology-compatibility'); ?></p>

            <div class="nc-success-details">
                <p><strong><?php _e('What happens next:', 'numerology-compatibility'); ?></strong></p>
                <ul>
                    <li><?php _e('Check your email inbox (and spam folder)', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Your PDF report should arrive within 5 minutes', 'numerology-compatibility'); ?></li>
                    <li><?php _e('If you don\'t receive it, contact our support', 'numerology-compatibility'); ?></li>
                </ul>
            </div>

            <div class="nc-form-actions">
                <a href="<?php echo home_url('/calculator'); ?>" class="nc-btn nc-btn-primary">
					<?php _e('Calculate Another', 'numerology-compatibility'); ?>
                </a>
            </div>
        </div>
    </div>
</div>