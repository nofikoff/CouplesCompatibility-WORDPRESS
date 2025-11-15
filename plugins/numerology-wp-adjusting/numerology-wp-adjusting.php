<?php
/**
 * Plugin Name: Numerology WP Adjusting
 * Plugin URI:
 * Description: WordPress adjustments and customizations for numerology website. Includes language-based shortcodes and other utilities.
 * Version: 1.0.0
 * Author: Novikov
 * Author URI:
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: numerology-wp-adjusting
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Shortcode: [if_lang]
 *
 * Display content only if current language matches the specified code.
 * Requires Polylang plugin.
 *
 * Usage:
 * [if_lang code="en"]This content shows only in English[/if_lang]
 * [if_lang code="ru"]Этот контент показывается только на русском[/if_lang]
 *
 * @param array $atts Shortcode attributes
 * @param string|null $content Content between shortcode tags
 * @return string Processed content or empty string
 */
add_shortcode('if_lang', function($atts, $content = null) {
    $atts = shortcode_atts(['code' => ''], $atts);

    // Check if Polylang is active and function exists
    if (function_exists('pll_current_language')) {
        $current_lang = pll_current_language();

        // If current language matches the specified code, return the content
        if ($current_lang === $atts['code']) {
            return do_shortcode($content);
        }
    }

    // Return empty string if language doesn't match or Polylang is not active
    return '';
});
