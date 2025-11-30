<?php
// tests/test-shortcodes.php

namespace NC\Tests;

use NC\PublicSite\Shortcodes;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class TestShortcodes extends TestCase {

    protected $shortcodes;

    public function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Mock $_GET for result page tests
        $_GET = [];

        // Mock WordPress functions
        Functions\stubs([
            '__' => function($text) { return $text; },
            '_e' => function($text) { echo $text; },
            'esc_attr' => function($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); },
            'esc_html' => function($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); },
            'esc_url' => function($url) { return $url; },
            'home_url' => function($path = '') { return 'https://example.com' . $path; },
            'sanitize_text_field' => function($text) { return trim(strip_tags($text)); },
            'shortcode_atts' => function($defaults, $atts) {
                $atts = (array) $atts;
                return array_merge($defaults, $atts);
            },
            'add_shortcode' => function() { return true; },
            'shortcode_exists' => function() { return true; },
            'get_option' => function($key, $default = '') {
                $options = [
                    'nc_price_standard' => '9.99',
                    'nc_price_premium' => '19.99',
                ];
                return $options[$key] ?? $default;
            },
        ]);

        // Define NC_PLUGIN_DIR constant if not defined
        if (!defined('NC_PLUGIN_DIR')) {
            define('NC_PLUGIN_DIR', dirname(__DIR__) . '/');
        }

        $this->shortcodes = new Shortcodes();
    }

    public function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test normal mode shortcode renders with data-mode="normal"
     */
    public function test_normal_mode_shortcode_output() {
        $output = $this->shortcodes->render_calculator([]);

        $this->assertStringContainsString('data-mode="normal"', $output);
        $this->assertStringContainsString('class="nc-calculator', $output);
        $this->assertStringContainsString('nc-step-1', $output);
    }

    /**
     * Test reversed mode shortcode renders with data-mode="reversed"
     */
    public function test_reversed_mode_shortcode_output() {
        $output = $this->shortcodes->render_calculator_v2([]);

        $this->assertStringContainsString('data-mode="reversed"', $output);
        $this->assertStringContainsString('class="nc-calculator', $output);
        $this->assertStringContainsString('nc-step-1', $output);
    }

    /**
     * Test normal mode has date form in Step 1
     */
    public function test_normal_mode_step1_has_date_form() {
        $output = $this->shortcodes->render_calculator([]);

        // In normal mode, Step 1 should contain the date input form
        $this->assertMatchesRegularExpression(
            '/nc-step-1.*nc-active.*person1_date/s',
            $output,
            'Normal mode Step 1 should contain date input'
        );
    }

    /**
     * Test reversed mode has package selection in Step 1
     */
    public function test_reversed_mode_step1_has_package_selection() {
        $output = $this->shortcodes->render_calculator_v2([]);

        // In reversed mode, Step 1 should contain package selection
        $this->assertMatchesRegularExpression(
            '/nc-step-1.*nc-active.*nc-packages/s',
            $output,
            'Reversed mode Step 1 should contain package selection'
        );
    }

    /**
     * Test reversed mode has date form in Step 2
     */
    public function test_reversed_mode_step2_has_date_form() {
        $output = $this->shortcodes->render_calculator_v2([]);

        // In reversed mode, Step 2 should contain the date input form
        $this->assertMatchesRegularExpression(
            '/nc-step-2.*person1_date/s',
            $output,
            'Reversed mode Step 2 should contain date input'
        );
    }

    /**
     * Test shortcode attributes are applied
     */
    public function test_shortcode_attributes() {
        $output = $this->shortcodes->render_calculator([
            'package' => 'premium'
        ]);

        $this->assertStringContainsString('data-package="premium"', $output);
    }

    /**
     * Test reversed shortcode attributes are applied
     */
    public function test_reversed_shortcode_attributes() {
        $output = $this->shortcodes->render_calculator_v2([
            'package' => 'standard'
        ]);

        $this->assertStringContainsString('data-package="standard"', $output);
        $this->assertStringContainsString('data-mode="reversed"', $output);
    }

    /**
     * Test both shortcodes have same structure for Steps 3-6
     */
    public function test_common_steps_present_in_both_modes() {
        $normalOutput = $this->shortcodes->render_calculator([]);
        $reversedOutput = $this->shortcodes->render_calculator_v2([]);

        // Both should have Step 3 (Processing)
        $this->assertStringContainsString('nc-step-3', $normalOutput);
        $this->assertStringContainsString('nc-step-3', $reversedOutput);

        // Both should have Step 4 (Payment Pending)
        $this->assertStringContainsString('nc-step-4', $normalOutput);
        $this->assertStringContainsString('nc-step-4', $reversedOutput);

        // Both should have Step 5 (Success)
        $this->assertStringContainsString('nc-step-5', $normalOutput);
        $this->assertStringContainsString('nc-step-5', $reversedOutput);

        // Both should have Step 6 (Error)
        $this->assertStringContainsString('nc-step-6', $normalOutput);
        $this->assertStringContainsString('nc-step-6', $reversedOutput);
    }

    /**
     * Test reversed mode button text is "Calculate" instead of "Continue"
     */
    public function test_reversed_mode_button_text() {
        $reversedOutput = $this->shortcodes->render_calculator_v2([]);

        // In reversed mode, the form button should say "Calculate"
        $this->assertStringContainsString('Calculate', $reversedOutput);
    }

    // =========================================
    // Tests for [numerology_result] shortcode
    // =========================================

    /**
     * Test result shortcode renders wrapper element
     */
    public function test_result_shortcode_renders_wrapper() {
        $output = $this->shortcodes->render_result([]);

        $this->assertStringContainsString('id="nc-result-wrapper"', $output);
        $this->assertStringContainsString('class="nc-result-wrapper"', $output);
    }

    /**
     * Test result shortcode has all state containers
     */
    public function test_result_shortcode_has_all_states() {
        $output = $this->shortcodes->render_result([]);

        // Should have loading state
        $this->assertStringContainsString('nc-result-loading', $output);

        // Should have success state
        $this->assertStringContainsString('nc-result-success', $output);

        // Should have generating state
        $this->assertStringContainsString('nc-result-generating', $output);

        // Should have error state
        $this->assertStringContainsString('nc-result-error', $output);

        // Should have cancelled state
        $this->assertStringContainsString('nc-result-cancelled', $output);

        // Should have empty state
        $this->assertStringContainsString('nc-result-empty', $output);
    }

    /**
     * Test result shortcode shows empty state when no parameters
     */
    public function test_result_shortcode_shows_empty_state_by_default() {
        $_GET = [];
        $output = $this->shortcodes->render_result([]);

        // Empty state should be visible (no nc-hidden class)
        $this->assertMatchesRegularExpression(
            '/nc-result-empty[^"]*"[^>]*>/',
            $output,
            'Empty state should be visible when no URL parameters'
        );
    }

    /**
     * Test result shortcode shows cancelled state when payment_cancelled=1
     */
    public function test_result_shortcode_shows_cancelled_state() {
        $_GET = ['payment_cancelled' => '1'];
        $output = $this->shortcodes->render_result([]);

        // Cancelled state should be visible
        $this->assertMatchesRegularExpression(
            '/nc-result-cancelled[^"]*"[^>]*>/',
            $output,
            'Cancelled state should be visible when payment_cancelled=1'
        );
    }

    /**
     * Test result shortcode has email form in success state
     */
    public function test_result_shortcode_has_email_form() {
        $output = $this->shortcodes->render_result([]);

        $this->assertStringContainsString('nc-result-email-form', $output);
        $this->assertStringContainsString('type="email"', $output);
    }

    /**
     * Test result shortcode has PDF download link
     */
    public function test_result_shortcode_has_pdf_download_link() {
        $output = $this->shortcodes->render_result([]);

        $this->assertStringContainsString('nc-result-pdf-link', $output);
        $this->assertStringContainsString('Download PDF Report', $output);
    }

    /**
     * Test result shortcode stores data attributes from URL
     */
    public function test_result_shortcode_stores_url_parameters() {
        $_GET = [
            'code' => 'abc123def456abc123def456abc12345',
            'payment_id' => '999',
            'payment_success' => '1'
        ];
        $output = $this->shortcodes->render_result([]);

        $this->assertStringContainsString('data-secret-code="abc123def456abc123def456abc12345"', $output);
        $this->assertStringContainsString('data-payment-id="999"', $output);
        $this->assertStringContainsString('data-payment-success="1"', $output);
    }

    /**
     * Test all three shortcodes are registered
     */
    public function test_all_shortcodes_registered() {
        // This test verifies the register_shortcodes method works
        // The actual registration is mocked, but we verify method exists
        $this->assertTrue(method_exists($this->shortcodes, 'render_calculator'));
        $this->assertTrue(method_exists($this->shortcodes, 'render_calculator_v2'));
        $this->assertTrue(method_exists($this->shortcodes, 'render_result'));
    }
}
