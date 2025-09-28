<?php
/**
 * Calculator form template
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
$package_type = $attributes['package'] ?? 'free';
$show_prices = $attributes['show_prices'] ?? true;
$require_auth = $attributes['require_auth'] ?? true;
?>

    <div id="nc-calculator-wrapper" class="nc-calculator" data-package="<?php echo esc_attr($package_type); ?>">

        <!-- Step 1: Input Form -->
        <div class="nc-step nc-step-1 nc-active" data-step="1">
            <h2><?php _e('Discover Your Relationship Compatibility', 'numerology-compatibility'); ?></h2>
            <p class="nc-subtitle"><?php _e('Enter birth dates to reveal deep insights based on Numerology', 'numerology-compatibility'); ?></p>

            <form id="nc-calculator-form" class="nc-form">
                <div class="nc-form-row">
                    <!-- Partner 1 -->
                    <div class="nc-form-group nc-partner-1">
                        <h3><?php _e('Partner 1', 'numerology-compatibility'); ?></h3>

                        <div class="nc-field">
                            <label for="person1_name"><?php _e('Name (optional)', 'numerology-compatibility'); ?></label>
                            <input type="text" id="person1_name" name="person1_name" placeholder="<?php _e('Enter name', 'numerology-compatibility'); ?>">
                        </div>

                        <div class="nc-field nc-required">
                            <label for="person1_date"><?php _e('Date of Birth', 'numerology-compatibility'); ?> *</label>
                            <input type="date" id="person1_date" name="person1_date" required
                                   max="<?php echo date('Y-m-d'); ?>"
                                   min="1900-01-01">
                            <span class="nc-error-message"></span>
                        </div>

                        <div class="nc-field nc-advanced" style="display:none;">
                            <label for="person1_time"><?php _e('Time of Birth (optional)', 'numerology-compatibility'); ?></label>
                            <input type="time" id="person1_time" name="person1_time">
                        </div>

                        <div class="nc-field nc-advanced" style="display:none;">
                            <label for="person1_place"><?php _e('Place of Birth (optional)', 'numerology-compatibility'); ?></label>
                            <input type="text" id="person1_place" name="person1_place" placeholder="<?php _e('City, Country', 'numerology-compatibility'); ?>">
                        </div>
                    </div>

                    <!-- Partner 2 -->
                    <div class="nc-form-group nc-partner-2">
                        <h3><?php _e('Partner 2', 'numerology-compatibility'); ?></h3>

                        <div class="nc-field">
                            <label for="person2_name"><?php _e('Name (optional)', 'numerology-compatibility'); ?></label>
                            <input type="text" id="person2_name" name="person2_name" placeholder="<?php _e('Enter name', 'numerology-compatibility'); ?>">
                        </div>

                        <div class="nc-field nc-required">
                            <label for="person2_date"><?php _e('Date of Birth', 'numerology-compatibility'); ?> *</label>
                            <input type="date" id="person2_date" name="person2_date" required
                                   max="<?php echo date('Y-m-d'); ?>"
                                   min="1900-01-01">
                            <span class="nc-error-message"></span>
                        </div>

                        <div class="nc-field nc-advanced" style="display:none;">
                            <label for="person2_time"><?php _e('Time of Birth (optional)', 'numerology-compatibility'); ?></label>
                            <input type="time" id="person2_time" name="person2_time">
                        </div>

                        <div class="nc-field nc-advanced" style="display:none;">
                            <label for="person2_place"><?php _e('Place of Birth (optional)', 'numerology-compatibility'); ?></label>
                            <input type="text" id="person2_place" name="person2_place" placeholder="<?php _e('City, Country', 'numerology-compatibility'); ?>">
                        </div>
                    </div>
                </div>

                <div class="nc-toggle-advanced">
                    <a href="#" id="nc-toggle-advanced"><?php _e('+ Show advanced options', 'numerology-compatibility'); ?></a>
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
                    <div class="nc-package" data-package="free">
                        <div class="nc-package-header">
                            <h3><?php _e('Free', 'numerology-compatibility'); ?></h3>
                            <div class="nc-price">$0</div>
                        </div>
                        <ul class="nc-features">
                            <li><?php _e('Basic compatibility score', 'numerology-compatibility'); ?></li>
                            <li><?php _e('Key personality insights', 'numerology-compatibility'); ?></li>
                            <li><?php _e('Instant online result', 'numerology-compatibility'); ?></li>
                        </ul>
                        <button class="nc-btn nc-btn-outline nc-select-package" data-package="free">
                            <?php _e('Select Free', 'numerology-compatibility'); ?>
                        </button>
                    </div>

                    <div class="nc-package nc-package-featured" data-package="light">
                        <div class="nc-badge"><?php _e('Most Popular', 'numerology-compatibility'); ?></div>
                        <div class="nc-package-header">
                            <h3><?php _e('Light', 'numerology-compatibility'); ?></h3>
                            <div class="nc-price">$<?php echo get_option('nc_price_light', '19'); ?></div>
                        </div>
                        <ul class="nc-features">
                            <li><?php _e('Full compatibility analysis', 'numerology-compatibility'); ?></li>
                            <li><?php _e('Relationship dynamics', 'numerology-compatibility'); ?></li>
                            <li><?php _e('1-2 year forecast', 'numerology-compatibility'); ?></li>
                            <li><?php _e('PDF download', 'numerology-compatibility'); ?></li>
                        </ul>
                        <button class="nc-btn nc-btn-primary nc-select-package" data-package="light">
                            <?php _e('Select Light', 'numerology-compatibility'); ?>
                        </button>
                    </div>

                    <div class="nc-package" data-package="pro">
                        <div class="nc-package-header">
                            <h3><?php _e('Pro', 'numerology-compatibility'); ?></h3>
                            <div class="nc-price">$<?php echo get_option('nc_price_pro', '49'); ?></div>
                        </div>
                        <ul class="nc-features">
                            <li><?php _e('Everything in Light', 'numerology-compatibility'); ?></li>
                            <li><?php _e('Karmic connections', 'numerology-compatibility'); ?></li>
                            <li><?php _e('10-20 year forecast', 'numerology-compatibility'); ?></li>
                            <li><?php _e('Action recommendations', 'numerology-compatibility'); ?></li>
                            <li><?php _e('Priority support', 'numerology-compatibility'); ?></li>
                        </ul>
                        <button class="nc-btn nc-btn-primary nc-select-package" data-package="pro">
                            <?php _e('Select Pro', 'numerology-compatibility'); ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Step 3: Authentication (if needed) -->
        <div class="nc-step nc-step-3" data-step="3" style="display:none;">
            <!-- Will be populated by auth modal -->
        </div>

        <!-- Step 4: Payment (if needed) -->
        <div class="nc-step nc-step-4" data-step="4" style="display:none;">
            <h2><?php _e('Complete Your Payment', 'numerology-compatibility'); ?></h2>

            <div class="nc-payment-summary">
                <h3><?php _e('Order Summary', 'numerology-compatibility'); ?></h3>
                <div class="nc-summary-item">
                    <span class="nc-item-name"></span>
                    <span class="nc-item-price"></span>
                </div>
            </div>

            <div id="nc-stripe-payment-element">
                <!-- Stripe Payment Element will be mounted here -->
            </div>

            <div class="nc-payment-errors" role="alert"></div>

            <div class="nc-form-actions">
                <button id="nc-submit-payment" class="nc-btn nc-btn-primary nc-btn-large">
                    <span class="nc-btn-text"><?php _e('Complete Payment', 'numerology-compatibility'); ?></span>
                    <span class="nc-btn-loading" style="display:none;"><?php _e('Processing...', 'numerology-compatibility'); ?></span>
                </button>
            </div>

            <div class="nc-security-badges">
                <img src="<?php echo NC_PLUGIN_URL; ?>public/assets/images/stripe-badge.png" alt="Stripe">
                <span><?php _e('Secure payment powered by Stripe', 'numerology-compatibility'); ?></span>
            </div>
        </div>

        <!-- Step 5: Processing -->
        <div class="nc-step nc-step-5" data-step="5" style="display:none;">
            <div class="nc-processing">
                <div class="nc-spinner"></div>
                <h2><?php _e('Calculating Your Compatibility...', 'numerology-compatibility'); ?></h2>
                <p><?php _e('Analyzing numerological patterns and creating your personalized report', 'numerology-compatibility'); ?></p>
            </div>
        </div>

        <!-- Step 6: Result -->
        <div class="nc-step nc-step-6" data-step="6" style="display:none;">
            <div class="nc-result">
                <!-- Results will be populated here -->
            </div>
        </div>
    </div>

    <!-- Include auth modal -->
<?php include NC_PLUGIN_DIR . 'public/views/modal-auth.php'; ?>