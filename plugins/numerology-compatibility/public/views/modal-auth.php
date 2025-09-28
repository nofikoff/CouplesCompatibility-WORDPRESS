<?php
/**
 * Authentication modal template
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="nc-auth-modal" class="nc-modal" style="display:none;">
    <div class="nc-modal-overlay"></div>
    <div class="nc-modal-content">
        <button class="nc-modal-close">&times;</button>

        <div class="nc-auth-container">
            <!-- Tabs -->
            <div class="nc-auth-tabs">
                <button class="nc-tab nc-tab-active" data-tab="login"><?php _e('Sign In', 'numerology-compatibility'); ?></button>
                <button class="nc-tab" data-tab="register"><?php _e('Sign Up', 'numerology-compatibility'); ?></button>
            </div>

            <!-- Login Tab -->
            <div class="nc-tab-content nc-tab-login nc-active">
                <h3><?php _e('Welcome Back', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Sign in to access your reports and history', 'numerology-compatibility'); ?></p>

                <form id="nc-login-form" class="nc-auth-form">
                    <div class="nc-field">
                        <label for="login_email"><?php _e('Email', 'numerology-compatibility'); ?></label>
                        <input type="email" id="login_email" name="email" required>
                        <span class="nc-error-message"></span>
                    </div>

                    <div class="nc-field">
                        <label for="login_password"><?php _e('Password', 'numerology-compatibility'); ?></label>
                        <input type="password" id="login_password" name="password" required>
                        <span class="nc-error-message"></span>
                    </div>

                    <div class="nc-field-row">
                        <label class="nc-checkbox">
                            <input type="checkbox" name="remember" id="login_remember">
                            <span><?php _e('Remember me', 'numerology-compatibility'); ?></span>
                        </label>
                        <a href="#" class="nc-forgot-password"><?php _e('Forgot Password?', 'numerology-compatibility'); ?></a>
                    </div>

                    <button type="submit" class="nc-btn nc-btn-primary nc-btn-block">
                        <?php _e('Sign In', 'numerology-compatibility'); ?>
                    </button>
                </form>

                <div class="nc-divider">
                    <span><?php _e('OR', 'numerology-compatibility'); ?></span>
                </div>

                <button class="nc-btn nc-btn-google nc-btn-block" id="nc-google-signin">
                    <img src="<?php echo NC_PLUGIN_URL; ?>public/assets/images/google-icon.svg" alt="Google">
                    <?php _e('Sign in with Google', 'numerology-compatibility'); ?>
                </button>

                <p class="nc-auth-switch">
                    <?php _e("Don't have an account?", 'numerology-compatibility'); ?>
                    <a href="#" data-tab="register"><?php _e('Sign Up', 'numerology-compatibility'); ?></a>
                </p>
            </div>

            <!-- Register Tab -->
            <div class="nc-tab-content nc-tab-register">
                <h3><?php _e('Create Your Account', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Join to save your reports and unlock full features', 'numerology-compatibility'); ?></p>

                <form id="nc-register-form" class="nc-auth-form">
                    <div class="nc-field">
                        <label for="register_name"><?php _e('Full Name', 'numerology-compatibility'); ?></label>
                        <input type="text" id="register_name" name="name">
                        <span class="nc-error-message"></span>
                    </div>

                    <div class="nc-field">
                        <label for="register_email"><?php _e('Email', 'numerology-compatibility'); ?></label>
                        <input type="email" id="register_email" name="email" required>
                        <span class="nc-error-message"></span>
                    </div>

                    <div class="nc-field">
                        <label for="register_password"><?php _e('Password', 'numerology-compatibility'); ?></label>
                        <input type="password" id="register_password" name="password" required minlength="8">
                        <small><?php _e('Minimum 8 characters', 'numerology-compatibility'); ?></small>
                        <span class="nc-error-message"></span>
                    </div>

                    <div class="nc-field">
                        <label for="register_password_confirmation"><?php _e('Confirm Password', 'numerology-compatibility'); ?></label>
                        <input type="password" id="register_password_confirmation" name="password_confirmation" required>
                        <span class="nc-error-message"></span>
                    </div>

                    <!-- Required Consent Checkboxes -->
                    <div class="nc-consent-group">
                        <label class="nc-checkbox nc-required">
                            <input type="checkbox" name="age_consent" id="age_consent" required>
                            <span><?php _e('I confirm that I am at least 18 years old', 'numerology-compatibility'); ?> *</span>
                        </label>

                        <label class="nc-checkbox nc-required">
                            <input type="checkbox" name="terms_consent" id="terms_consent" required>
                            <span>
                                <?php printf(
                                    __('I agree to the %s and %s', 'numerology-compatibility'),
                                    '<a href="/terms" target="_blank">' . __('Terms of Service', 'numerology-compatibility') . '</a>',
                                    '<a href="/privacy" target="_blank">' . __('Privacy Policy', 'numerology-compatibility') . '</a>'
                                ); ?> *
                            </span>
                        </label>

                        <label class="nc-checkbox">
                            <input type="checkbox" name="marketing_consent" id="marketing_consent">
                            <span><?php _e('Send me numerology insights and special offers (you can unsubscribe anytime)', 'numerology-compatibility'); ?></span>
                        </label>
                    </div>

                    <button type="submit" class="nc-btn nc-btn-primary nc-btn-block">
                        <?php _e('Create Account', 'numerology-compatibility'); ?>
                    </button>
                </form>

                <div class="nc-divider">
                    <span><?php _e('OR', 'numerology-compatibility'); ?></span>
                </div>

                <button class="nc-btn nc-btn-google nc-btn-block" id="nc-google-signup">
                    <img src="<?php echo NC_PLUGIN_URL; ?>public/assets/images/google-icon.svg" alt="Google">
                    <?php _e('Sign up with Google', 'numerology-compatibility'); ?>
                </button>

                <p class="nc-auth-switch">
                    <?php _e('Already have an account?', 'numerology-compatibility'); ?>
                    <a href="#" data-tab="login"><?php _e('Sign In', 'numerology-compatibility'); ?></a>
                </p>
            </div>

            <!-- Forgot Password Tab -->
            <div class="nc-tab-content nc-tab-forgot" style="display:none;">
                <h3><?php _e('Reset Your Password', 'numerology-compatibility'); ?></h3>
                <p><?php _e("Enter your email and we'll send you a reset link", 'numerology-compatibility'); ?></p>

                <form id="nc-forgot-form" class="nc-auth-form">
                    <div class="nc-field">
                        <label for="forgot_email"><?php _e('Email', 'numerology-compatibility'); ?></label>
                        <input type="email" id="forgot_email" name="email" required>
                        <span class="nc-error-message"></span>
                    </div>

                    <button type="submit" class="nc-btn nc-btn-primary nc-btn-block">
                        <?php _e('Send Reset Link', 'numerology-compatibility'); ?>
                    </button>
                </form>

                <p class="nc-auth-switch">
                    <a href="#" data-tab="login"><?php _e('Back to Sign In', 'numerology-compatibility'); ?></a>
                </p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div class="nc-auth-messages">
            <div class="nc-message nc-message-success" style="display:none;"></div>
            <div class="nc-message nc-message-error" style="display:none;"></div>
        </div>
    </div>
</div>