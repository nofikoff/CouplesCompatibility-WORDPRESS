<?php
namespace NC\PublicSite;

class Shortcodes {

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('numerology_compatibility', [$this, 'render_calculator']);
        add_shortcode('numerology_dashboard', [$this, 'render_dashboard']);
        add_shortcode('numerology_pricing', [$this, 'render_pricing']);
        add_shortcode('numerology_login', [$this, 'render_login']);
    }

    /**
     * Render calculator shortcode
     */
    public function render_calculator($atts) {
        $attributes = shortcode_atts([
            'package' => 'auto',
            'show_prices' => 'yes',
            'require_auth' => 'yes',
            'redirect_after' => '',
            'language' => 'auto',
            'currency' => 'auto',
            'style' => 'modern'
        ], $atts);

        ob_start();
        include NC_PLUGIN_DIR . 'public/views/form-calculator.php';
        return ob_get_clean();
    }

    /**
     * Render dashboard shortcode
     */
    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to view your dashboard.', 'numerology-compatibility') . '</p>';
        }

        ob_start();
        include NC_PLUGIN_DIR . 'public/views/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Render pricing shortcode
     */
    public function render_pricing($atts) {
        $attributes = shortcode_atts([
            'style' => 'cards',
            'highlight' => 'light'
        ], $atts);

        ob_start();
        ?>
        <div class="nc-pricing-table nc-style-<?php echo esc_attr($attributes['style']); ?>">
            <div class="nc-pricing-card <?php echo $attributes['highlight'] === 'free' ? 'nc-featured' : ''; ?>">
                <h3><?php _e('Free', 'numerology-compatibility'); ?></h3>
                <div class="nc-price">$0</div>
                <ul class="nc-features">
                    <li><?php _e('Basic compatibility score', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Key insights', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Online viewing', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo home_url('/calculator?package=free'); ?>" class="nc-btn nc-btn-outline">
                    <?php _e('Start Free', 'numerology-compatibility'); ?>
                </a>
            </div>

            <div class="nc-pricing-card <?php echo $attributes['highlight'] === 'light' ? 'nc-featured' : ''; ?>">
                <?php if ($attributes['highlight'] === 'light'): ?>
                    <div class="nc-badge"><?php _e('Most Popular', 'numerology-compatibility'); ?></div>
                <?php endif; ?>
                <h3><?php _e('Light', 'numerology-compatibility'); ?></h3>
                <div class="nc-price">$<?php echo get_option('nc_price_light', 19); ?></div>
                <ul class="nc-features">
                    <li><?php _e('Full compatibility analysis', 'numerology-compatibility'); ?></li>
                    <li><?php _e('1-2 year forecast', 'numerology-compatibility'); ?></li>
                    <li><?php _e('PDF download', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Email delivery', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo home_url('/calculator?package=light'); ?>" class="nc-btn nc-btn-primary">
                    <?php _e('Get Light', 'numerology-compatibility'); ?>
                </a>
            </div>

            <div class="nc-pricing-card <?php echo $attributes['highlight'] === 'pro' ? 'nc-featured' : ''; ?>">
                <h3><?php _e('Pro', 'numerology-compatibility'); ?></h3>
                <div class="nc-price">$<?php echo get_option('nc_price_pro', 49); ?></div>
                <ul class="nc-features">
                    <li><?php _e('Everything in Light', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Karmic connections', 'numerology-compatibility'); ?></li>
                    <li><?php _e('10-20 year forecast', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Action recommendations', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Priority support', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo home_url('/calculator?package=pro'); ?>" class="nc-btn nc-btn-primary">
                    <?php _e('Get Pro', 'numerology-compatibility'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render login shortcode
     */
    public function render_login($atts) {
        if (is_user_logged_in()) {
            return '<p>' . __('You are already logged in.', 'numerology-compatibility') . ' <a href="' . home_url('/dashboard') . '">' . __('Go to Dashboard', 'numerology-compatibility') . '</a></p>';
        }

        ob_start();
        include NC_PLUGIN_DIR . 'public/views/modal-auth.php';
        ?>
        <script>
            jQuery(document).ready(function($) {
                // Show auth modal immediately
                $('#nc-auth-modal').show();
                $('.nc-modal-close').hide(); // Hide close button for dedicated login page
            });
        </script>
        <?php
        return ob_get_clean();
    }
}