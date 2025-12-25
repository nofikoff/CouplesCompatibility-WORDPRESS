<?php
/**
 * Template for single posts
 */

$context = Timber\Timber::context();
$context['post'] = Timber\Timber::get_post();

if (function_exists('get_fields')) {
    $context['fields'] = get_fields();
}

Timber\Timber::render('pages/single.twig', $context);
