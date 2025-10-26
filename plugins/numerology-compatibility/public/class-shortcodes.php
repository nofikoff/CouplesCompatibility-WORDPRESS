<?php
// plugins/numerology-compatibility/public/class-shortcodes.php
namespace NC\PublicSite;

class Shortcodes {

	/**
	 * Register all shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode('numerology_compatibility', [$this, 'render_calculator']);
		add_shortcode('numerology_pricing', [$this, 'render_pricing']);
		add_shortcode('numerology_gdpr', [$this, 'render_gdpr_tools']);
	}

	/**
	 * Render calculator shortcode
	 */
	public function render_calculator($atts) {
		$attributes = shortcode_atts([
			'package' => 'auto',
			'show_prices' => 'yes',
			'language' => 'auto',
			'currency' => 'auto',
			'style' => 'modern'
		], $atts);

		ob_start();
		include NC_PLUGIN_DIR . 'public/views/form-calculator.php';
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
                    <li><?php _e('PDF via email', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo home_url('/calculator?package=free'); ?>" class="nc-btn nc-btn-outline">
					<?php _e('Get Free Report', 'numerology-compatibility'); ?>
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
                    <li><?php _e('Detailed PDF report', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Email delivery', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo home_url('/calculator?package=light'); ?>" class="nc-btn nc-btn-primary">
					<?php _e('Get Light Report', 'numerology-compatibility'); ?>
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
					<?php _e('Get Pro Report', 'numerology-compatibility'); ?>
                </a>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render GDPR tools shortcode
	 */
	public function render_gdpr_tools($atts) {
		ob_start();
		?>
        <div class="nc-gdpr-tools">
            <h2><?php _e('Your Data & Privacy', 'numerology-compatibility'); ?></h2>

            <div class="nc-gdpr-section">
                <h3><?php _e('Export Your Data', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Download all your calculation data in JSON format', 'numerology-compatibility'); ?></p>

                <form id="nc-export-form" class="nc-gdpr-form">
                    <input type="email" name="email" placeholder="<?php _e('Your email address', 'numerology-compatibility'); ?>" required>
                    <button type="submit" class="nc-btn nc-btn-secondary">
						<?php _e('Export Data', 'numerology-compatibility'); ?>
                    </button>
                </form>
            </div>

            <div class="nc-gdpr-section nc-danger-zone">
                <h3><?php _e('Delete Your Data', 'numerology-compatibility'); ?></h3>
                <p><?php _e('Permanently delete all your calculation data', 'numerology-compatibility'); ?></p>

                <div class="nc-alert nc-alert-danger">
                    <strong><?php _e('Warning!', 'numerology-compatibility'); ?></strong>
					<?php _e('This action cannot be undone. All your data will be permanently deleted.', 'numerology-compatibility'); ?>
                </div>

                <form id="nc-delete-form" class="nc-gdpr-form">
                    <input type="email" name="email" placeholder="<?php _e('Your email address', 'numerology-compatibility'); ?>" required>
                    <input type="text" name="confirmation" placeholder="<?php _e('Type DELETE to confirm', 'numerology-compatibility'); ?>" required>
                    <button type="submit" class="nc-btn nc-btn-danger">
						<?php _e('Delete All My Data', 'numerology-compatibility'); ?>
                    </button>
                </form>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Export data
                $('#nc-export-form').on('submit', function(e) {
                    e.preventDefault();

                    $.ajax({
                        url: nc_public.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'nc_export_data',
                            email: $(this).find('input[name="email"]').val(),
                            nonce: nc_public.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.href = response.data.download_url;
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                });

                // Delete data
                $('#nc-delete-form').on('submit', function(e) {
                    e.preventDefault();

                    if (!confirm('<?php _e('Are you absolutely sure? This cannot be undone.', 'numerology-compatibility'); ?>')) {
                        return;
                    }

                    $.ajax({
                        url: nc_public.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'nc_delete_data',
                            email: $(this).find('input[name="email"]').val(),
                            confirmation: $(this).find('input[name="confirmation"]').val(),
                            nonce: nc_public.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                $('#nc-delete-form')[0].reset();
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                });
            });
        </script>
		<?php
		return ob_get_clean();
	}
}