<?php
namespace NC;

/**
 * Geo-based language detection and switching
 *
 * Auto-switches WordPress language based on visitor's country
 * via Cloudflare CF-IPCountry header.
 *
 * Country mapping:
 * - UA (Ukraine) -> uk (Ukrainian)
 * - All other countries -> en (English, default)
 *
 * Requires Polylang plugin.
 */
class GeoLanguage {

	/**
	 * Cookie name to track if geo detection was already performed
	 */
	private const COOKIE_NAME = 'nc_geo_lang_detected';

	/**
	 * Cookie lifetime in days
	 */
	private const COOKIE_DAYS = 30;

	/**
	 * Country to language mapping
	 */
	private const COUNTRY_LANG_MAP = [
		'UA' => 'uk',
	];

	/**
	 * Default language if country not in map
	 */
	private const DEFAULT_LANG = 'en';

	/**
	 * Register hooks
	 */
	public function register() {
		add_action('template_redirect', [$this, 'maybe_switch_language']);
	}

	/**
	 * Check if language switch is needed based on geo
	 */
	public function maybe_switch_language() {
		// Check if Polylang is active
		if (!function_exists('pll_the_languages') || !function_exists('PLL')) {
			return;
		}

		// Check if geo detection was already done
		if (isset($_COOKIE[self::COOKIE_NAME])) {
			return;
		}

		// Get country code from Cloudflare header
		$country_code = $this->get_country_code();
		if (empty($country_code)) {
			return;
		}

		// Determine target language
		$target_lang = self::COUNTRY_LANG_MAP[$country_code] ?? self::DEFAULT_LANG;
		$current_lang = pll_current_language();

		// No need to switch if already on target language
		if ($current_lang === $target_lang) {
			$this->set_detection_cookie();
			return;
		}

		// Find target language in Polylang
		$target_url = $this->get_translated_url($target_lang);

		if ($target_url) {
			$this->set_detection_cookie();
			wp_safe_redirect($target_url);
			exit;
		}
	}

	/**
	 * Get country code from Cloudflare header
	 *
	 * @return string Country code (e.g., 'UA', 'US') or empty string
	 */
	private function get_country_code() {
		return isset($_SERVER['HTTP_CF_IPCOUNTRY'])
			? strtoupper($_SERVER['HTTP_CF_IPCOUNTRY'])
			: '';
	}

	/**
	 * Get translated URL for target language
	 *
	 * @param string $target_lang Language code (e.g., 'uk', 'en')
	 * @return string|null Translated URL or null
	 */
	private function get_translated_url($target_lang) {
		$languages = PLL()->model->get_languages_list();
		$target_lang_obj = null;

		foreach ($languages as $lang) {
			if ($lang->slug === $target_lang) {
				$target_lang_obj = $lang;
				break;
			}
		}

		if (!$target_lang_obj) {
			return null;
		}

		// Try to get translation URL for current page
		$translation_url = null;
		if (method_exists(PLL()->links, 'get_translation_url')) {
			$translation_url = PLL()->links->get_translation_url($target_lang_obj);
		}

		// Fallback to home URL in target language
		if (!$translation_url) {
			$translation_url = pll_home_url($target_lang);
		}

		$current_url = home_url($_SERVER['REQUEST_URI']);

		// Return only if different from current URL
		return ($translation_url && $translation_url !== $current_url)
			? $translation_url
			: null;
	}

	/**
	 * Set cookie to mark that geo detection was performed
	 */
	private function set_detection_cookie() {
		setcookie(
			self::COOKIE_NAME,
			'1',
			time() + (86400 * self::COOKIE_DAYS),
			'/'
		);
	}
}
