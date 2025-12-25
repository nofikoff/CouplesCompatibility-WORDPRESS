<?php

namespace MyTheme;

use Timber\Site;

class StarterSite extends Site {

	public function __construct() {
		// Хуки WordPress
		add_action('after_setup_theme', [$this, 'theme_supports']);
		add_filter('timber/context', [$this, 'add_to_context']);

		parent::__construct();
	}

	/**
	 * Стандартные настройки WP
	 */
	public function theme_supports() {
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('menus');

		// Поддержка локализации (текстовый домен)
		load_theme_textdomain('ai-landing', get_template_directory() . '/languages');
	}

	/**
	 * Глобальные данные, доступные во ВСЕХ .twig файлах
	 */
	public function add_to_context($context) {
		$context['site'] = $this;
		$context['menu'] = \Timber\Timber::get_menu();

		// Если установлен Polylang, передаем текущий язык
		if (function_exists('pll_current_language')) {
			$context['current_lang'] = pll_current_language(); // 'ru', 'en'
			$context['languages'] = pll_the_languages(['raw' => 1]); // Список языков для переключателя
		}

		return $context;
	}

}