
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

    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="?page=nc-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'numerology-compatibility'); ?>
        </a>
        <a href="?page=nc-settings&tab=api" class="nav-tab <?php echo $active_tab == 'api' ? 'nav-tab-active' : ''; ?>">
            <?php _e('API Configuration', 'numerology-compatibility'); ?>
        </a>
        <a href="?page=nc-settings&tab=pricing" class="nav-tab <?php echo $active_tab == 'pricing' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Pricing', 'numerology-compatibility'); ?>
        </a>
        <a href="?page=nc-settings&tab=payment" class="nav-tab <?php echo $active_tab == 'payment' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Payment Gateway', 'numerology-compatibility'); ?>
        </a>
        <a href="?page=nc-settings&tab=localization" class="nav-tab <?php echo $active_tab == 'localization' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Localization', 'numerology-compatibility'); ?>
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
                    <th scope="row"><?php _e('Environment', 'numerology-compatibility'); ?></th>
                    <td>
                        <select name="nc_environment">
                            <option value="production" <?php selected(get_option('nc_environment'), 'production'); ?>>
                                <?php _e('Production', 'numerology-compatibility'); ?>
                            </option>
                            <option value="staging" <?php selected(get_option('nc_environment'), 'staging'); ?>>
                                <?php _e('Staging', 'numerology-compatibility'); ?>
                            </option>
                            <option value="development" <?php selected(get_option('nc_environment'), 'development'); ?>>
                                <?php _e('Development', 'numerology-compatibility'); ?>
                            </option>
                        </select>
                        <p class="description"><?php _e('Select the environment for API connections', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Require Authentication', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_require_auth" value="1" <?php checked(get_option('nc_require_auth', 1)); ?>>
                            <?php _e('Users must sign in before calculations', 'numerology-compatibility'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Email Verification', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_email_verification" value="1" <?php checked(get_option('nc_email_verification', 0)); ?>>
                            <?php _e('Require email verification for new accounts', 'numerology-compatibility'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Terms Page URL', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="url" name="nc_terms_url" value="<?php echo esc_attr(get_option('nc_terms_url', '/terms')); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Privacy Page URL', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="url" name="nc_privacy_url" value="<?php echo esc_attr(get_option('nc_privacy_url', '/privacy')); ?>" class="regular-text">
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
                    <th scope="row"><?php _e('API Secret', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="password" name="nc_api_secret" value="<?php echo esc_attr(get_option('nc_api_secret')); ?>" class="regular-text">
                        <p class="description"><?php _e('API secret for request signing', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Webhook Secret', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="password" name="nc_webhook_secret" value="<?php echo esc_attr(get_option('nc_webhook_secret')); ?>" class="regular-text">
                        <p class="description">
                            <?php _e('Secret key for verifying webhook signatures from the backend', 'numerology-compatibility'); ?><br>
                            <?php _e('Must match WORDPRESS_WEBHOOK_SECRET in backend .env', 'numerology-compatibility'); ?>
                        </p>
                        <p class="description">
                            <strong><?php _e('Webhook URL:', 'numerology-compatibility'); ?></strong>
                            <code><?php echo home_url('/wp-json/numerology/v1/webhook/stripe'); ?></code>
                        </p>
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

        <!-- Pricing Tab -->
        <?php if ($active_tab == 'pricing'): ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Default Currency', 'numerology-compatibility'); ?></th>
                    <td>
                        <select name="nc_currency">
                            <option value="USD" <?php selected(get_option('nc_currency'), 'USD'); ?>>USD ($)</option>
                            <option value="EUR" <?php selected(get_option('nc_currency'), 'EUR'); ?>>EUR (€)</option>
                            <option value="GBP" <?php selected(get_option('nc_currency'), 'GBP'); ?>>GBP (£)</option>
                            <option value="CAD" <?php selected(get_option('nc_currency'), 'CAD'); ?>>CAD ($)</option>
                            <option value="AUD" <?php selected(get_option('nc_currency'), 'AUD'); ?>>AUD ($)</option>
                            <option value="RUB" <?php selected(get_option('nc_currency'), 'RUB'); ?>>RUB (₽)</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th colspan="2">
                        <h3><?php _e('Package Pricing', 'numerology-compatibility'); ?></h3>
                    </th>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Free Package', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_free_enabled" value="1" <?php checked(get_option('nc_free_enabled', 1)); ?>>
                            <?php _e('Enable free package', 'numerology-compatibility'); ?>
                        </label>
                        <p class="description"><?php _e('Basic compatibility overview', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Light Package Price', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="number" name="nc_price_light" value="<?php echo esc_attr(get_option('nc_price_light', 19)); ?>" min="1" step="0.01" class="small-text">
                        <p class="description"><?php _e('Full report with 1-2 year forecast', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Pro Package Price', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="number" name="nc_price_pro" value="<?php echo esc_attr(get_option('nc_price_pro', 49)); ?>" min="1" step="0.01" class="small-text">
                        <p class="description"><?php _e('Extended report with karmic analysis and 10-20 year forecast', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- Payment Gateway Tab -->
        <?php if ($active_tab == 'payment'): ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Payment Mode', 'numerology-compatibility'); ?></th>
                    <td>
                        <select name="nc_stripe_mode">
                            <option value="test" <?php selected(get_option('nc_stripe_mode'), 'test'); ?>>
                                <?php _e('Test Mode', 'numerology-compatibility'); ?>
                            </option>
                            <option value="live" <?php selected(get_option('nc_stripe_mode'), 'live'); ?>>
                                <?php _e('Live Mode', 'numerology-compatibility'); ?>
                            </option>
                        </select>
                        <?php if (get_option('nc_stripe_mode') == 'test'): ?>
                            <p class="notice notice-warning inline">
                                <?php _e('⚠️ Test mode is active. Real payments will not be processed.', 'numerology-compatibility'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Stripe Configuration', 'numerology-compatibility'); ?></th>
                    <td>
                        <div class="notice notice-info inline">
                            <p><?php _e('Stripe is configured through your Laravel backend. Ensure your backend Stripe settings are properly configured.', 'numerology-compatibility'); ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Webhook URL', 'numerology-compatibility'); ?></th>
                    <td>
                        <code><?php echo home_url('/wp-json/numerology/v1/stripe-webhook'); ?></code>
                        <button type="button" class="button button-small nc-copy-webhook" style="margin-left: 10px;">
                            <?php _e('Copy', 'numerology-compatibility'); ?>
                        </button>
                        <p class="description"><?php _e('Add this URL to your Stripe webhook settings', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Webhook Secret', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="password" name="nc_stripe_webhook_secret" value="<?php echo esc_attr(get_option('nc_stripe_webhook_secret')); ?>" class="regular-text">
                        <p class="description"><?php _e('Get this from your Stripe webhook settings', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- Localization Tab -->
        <?php if ($active_tab == 'localization'): ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Default Language', 'numerology-compatibility'); ?></th>
                    <td>
                        <select name="nc_default_language">
                            <option value="en_US" <?php selected(get_option('nc_default_language'), 'en_US'); ?>>English</option>
                            <option value="es_ES" <?php selected(get_option('nc_default_language'), 'es_ES'); ?>>Español</option>
                            <option value="fr_FR" <?php selected(get_option('nc_default_language'), 'fr_FR'); ?>>Français</option>
                            <option value="de_DE" <?php selected(get_option('nc_default_language'), 'de_DE'); ?>>Deutsch</option>
                            <option value="ru_RU" <?php selected(get_option('nc_default_language'), 'ru_RU'); ?>>Русский</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Multi-language Support', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_multilanguage" value="1" <?php checked(get_option('nc_multilanguage', 1)); ?>>
                            <?php _e('Enable multi-language support', 'numerology-compatibility'); ?>
                        </label>
                        <p class="description"><?php _e('Works with WPML or Polylang if installed', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Auto-detect Currency', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_auto_currency" value="1" <?php checked(get_option('nc_auto_currency', 1)); ?>>
                            <?php _e('Automatically detect currency based on user location', 'numerology-compatibility'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Date Format', 'numerology-compatibility'); ?></th>
                    <td>
                        <select name="nc_date_format">
                            <option value="Y-m-d" <?php selected(get_option('nc_date_format'), 'Y-m-d'); ?>>YYYY-MM-DD</option>
                            <option value="d/m/Y" <?php selected(get_option('nc_date_format'), 'd/m/Y'); ?>>DD/MM/YYYY</option>
                            <option value="m/d/Y" <?php selected(get_option('nc_date_format'), 'm/d/Y'); ?>>MM/DD/YYYY</option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- Advanced Tab -->
        <?php if ($active_tab == 'advanced'): ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Debug Mode', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_debug_mode" value="1" <?php checked(get_option('nc_debug_mode', 0)); ?>>
                            <?php _e('Enable debug logging', 'numerology-compatibility'); ?>
                        </label>
                        <p class="description"><?php _e('Logs will be saved to wp-content/uploads/nc-logs/', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Cache Duration', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="number" name="nc_cache_duration" value="<?php echo esc_attr(get_option('nc_cache_duration', 3600)); ?>" min="0" class="small-text">
                        <span><?php _e('seconds', 'numerology-compatibility'); ?></span>
                        <p class="description"><?php _e('How long to cache API responses (0 to disable)', 'numerology-compatibility'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Rate Limiting', 'numerology-compatibility'); ?></th>
                    <td>
                        <input type="number" name="nc_rate_limit" value="<?php echo esc_attr(get_option('nc_rate_limit', 10)); ?>" min="1" class="small-text">
                        <span><?php _e('calculations per hour for free users', 'numerology-compatibility'); ?></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Delete Data on Uninstall', 'numerology-compatibility'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nc_delete_on_uninstall" value="1" <?php checked(get_option('nc_delete_on_uninstall', 0)); ?>>
                            <?php _e('Remove all plugin data when uninstalled', 'numerology-compatibility'); ?>
                        </label>
                        <p class="notice notice-warning inline">
                            <?php _e('⚠️ This will permanently delete all calculations and user data!', 'numerology-compatibility'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Export/Import', 'numerology-compatibility'); ?></th>
                    <td>
                        <button type="button" class="button" id="nc-export-settings">
                            <?php _e('Export Settings', 'numerology-compatibility'); ?>
                        </button>
                        <button type="button" class="button" id="nc-import-settings">
                            <?php _e('Import Settings', 'numerology-compatibility'); ?>
                        </button>
                        <input type="file" id="nc-import-file" accept=".json" style="display:none;">
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

        // Copy webhook URL
        $('.nc-copy-webhook').on('click', function() {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val('<?php echo home_url('/wp-json/numerology/v1/stripe-webhook'); ?>').select();
            document.execCommand('copy');
            $temp.remove();
            $(this).text('Copied!');
            setTimeout(() => $(this).text('Copy'), 2000);
        });

        // Export settings
        $('#nc-export-settings').on('click', function() {
            // Collect all settings
            var settings = {};
            $('input[name^="nc_"], select[name^="nc_"]').each(function() {
                var name = $(this).attr('name');
                var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                settings[name] = value;
            });

            // Download as JSON
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
            var downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", "numerology-settings.json");
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
        });

        // Import settings
        $('#nc-import-settings').on('click', function() {
            $('#nc-import-file').click();
        });

        $('#nc-import-file').on('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);

                    // Apply settings
                    for (var key in settings) {
                        var $field = $('[name="' + key + '"]');
                        if ($field.is(':checkbox')) {
                            $field.prop('checked', settings[key]);
                        } else {
                            $field.val(settings[key]);
                        }
                    }

                    alert('Settings imported successfully! Please save to apply.');
                } catch (err) {
                    alert('Error importing settings: ' + err.message);
                }
            };
            reader.readAsText(file);
        });
    });
</script>