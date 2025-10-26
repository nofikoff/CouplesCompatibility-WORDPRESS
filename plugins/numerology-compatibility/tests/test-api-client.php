<?php

// tests/test-api-client.php

namespace NC\Tests;

use NC\Api\ApiClient;
use PHPUnit\Framework\TestCase;

class TestApiClient extends TestCase {

    protected $client;

    public function setUp(): void {
        parent::setUp();
        $this->client = new ApiClient();
    }

    /**
     * Test API connection
     */
    public function test_api_connection() {
        $result = $this->client->test_connection();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test error handling
     */
    public function test_error_handling() {
        $this->expectException(\Exception::class);

        // Set invalid API URL
        update_option('nc_api_url', 'https://invalid-domain-that-does-not-exist.com');

        $client = new ApiClient();
        $client->request('/test', 'GET');
    }

    /**
     * Test token management
     */
    public function test_token_management() {
        $token = 'test_token_123';

        $this->client->set_token($token);
        $retrieved_token = $this->client->get_token();

        $this->assertEquals($token, $retrieved_token);
    }

    /**
     * Test retry logic
     */
    public function test_retry_logic() {
        // Mock a failing request that should retry
        $client = $this->getMockBuilder(ApiClient::class)
            ->onlyMethods(['request_with_retry'])
            ->getMock();

        $client->expects($this->exactly(3))
            ->method('request_with_retry')
            ->willReturn(new \WP_Error('http_request_failed', 'Connection timeout'));

        // This should trigger 3 retry attempts
        try {
            $client->request('/test', 'GET');
        } catch (\Exception $e) {
            // Expected exception after retries fail
            $this->assertStringContainsString('API request failed', $e->getMessage());
        }
    }
}