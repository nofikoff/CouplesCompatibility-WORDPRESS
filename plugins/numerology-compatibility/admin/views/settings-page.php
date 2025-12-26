
<?php
/**
 * Admin settings page template
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$active_tab = $_GET['tab'] ?? 'general';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Shortcode Info -->
    <div class="notice notice-success" style="padding: 15px; margin-top: 20px; border-left: 4px solid #46b450;">
        <h3 style="margin-top: 0;">
            <?php _e('🎯 Available Shortcodes', 'numerology-compatibility'); ?>
        </h3>

        <!-- Standard Shortcode -->
        <div style="margin-bottom: 15px;">
            <p style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">
                <?php _e('Standard Flow (Dates → Package Selection):', 'numerology-compatibility'); ?>
            </p>
            <p style="background: #fff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 16px; font-weight: bold; color: #2271b1; margin-bottom: 5px;">
                [numerology_compatibility]
            </p>
            <p style="font-size: 13px; color: #666; margin: 0;">
                <?php _e('User enters birth dates first, then selects a package (Free/Standard/Premium).', 'numerology-compatibility'); ?>
            </p>
        </div>

        <!-- Reversed Shortcode (v2) -->
        <div style="margin-bottom: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <p style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">
                <?php _e('Landing Page Flow (Package Selection → Dates):', 'numerology-compatibility'); ?>
            </p>
            <p style="background: #fff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 16px; font-weight: bold; color: #9b59b6; margin-bottom: 5px;">
                [numerology_compatibility_v2]
            </p>
            <p style="font-size: 13px; color: #666; margin: 0;">
                <?php _e('User selects a package first, then enters birth dates. Ideal for landing pages with pre-selected offers.', 'numerology-compatibility'); ?>
            </p>
        </div>

        <!-- Result Shortcode -->
        <div style="margin-bottom: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <p style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">
                <?php _e('Result Page (PDF Download):', 'numerology-compatibility'); ?>
            </p>
            <p style="background: #fff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 16px; font-weight: bold; color: #27ae60; margin-bottom: 5px;">
                [numerology_result]
            </p>
            <p style="font-size: 13px; color: #666; margin: 0;">
                <?php _e('Displays calculation result and PDF download. Place on a separate page and set its URL in "Result Page URL" setting below.', 'numerology-compatibility'); ?>
            </p>
        </div>

        <!-- Shortcode Attributes -->
        <div style="padding-top: 10px; border-top: 1px solid #ddd;">
            <p style="font-size: 14px; margin-bottom: 5px; font-weight: 600;">
                <?php _e('Optional Attributes:', 'numerology-compatibility'); ?>
            </p>
            <ul style="font-size: 13px; color: #666; margin: 0; padding-left: 20px;">
                <li><code>package="auto|free|standard|premium"</code> - <?php _e('Pre-select package (default: auto)', 'numerology-compatibility'); ?></li>
                <li><code>show_prices="yes|no"</code> - <?php _e('Show price labels (default: yes)', 'numerology-compatibility'); ?></li>
                <li><code>language="auto|en|ru|uk"</code> - <?php _e('Force language (default: auto)', 'numerology-compatibility'); ?></li>
            </ul>
            <p style="font-size: 13px; color: #666; margin-top: 10px; margin-bottom: 0;">
                <?php _e('Example:', 'numerology-compatibility'); ?> <code>[numerology_compatibility_v2 package="premium"]</code>
            </p>
        </div>
    </div>

    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="?page=nc-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'numerology-compatibility'); ?>
        </a>
        <a href="?page=nc-settings&tab=api" class="nav-tab <?php echo $active_tab == 'api' ? 'nav-tab-active' : ''; ?>">
            <?php _e('API Configuration', 'numerology-compatibility'); ?>
        </a>
        <a href="?page=nc-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Advanced', 'numerology-compatibility'); ?>
        </a>
    </nav>

    <form method="post" action="options.php" class="nc-settings-form">
        <?php settings_fields('nc_settings_' . $active_tab); ?>

        <!-- General Tab -->
        <?php if ($active_tab == 'general'): ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Result Page URL', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="url" name="nc_result_page_url" value="<?php echo esc_attr(get_option('nc_result_page_url', '')); ?>" class="regular-text" placeholder="https://example.com/compatibility-result/">
                        <p class="description">
                            <?php _e('URL of the page with [numerology_result] shortcode. After payment, users will be redirected here.', 'numerology-compatibility'); ?>
                            <br>
                            <?php _e('For multilingual sites: this page must have translated versions linked via Polylang. Users will be redirected to the localized version based on their language.', 'numerology-compatibility'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 0;"><?php _e('Package Pricing (USD)', 'numerology-compatibility'); ?></h3>
                    </th>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Standard Price', 'numerology-compatibility'); ?></th>
                    <td>
                        <span style="font-size: 16px; margin-right: 5px;">$</span>
                        <input type="number" name="nc_price_standard" value="<?php echo esc_attr(get_option('nc_price_standard', '9.99')); ?>" class="small-text" step="0.01" min="0">
                        <p class="description"><?php _e('Price for Standard report (e.g., 9.99)', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Premium Price', 'numerology-compatibility'); ?></th>
                    <td>
                        <span style="font-size: 16px; margin-right: 5px;">$</span>
                        <input type="number" name="nc_price_premium" value="<?php echo esc_attr(get_option('nc_price_premium', '19.99')); ?>" class="small-text" step="0.01" min="0">
                        <p class="description"><?php _e('Price for Premium report (e.g., 19.99)', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- API Configuration Tab -->
        <?php if ($active_tab == 'api'): ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Backend API URL', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="url" name="nc_api_url" value="<?php echo esc_attr(get_option('nc_api_url', 'https://api.your-domain.com')); ?>" class="regular-text">
                        <p class="description"><?php _e('Your Laravel backend API URL', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('API Key', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="text" name="nc_api_key" value="<?php echo esc_attr(get_option('nc_api_key')); ?>" class="regular-text">
                        <p class="description"><?php _e('Your unique API key from the backend', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Connection Test', 'numerology-compatibility'); ?></th>
                    <td>
                        <button type="button" id="nc-test-connection" class="button">
                            <?php _e('Test Connection', 'numerology-compatibility'); ?>
                        </button>
                        <span id="nc-test-result" style="margin-left: 10px;"></span>
                    </td>
                </tr>
            </table>
        <?php endif; ?>


        <!-- Advanced Tab -->
        <?php if ($active_tab == 'advanced'): ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Delete Data on Uninstall', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_delete_on_uninstall" value="1" <?php checked(get_option('nc_delete_on_uninstall', 0)); ?>>
                            <?php _e('Remove all plugin settings when uninstalled', 'numerology-compatibility'); ?>
                        </label>
                        <p class="description">
                            <?php _e('All calculation data is stored on the backend API.', 'numerology-compatibility'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <?php submit_button(); ?>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Test API connection
        $('#nc-test-connection').on('click', function() {
            var $button = $(this);
            var $result = $('#nc-test-result');

            $button.prop('disabled', true);
            $result.html('<span class="spinner is-active"></span> Testing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'nc_test_api_connection',
                    nonce: '<?php echo wp_create_nonce('nc_test_api'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span style="color:green;">✓ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color:red;">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span style="color:red;">✗ Connection failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    });
</script>