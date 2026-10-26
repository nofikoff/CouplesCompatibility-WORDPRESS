<?php
// tests/test-geo-language.php

namespace NC\Tests;

use NC\GeoLanguage;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * wp_safe_redirect() is followed by exit in GeoLanguage, so the stub throws this
 * to end the request inside the test instead of killing the PHPUnit process.
 */
class RedirectIssued extends \RuntimeException {}

class TestGeoLanguage extends TestCase {

    public function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        $_COOKIE = [];
        $_SERVER['HTTP_CF_IPCOUNTRY'] = 'UA';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $uk = (object) ['slug' => 'uk'];
        $pll = new \stdClass();
        $pll->model = new class([$uk]) {
            private $languages;
            public function __construct($languages) { $this->languages = $languages; }
            public function get_languages_list() { return $this->languages; }
        };
        $pll->links = new class {
            public function get_translation_url($lang) { return 'https://numerology.x3mal.com/' . $lang->slug . '/'; }
        };

        Functions\stubs([
            'pll_the_languages' => null,
            'PLL' => $pll,
            'pll_current_language' => 'en',
            'pll_home_url' => function($slug) { return 'https://numerology.x3mal.com/' . $slug . '/'; },
            'home_url' => function($path = '') { return 'https://numerology.x3mal.com' . $path; },
            'is_robots' => false,
            'is_favicon' => false,
            'is_feed' => false,
            'is_trackback' => false,
            'is_embed' => false,
            'get_query_var' => '',
        ]);
    }

    public function tearDown(): void {
        Monkey\tearDown();
        $_COOKIE = [];
        unset($_SERVER['HTTP_CF_IPCOUNTRY'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
        parent::tearDown();
    }

    /**
     * @dataProvider conditionalTags
     */
    public function test_service_request_is_not_redirected($tag) {
        Functions\when($tag)->justReturn(true);

        $this->assertNoRedirect();
    }

    public function conditionalTags() {
        return [
            'robots.txt' => ['is_robots'],
            'favicon.ico' => ['is_favicon'],
            'feed' => ['is_feed'],
            'trackback' => ['is_trackback'],
            'embed' => ['is_embed'],
        ];
    }

    /**
     * @dataProvider sitemapQueryVars
     */
    public function test_sitemap_request_is_not_redirected($var) {
        Functions\when('get_query_var')->alias(function($name) use ($var) {
            return $name === $var ? 'index' : '';
        });

        $this->assertNoRedirect();
    }

    public function sitemapQueryVars() {
        return [
            'sitemap' => ['sitemap'],
            'sitemap stylesheet' => ['sitemap-stylesheet'],
        ];
    }

    /**
     * @dataProvider nonNavigationMethods
     */
    public function test_non_get_request_is_not_redirected($method) {
        $_SERVER['REQUEST_METHOD'] = $method;

        $this->assertNoRedirect();
    }

    public function nonNavigationMethods() {
        return [
            'POST' => ['POST'],
            'PUT' => ['PUT'],
        ];
    }

    /**
     * Separate process: setcookie() runs before the redirect and would warn once PHPUnit has printed output.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_page_view_from_ukraine_redirects_to_ukrainian() {
        Functions\expect('wp_safe_redirect')
            ->once()
            ->with('https://numerology.x3mal.com/uk/')
            ->andThrow(new RedirectIssued());

        $this->expectException(RedirectIssued::class);

        (new GeoLanguage())->maybe_switch_language();
    }

    /**
     * A request that reached setcookie() would also fail here: PHPUnit has printed output, and
     * phpunit.xml turns the "headers already sent" warning into an exception.
     */
    private function assertNoRedirect() {
        Functions\expect('wp_safe_redirect')->never();

        (new GeoLanguage())->maybe_switch_language();

        // never() is verified by Monkey\tearDown(); counted here so PHPUnit does not flag the test as risky.
        $this->addToAssertionCount(1);
    }
}
