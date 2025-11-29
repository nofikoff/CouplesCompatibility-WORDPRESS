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
    // Skip for admin area, AJAX requests, and REST API
    if (is_admin() || wp_doing_ajax() || defined('REST_REQUEST')) {
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
    $target_lang = $country_to_lang[ $country_code ] ?? 'en';

    // Get current language
    $current_lang = pll_current_language();

    // Check if user has manually selected a language (cookie check)
    $lang_cookie = $_COOKIE['pll_language'] ?? null;

    // Only auto-switch if:
    // 1. User hasn't manually selected a language (no cookie or first visit)
    // 2. Current language differs from target language
    // 3. We're on the home page or no language is set in URL yet
    if ($lang_cookie === null && $current_lang !== $target_lang) {
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
            // Get the URL for the target language version of current page
            $translations = PLL()->links->get_translation_url($target_lang_obj);

            if ($translations && $translations !== home_url($_SERVER['REQUEST_URI'])) {
                // Set cookie to remember the auto-detected language
                setcookie('pll_language', $target_lang, time() + (86400 * 30), '/'); // 30 days

                // Redirect to the target language version
                wp_safe_redirect($translations);
                exit;
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
