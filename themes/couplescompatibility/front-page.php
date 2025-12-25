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

// Get 4 random posts for blog preview (Polylang filters by current language)
$posts = Timber\Timber::get_posts([
	'post_type' => 'post',
	'posts_per_page' => 4,
	'orderby' => 'rand',
]);
$context['blog_posts'] = [];
foreach ($posts as $post) {
	$context['blog_posts'][] = [
		'title' => $post->title(),
		'link' => $post->link(),
		'preview' => wp_trim_words(wp_strip_all_tags($post->content()), 25, '...'),
	];
}

// Render landing page template
Timber\Timber::render('pages/landing.twig', $context);
