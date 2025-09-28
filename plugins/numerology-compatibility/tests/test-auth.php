<?php
// tests/test-auth.php

namespace NC\Tests;

use NC\Api\ApiAuth;
use PHPUnit\Framework\TestCase;

class TestAuth extends TestCase {

    protected $auth;

    public function setUp(): void {
        parent::setUp();
        $this->auth = new ApiAuth();
    }

    /**
     * Test registration validation
     */
    public function test_registration_validation() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Required consents not provided');

        // Missing required consents
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $this->auth->register($data);
    }

    /**
     * Test WordPress user creation
     */
    public function test_wp_user_creation() {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->auth);
        $method = $reflection->getMethod('create_wp_user');
        $method->setAccessible(true);

        $api_user = [
            'id' => 123,
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];

        $wp_user_id = $method->invoke($this->auth, $api_user);

        $this->assertIsInt($wp_user_id);

        // Check user was created
        $user = get_user_by('ID', $wp_user_id);
        $this->assertInstanceOf(\WP_User::class, $user);
        $this->assertEquals('test@example.com', $user->user_email);
    }

    /**
     * Test consent storage
     */
    public function test_consent_storage() {
        $reflection = new \ReflectionClass($this->auth);
        $method = $reflection->getMethod('store_consents');
        $method->setAccessible(true);

        $user_id = 1;
        $data = [
            'age_consent' => true,
            'terms_consent' => true,
            'marketing_consent' => false
        ];

        $method->invoke($this->auth, $user_id, $data);

        $stored_consents = get_user_meta($user_id, 'nc_consents', true);

        $this->assertIsArray($stored_consents);
        $this->assertTrue($stored_consents['age_consent']['value']);
        $this->assertTrue($stored_consents['terms_consent']['value']);
        $this->assertFalse($stored_consents['marketing_consent']['value']);
    }
}