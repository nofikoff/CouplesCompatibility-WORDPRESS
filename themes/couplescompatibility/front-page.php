<?php
/**
 * Front Page template
 * Used when a static page is set as the front page
 */

$context = Timber\Timber::context();
$context['post'] = Timber\Timber::get_post();

// ACF Integration - add fields to context if ACF is active
// Polylang automatically handles field translations
if (function_exists('get_fields')) {
	$context['fields'] = get_fields();
}

// Render landing page template
Timber\Timber::render('pages/landing.twig', $context);
