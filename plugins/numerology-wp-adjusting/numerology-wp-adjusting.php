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
add_action('template_redirect', function() {
	// Check if Polylang is active
	if (!function_exists('pll_the_languages') || !function_exists('PLL')) {
		return;
	}

	// Get country code from Cloudflare header
	$country_code = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']) : '';

	// Map country to language code
	$country_to_lang = [
		'UA' => 'uk',
	];

	$target_lang = $country_to_lang[$country_code] ?? 'en';
	$current_lang = pll_current_language();

	// Check if geo-detection was already done
	$geo_detected = $_COOKIE['nc_geo_lang_detected'] ?? null;

	if ($geo_detected === null && $current_lang !== $target_lang) {
		$languages = PLL()->model->get_languages_list();
		$target_lang_obj = null;

		foreach ($languages as $lang) {
			if ($lang->slug === $target_lang) {
				$target_lang_obj = $lang;
				break;
			}
		}

		if ($target_lang_obj) {
			$translations = null;
			if (method_exists(PLL()->links, 'get_translation_url')) {
				$translations = PLL()->links->get_translation_url($target_lang_obj);
			}
			if (!$translations) {
				$translations = pll_home_url($target_lang);
			}

			$current_url = home_url($_SERVER['REQUEST_URI']);

			setcookie('nc_geo_lang_detected', '1', time() + (86400 * 30), '/');

			if ($translations && $translations !== $current_url) {
				wp_safe_redirect($translations);
				exit;
			}
		}
	}
});
