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
            'get_query_var' => '',
        ]);
    }

    public function tearDown(): void {
        Monkey\tearDown();
        unset($_SERVER['HTTP_CF_IPCOUNTRY'], $_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    /**
     * @dataProvider serviceRequests
     */
    public function test_service_request_is_not_redirected($function, $value) {
        Functions\when($function)->justReturn($value);
        Functions\expect('wp_safe_redirect')->never();

        (new GeoLanguage())->maybe_switch_language();

        $this->addToAssertionCount(1);
    }

    public function serviceRequests() {
        return [
            'robots.txt' => ['is_robots', true],
            'favicon.ico' => ['is_favicon', true],
            'feed' => ['is_feed', true],
            'trackback' => ['is_trackback', true],
            'sitemap' => ['get_query_var', 'index'],
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
}
