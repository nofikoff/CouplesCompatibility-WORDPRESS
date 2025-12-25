<?php

namespace MyTheme;

use Timber\Site;

class StarterSite extends Site {

	public function __construct() {
		// WordPress hooks
		add_action('after_setup_theme', [$this, 'theme_supports']);
		add_filter('timber/context', [$this, 'add_to_context']);

		parent::__construct();
	}

	/**
	 * Get settings page ID for current language
	 * Uses a page with template 'page-site-settings.php' (translatable via Polylang)
	 */
	public function get_settings_page_id() {
		$args = [
			'post_type'      => 'page',
			'posts_per_page' => 1,
			'meta_key'       => '_wp_page_template',
			'meta_value'     => 'page-site-settings.php',
			'fields'         => 'ids',
		];

		// Polylang automatically filters by current language
		$pages = get_posts($args);

		return !empty($pages) ? $pages[0] : null;
	}

	/**
	 * Standard WP theme settings
	 */
	public function theme_supports() {
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('menus');

		// Localization support (text domain)
		load_theme_textdomain('couplescompatibility', get_template_directory() . '/languages');
	}

	/**
	 * Global data available in ALL .twig files
	 */
	public function add_to_context($context) {
		$context['site'] = $this;
		$context['menu'] = \Timber\Timber::get_menu();

		// If Polylang is installed, pass current language
		if (function_exists('pll_current_language')) {
			$context['current_lang'] = pll_current_language();
			$context['languages'] = pll_the_languages(['raw' => 1]);
		}

		// Site settings from "site-settings" page (translatable via Polylang)
		if (function_exists('get_fields')) {
			$settings_page_id = $this->get_settings_page_id();
			if ($settings_page_id) {
				$context['options'] = get_fields($settings_page_id);
			}
		}

		return $context;
	}

}