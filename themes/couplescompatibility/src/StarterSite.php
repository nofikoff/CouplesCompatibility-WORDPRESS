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
	 * Standard WP theme settings
	 */
	public function theme_supports() {
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('menus');

		// Localization support (text domain)
		load_theme_textdomain('ai-landing', get_template_directory() . '/languages');
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

		return $context;
	}

}