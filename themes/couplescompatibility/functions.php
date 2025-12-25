<?php
/**
 * Theme entry point
 */

// 1. Include Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Fallback if composer install was not run
	wp_die('<h1>Error: Composer not installed</h1><p>Run <code>composer install</code> in theme folder.</p>');
}

// 2. Initialize Timber
Timber\Timber::init();

// 3. Set Twig templates directory
Timber\Timber::$dirname = ['views'];

// 4. Initialize theme configuration class
new MyTheme\StarterSite();