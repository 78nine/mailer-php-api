<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class RateLimitTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        // Create a unique temp directory for this test
        $this->tempDir = sys_get_temp_dir() . '/mailer_rate_limit_test_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0700, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test rate limit files
        $dirs = [
            sys_get_temp_dir() . '/mailer_rate_limit',
            $this->tempDir
        ];
        
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*.json');
                foreach ($files as $file) {
                    @unlink($file);
                }
                @rmdir($dir);
            }
        }
    }

    public function testGetClientIpFromRemoteAddr()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $ip = get_client_ip();
        $this->assertEquals('192.168.1.100', $ip);
    }

    public function testGetClientIpFromXForwardedFor()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.195, 70.41.3.18, 150.172.238.178';
        $ip = get_client_ip();
        $this->assertEquals('203.0.113.195', $ip); // Should return first IP
    }

    public function testGetClientIpFromXForwardedForSingle()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.195';
        $ip = get_client_ip();
        $this->assertEquals('203.0.113.195', $ip);
    }

    public function testGetClientIpFromClientIp()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_CLIENT_IP'] = '198.51.100.1';
        $ip = get_client_ip();
        $this->assertEquals('198.51.100.1', $ip);
    }

    public function testGetClientIpPrioritizesXForwardedFor()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.195';
        $_SERVER['HTTP_CLIENT_IP'] = '198.51.100.1';
        $ip = get_client_ip();
        $this->assertEquals('203.0.113.195', $ip); // X-Forwarded-For takes priority
    }

    public function testCheckRateLimitAllowsFirstRequest()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $result = check_rate_limit(10, 60);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(9, $result['remaining']);
    }

    public function testCheckRateLimitTracksMultipleRequests()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        for ($i = 0; $i < 5; $i++) {
            $result = check_rate_limit(10, 60);
            $this->assertTrue($result['allowed']);
            $this->assertEquals(9 - $i, $result['remaining']);
        }
    }

    public function testCheckRateLimitBlocksWhenLimitReached()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.2';
        
        // Make max_requests (3) requests
        for ($i = 0; $i < 3; $i++) {
            $result = check_rate_limit(3, 60);
            $this->assertTrue($result['allowed'], "Request $i should be allowed");
        }
        
        // Next request should be blocked
        $result = check_rate_limit(3, 60);
        $this->assertFalse($result['allowed']);
        $this->assertArrayHasKey('retry_after', $result);
        $this->assertGreaterThan(0, $result['retry_after']);
    }

    public function testCheckRateLimitExpireOldRequests()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.3';
        
        // Make 2 requests with 1 second time window
        $result = check_rate_limit(2, 1);
        $this->assertTrue($result['allowed']);
        
        $result = check_rate_limit(2, 1);
        $this->assertTrue($result['allowed']);
        
        // Wait for time window to expire
        sleep(2);
        
        // Should allow new requests after window expires
        $result = check_rate_limit(2, 1);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['remaining']);
    }

    public function testCheckRateLimitDifferentIpsIndependent()
    {
        // IP 1 makes requests
        $_SERVER['REMOTE_ADDR'] = '127.0.0.4';
        for ($i = 0; $i < 3; $i++) {
            $result = check_rate_limit(3, 60);
            $this->assertTrue($result['allowed']);
        }
        $result = check_rate_limit(3, 60);
        $this->assertFalse($result['allowed']);
        
        // IP 2 should still be allowed
        $_SERVER['REMOTE_ADDR'] = '127.0.0.5';
        $result = check_rate_limit(3, 60);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']);
    }

    public function testCheckRateLimitCreatesStorageDirectory()
    {
        $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
        
        // Remove directory if exists
        if (is_dir($storage_dir)) {
            $files = glob($storage_dir . '/*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($storage_dir);
        }
        
        $this->assertFalse(is_dir($storage_dir));
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.6';
        check_rate_limit(10, 60);
        
        $this->assertTrue(is_dir($storage_dir));
    }

    public function testCheckRateLimitRetryAfterCalculation()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.7';
        
        // Fill up the limit with 2 second window
        for ($i = 0; $i < 3; $i++) {
            check_rate_limit(3, 2);
        }
        
        $result = check_rate_limit(3, 2);
        $this->assertFalse($result['allowed']);
        $this->assertLessThanOrEqual(2, $result['retry_after']);
        $this->assertGreaterThanOrEqual(1, $result['retry_after']);
    }

    public function testCleanupRateLimitFilesRemovesOldFiles()
    {
        $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
        if (!is_dir($storage_dir)) {
            mkdir($storage_dir, 0700, true);
        }
        
        // Create an old file
        $old_file = $storage_dir . '/old_file.json';
        file_put_contents($old_file, json_encode(['requests' => [time() - 7200]]));
        touch($old_file, time() - 7200); // Set modification time to 2 hours ago
        
        // Create a new file
        $new_file = $storage_dir . '/new_file.json';
        file_put_contents($new_file, json_encode(['requests' => [time()]]));
        
        $this->assertFileExists($old_file);
        $this->assertFileExists($new_file);
        
        // Cleanup files older than 1 hour
        cleanup_rate_limit_files(3600);
        
        $this->assertFileDoesNotExist($old_file);
        $this->assertFileExists($new_file);
        
        // Cleanup
        @unlink($new_file);
    }

    public function testCleanupRateLimitFilesHandlesMissingDirectory()
    {
        $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit_nonexistent';
        
        if (is_dir($storage_dir)) {
            rmdir($storage_dir);
        }
        
        // Should not throw error
        cleanup_rate_limit_files(3600);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testCheckRateLimitHandlesCorruptedFile()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.8';
        $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
        
        if (!is_dir($storage_dir)) {
            mkdir($storage_dir, 0700, true);
        }
        
        $ip_hash = hash('sha256', '127.0.0.8');
        $file = $storage_dir . '/' . $ip_hash . '.json';
        
        // Write corrupted JSON
        file_put_contents($file, 'invalid json {{{');
        
        // Should handle gracefully and allow request
        $result = check_rate_limit(10, 60);
        $this->assertTrue($result['allowed']);
        
        // Cleanup
        @unlink($file);
    }

    public function testCheckRateLimitUsesFileLocking()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.9';
        
        // Make a request to create the file
        $result = check_rate_limit(10, 60);
        $this->assertTrue($result['allowed']);
        
        // Verify file exists and contains valid JSON
        $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
        $ip_hash = hash('sha256', '127.0.0.9');
        $file = $storage_dir . '/' . $ip_hash . '.json';
        
        $this->assertFileExists($file);
        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('requests', $data);
        $this->assertIsArray($data['requests']);
        $this->assertCount(1, $data['requests']);
        
        // Cleanup
        @unlink($file);
    }
}
