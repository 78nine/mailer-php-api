<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testSendSuccessResponse()
    {
        ob_start();
        send_success_response();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertEquals('Email sent successfully', $decoded['message']);
    }

    public function testSendSuccessResponseIsValidJson()
    {
        ob_start();
        send_success_response();
        $output = ob_get_clean();
        
        $this->assertJson($output);
    }

    public function testSendSuccessResponseHasSuccessKey()
    {
        ob_start();
        send_success_response();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('success', $decoded);
    }

    public function testSendSuccessResponseHasMessageKey()
    {
        ob_start();
        send_success_response();
        $output = ob_get_clean();
        
        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('message', $decoded);
    }

    public function testSendErrorResponse()
    {
        ob_start();
        send_error_response();
        $output = ob_get_clean();
        
        // send_error_response() doesn't output anything itself
        // It relies on error message already being echoed
        $this->assertEmpty($output);
    }
}
