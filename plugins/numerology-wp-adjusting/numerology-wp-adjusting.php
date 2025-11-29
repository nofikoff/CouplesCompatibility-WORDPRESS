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
 * Auto-switch WordPress language based on visitor's country via Cloudflare CF-IPCountry header.
 *
 * Country mapping:
 * - UA (Ukraine) -> uk (Ukrainian)
 * - All other countries -> en (English, default)
 *
 * Requires Polylang plugin.
 */
add_action('init', function() {
    // Skip for admin area, AJAX requests, REST API, cron, and system pages
    if (is_admin() || wp_doing_ajax() || defined('REST_REQUEST') || wp_doing_cron()) {
        return;
    }

    // Skip system URLs
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#^/(wp-cron|wp-admin|wp-login|wp-json|xmlrpc)#', $request_uri)) {
        return;
    }

    // Check if Polylang is active
    if (!function_exists('pll_the_languages') || !function_exists('PLL')) {
        return;
    }

    // Get country code from Cloudflare header
    $country_code = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']) : '';

    // Map country to language code
    $country_to_lang = [
        'UA' => 'uk', // Ukraine -> Ukrainian
    ];

    // Determine target language (default to English)
    $target_lang = $country_to_lang[$country_code] ?? 'en';

    // Get current language
    $current_lang = pll_current_language();

    // Check if geo-detection was already done (use separate cookie)
    $geo_detected = $_COOKIE['nc_geo_lang_detected'] ?? null;

    // Only auto-switch if geo-detection hasn't been done yet and language differs
    if ($geo_detected === null && $current_lang !== $target_lang) {
        // Check if target language exists in Polylang
        $languages = PLL()->model->get_languages_list();
        $target_lang_obj = null;

        foreach ($languages as $lang) {
            if ($lang->slug === $target_lang) {
                $target_lang_obj = $lang;
                break;
            }
        }

        if ($target_lang_obj) {
            // Try to get translation URL, fallback to home URL for homepage
            $translations = null;
            if (method_exists(PLL()->links, 'get_translation_url')) {
                $translations = PLL()->links->get_translation_url($target_lang_obj);
            }
            if (!$translations) {
                $translations = pll_home_url($target_lang);
            }

            $current_url = home_url($_SERVER['REQUEST_URI']);

            if ($translations && $translations !== $current_url) {
                setcookie('nc_geo_lang_detected', '1', time() + (86400 * 30), '/');
                wp_safe_redirect($translations);
                exit;
            } else {
                setcookie('nc_geo_lang_detected', '1', time() + (86400 * 30), '/');
            }
        }
    }
}, 1); // Priority 1 to run early

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
