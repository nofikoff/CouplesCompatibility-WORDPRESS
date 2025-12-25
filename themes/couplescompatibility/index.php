<?php
/**
 * Main template file
 */

$context = Timber\Timber::context();
$context['post'] = Timber\Timber::get_post();

// ACF Integration - add fields to context if ACF is active
// Polylang automatically handles field translations
if (function_exists('get_fields')) {
	$context['fields'] = get_fields();
}

// Render template from views/pages/
Timber\Timber::render('pages/landing.twig', $context);