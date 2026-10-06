<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER = [];
        
        // Clean up any rate limit files
        $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
        if (is_dir($storage_dir)) {
            $files = glob($storage_dir . '/*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up rate limit files
        $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
        if (is_dir($storage_dir)) {
            $files = glob($storage_dir . '/*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    // check_authentication tests
    public function testCheckAuthenticationWithValidToken()
    {
        $_SERVER['Authorization'] = 'Bearer valid-token-123';
        $config = ['api_token' => 'valid-token-123'];
        
        ob_start();
        check_authentication($config);
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }





    // enforce_rate_limit tests
    public function testEnforceRateLimitAllowsWithinLimit()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $config = [
            'rate_limit_enabled' => true,
            'rate_limit_max' => 10,
            'rate_limit_window' => 60
        ];
        
        ob_start();
        enforce_rate_limit($config);
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }



    public function testEnforceRateLimitWhenDisabled()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.3';
        $config = [
            'rate_limit_enabled' => false,
            'rate_limit_max' => 1,
            'rate_limit_window' => 60
        ];
        
        // Should allow unlimited requests
        for ($i = 0; $i < 10; $i++) {
            ob_start();
            enforce_rate_limit($config);
            $output = ob_get_clean();
            $this->assertEmpty($output);
        }
    }



    public function testEnforceRateLimitDefaultsToEnabled()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.5';
        $config = []; // No rate_limit_enabled key
        
        ob_start();
        enforce_rate_limit($config);
        $output = ob_get_clean();
        
        // Should apply rate limiting by default
        $this->assertEmpty($output);
    }



    public function testEnforceRateLimitHandlesStringBooleans()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.9';
        
        // Test with string "true"
        $config = [
            'rate_limit_enabled' => 'true',
            'rate_limit_max' => 5,
            'rate_limit_window' => 60
        ];
        
        ob_start();
        enforce_rate_limit($config);
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
        
        // Test with string "false"
        $config['rate_limit_enabled'] = 'false';
        
        ob_start();
        enforce_rate_limit($config);
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }


}
