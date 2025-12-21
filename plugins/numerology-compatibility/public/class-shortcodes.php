<?php
// plugins/numerology-compatibility/public/class-shortcodes.php
namespace NC\PublicSite;

class Shortcodes {

	/**
	 * Register all shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode('numerology_compatibility', [$this, 'render_calculator']);
		add_shortcode('numerology_compatibility_v2', [$this, 'render_calculator_v2']);
		add_shortcode('numerology_result', [$this, 'render_result']);
		add_shortcode('numerology_pricing', [$this, 'render_pricing']);
		add_shortcode('numerology_gdpr', [$this, 'render_gdpr_tools']);
	}

	/**
	 * Counter for unique calculator IDs
	 */
	private static $instance_counter = 0;

	/**
	 * Render calculator shortcode (normal mode: dates first, then package selection)
	 */
	public function render_calculator($atts) {
		$attributes = shortcode_atts([
			'package' => 'auto',
			'show_prices' => 'yes',
			'language' => 'auto',
			'currency' => 'auto',
			'style' => 'modern',
			'theme' => ''  // '' or 'hero' for dark backgrounds
		], $atts);

		$mode = 'normal';
		self::$instance_counter++;
		$instance_id = self::$instance_counter;

		ob_start();
		include NC_PLUGIN_DIR . 'public/views/form-calculator.php';
		return ob_get_clean();
	}

	/**
	 * Render calculator shortcode v2 (reversed mode: package selection first, then dates)
	 * Flow: Step1 (Package) -> Step2 (Dates) -> Payment (if paid) -> PDF
	 */
	public function render_calculator_v2($atts) {
		$attributes = shortcode_atts([
			'package' => 'auto',
			'show_prices' => 'yes',
			'language' => 'auto',
			'currency' => 'auto',
			'style' => 'modern',
			'theme' => ''  // '' or 'hero' for dark backgrounds
		], $atts);

		$mode = 'reversed';
		self::$instance_counter++;
		$instance_id = self::$instance_counter;

		ob_start();
		include NC_PLUGIN_DIR . 'public/views/form-calculator.php';
		return ob_get_clean();
	}

	/**
	 * Render result page shortcode
	 * Displays calculation result and PDF download link
	 *
	 * URL parameters:
	 * - ?code={secret_code} - access by secret code
	 * - ?payment_success=1&payment_id={id} - after payment redirect
	 * - ?payment_cancelled=1 - payment was cancelled
	 */
	public function render_result($atts) {
		$attributes = shortcode_atts([
			'style' => 'modern'
		], $atts);

		ob_start();
		include NC_PLUGIN_DIR . 'public/views/view-result.php';
		return ob_get_clean();
	}

	/**
	 * Render pricing shortcode
	 */
	public function render_pricing($atts) {
		$attributes = shortcode_atts([
			'style' => 'cards',
			'highlight' => 'standard',
			'calculator_url' => '' // URL страницы с калькулятором (опционально)
		], $atts);

		// Если calculator_url не указан, используем якорь на текущей странице
		$base_url = !empty($attributes['calculator_url'])
			? esc_url($attributes['calculator_url'])
			: '#nc-calculator-wrapper';

		ob_start();
		?>
        <div class="nc-pricing-table nc-style-<?php echo esc_attr($attributes['style']); ?>">
            <!-- Free Package -->
            <div class="nc-pricing-card <?php echo $attributes['highlight'] === 'free' ? 'nc-featured' : ''; ?>">
                <h3><?php _e('Free', 'numerology-compatibility'); ?></h3>
                <div class="nc-price">$0</div>
                <ul class="nc-features">
                    <li><?php _e('Basic compatibility score', 'numerology-compatibility'); ?></li>
                    <li><?php _e('3 positions analysis', 'numerology-compatibility'); ?></li>
                    <li><?php _e('PDF via email', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo $base_url; ?>" class="nc-btn nc-btn-outline nc-package-link" data-package="free">
					<?php _e('Get Free Report', 'numerology-compatibility'); ?>
                </a>
            </div>

            <!-- Standard Package -->
            <div class="nc-pricing-card <?php echo $attributes['highlight'] === 'standard' ? 'nc-featured' : ''; ?>">
				<?php if ($attributes['highlight'] === 'standard'): ?>
                    <div class="nc-badge"><?php _e('Most Popular', 'numerology-compatibility'); ?></div>
				<?php endif; ?>
                <h3><?php _e('Standard', 'numerology-compatibility'); ?></h3>
                <div class="nc-price">$9.99</div>
                <ul class="nc-features">
                    <li><?php _e('Full compatibility matrix', 'numerology-compatibility'); ?></li>
                    <li><?php _e('7 positions analysis', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Personal analysis', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Relationship dynamics', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Advanced PDF report', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo $base_url; ?>" class="nc-btn nc-btn-primary nc-package-link" data-package="standard">
					<?php _e('Get Standard Report', 'numerology-compatibility'); ?>
                </a>
            </div>

            <!-- Premium Package -->
            <div class="nc-pricing-card <?php echo $attributes['highlight'] === 'premium' ? 'nc-featured' : ''; ?>">
                <h3><?php _e('Premium', 'numerology-compatibility'); ?></h3>
                <div class="nc-price">$19.99</div>
                <ul class="nc-features">
                    <li><?php _e('Everything in Standard', 'numerology-compatibility'); ?></li>
                    <li><?php _e('9 positions analysis', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Karmic connections', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Timeline predictions', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Color therapy recommendations', 'numerology-compatibility'); ?></li>
                    <li><?php _e('Premium PDF with charts', 'numerology-compatibility'); ?></li>
                </ul>
                <a href="<?php echo $base_url; ?>" class="nc-btn nc-btn-primary nc-package-link" data-package="premium">
					<?php _e('Get Premium Report', 'numerology-compatibility'); ?>
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