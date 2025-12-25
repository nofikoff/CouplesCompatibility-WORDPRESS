<?php
/**
 * Template Name: Site Settings
 * Description: Hidden page for storing global site settings (header/footer)
 */

// Redirect to homepage - this page is only for storing ACF fields and is private!
wp_redirect(home_url('/'));
exit;
