<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    private $testConfigFile;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER = [];
        $this->testConfigFile = sys_get_temp_dir() . '/test_config_' . uniqid() . '.ini';
        // Clear test headers before each test
        clear_test_headers();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if (file_exists($this->testConfigFile)) {
            @unlink($this->testConfigFile);
        }
    }

    // load_config tests
    public function testLoadConfigReadsValidIniFile()
    {
        $config_content = <<<INI
api_token = "test-token-123"
smtp_host = "smtp.example.com"
smtp_port = 587
rate_limit_enabled = true
INI;
        
        file_put_contents($this->testConfigFile, $config_content);
        
        $config = load_config($this->testConfigFile);
        
        $this->assertIsArray($config);
        $this->assertEquals('test-token-123', $config['api_token']);
        $this->assertEquals('smtp.example.com', $config['smtp_host']);
        $this->assertEquals('587', $config['smtp_port']);
        $this->assertEquals('1', $config['rate_limit_enabled']);
    }

    public function testLoadConfigHandlesBooleanValues()
    {
        $config_content = <<<INI
cors_enabled = true
rate_limit_enabled = false
INI;
        
        file_put_contents($this->testConfigFile, $config_content);
        
        $config = load_config($this->testConfigFile);
        
        $this->assertEquals('1', $config['cors_enabled']);
        $this->assertEquals('', $config['rate_limit_enabled']);
    }

    public function testLoadConfigHandlesNumericValues()
    {
        $config_content = <<<INI
smtp_port = 587
rate_limit_max = 100
max_attachment_size = 10485760
INI;
        
        file_put_contents($this->testConfigFile, $config_content);
        
        $config = load_config($this->testConfigFile);
        
        $this->assertEquals('587', $config['smtp_port']);
        $this->assertEquals('100', $config['rate_limit_max']);
        $this->assertEquals('10485760', $config['max_attachment_size']);
    }



    public function testLoadConfigHandlesEmptyFile()
    {
        file_put_contents($this->testConfigFile, '');
        
        $config = load_config($this->testConfigFile);
        
        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function testLoadConfigHandlesCommentsInIni()
    {
        $config_content = <<<INI
; This is a comment
api_token = "test-token"
# This is also a comment
smtp_host = "smtp.example.com"
INI;
        
        file_put_contents($this->testConfigFile, $config_content);
        
        $config = load_config($this->testConfigFile);
        
        $this->assertEquals('test-token', $config['api_token']);
        $this->assertEquals('smtp.example.com', $config['smtp_host']);
    }

    // enforce_post_method tests
    public function testEnforcePostMethodAllowsPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        ob_start();
        enforce_post_method();
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }

    // setup_cors tests
    public function testSetupCorsWithWildcard()
    {
        $config = ['cors_enabled' => true, 'cors_origin' => '*'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        setup_cors($config);
        
        $headers = get_headers_list();
        $this->assertContains('Access-Control-Allow-Origin: *', $headers);
    }

    public function testSetupCorsWithSingleOrigin()
    {
        $config = ['cors_enabled' => true, 'cors_origin' => 'https://example.com'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';
        
        setup_cors($config);
        
        $headers = get_headers_list();
        $this->assertContains('Access-Control-Allow-Origin: https://example.com', $headers);
    }

    public function testSetupCorsWithMultipleOriginsMatched()
    {
        $config = ['cors_enabled' => true, 'cors_origin' => 'https://example.com, https://app.example.com, https://another.com'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        
        setup_cors($config);
        
        $headers = get_headers_list();
        $this->assertContains('Access-Control-Allow-Origin: https://app.example.com', $headers);
        $this->assertContains('Vary: Origin', $headers);
    }

    public function testSetupCorsWithMultipleOriginsNotMatched()
    {
        $config = ['cors_enabled' => true, 'cors_origin' => 'https://example.com, https://app.example.com'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.com';
        
        setup_cors($config);
        
        $headers = get_headers_list();
        // Should not contain any Access-Control-Allow-Origin header for non-matched origin
        $cors_headers = array_filter($headers, function($h) {
            return strpos($h, 'Access-Control-Allow-Origin:') === 0;
        });
        $this->assertEmpty($cors_headers);
    }

    public function testSetupCorsDisabled()
    {
        $config = ['cors_enabled' => false];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        setup_cors($config);
        
        $headers = get_headers_list();
        $this->assertContains('X-Permitted-Cross-Domain-Policies: none', $headers);
    }

}
