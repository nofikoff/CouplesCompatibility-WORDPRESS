<?php
/**
 * Template for archive pages
 */

$context = Timber\Timber::context();
$context['posts'] = Timber\Timber::get_posts();
$context['title'] = get_the_archive_title();
$context['description'] = get_the_archive_description();

if (function_exists('get_fields')) {
    $context['fields'] = get_fields();
}

Timber\Timber::render('pages/archive.twig', $context);
