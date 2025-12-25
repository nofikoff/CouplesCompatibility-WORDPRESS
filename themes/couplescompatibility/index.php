<?php
/**
 * Главный файл шаблона
 */

$context = Timber\Timber::context();
$context['post'] = Timber\Timber::get_post();

// === ИНТЕГРАЦИЯ С ACF ===
// Если поля ACF существуют, добавляем их в контекст.
// Polylang сам подменит данные в зависимости от языка.
if (function_exists('get_fields')) {
	$context['fields'] = get_fields();
}

// Рендерим шаблон
// Timber ищет файл в папке views/pages/
Timber\Timber::render('pages/landing.twig', $context);