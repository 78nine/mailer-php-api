<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER = [];
    }

    public function testGetAuthHeaderFromAuthorizationKey()
    {
        $_SERVER['Authorization'] = 'Bearer test-token-123';
        $result = get_auth_header();
        $this->assertEquals('Bearer test-token-123', $result);
    }

    public function testGetAuthHeaderFromHttpAuthorizationKey()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token-456';
        $result = get_auth_header();
        $this->assertEquals('Bearer test-token-456', $result);
    }

    public function testGetAuthHeaderPrioritizesAuthorizationOverHttpAuthorization()
    {
        $_SERVER['Authorization'] = 'Bearer priority-token';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer secondary-token';
        $result = get_auth_header();
        $this->assertEquals('Bearer priority-token', $result);
    }

    public function testGetAuthHeaderTrimsWhitespace()
    {
        $_SERVER['Authorization'] = '  Bearer test-token  ';
        $result = get_auth_header();
        $this->assertEquals('Bearer test-token', $result);
    }

    public function testGetAuthHeaderReturnsNullWhenNotSet()
    {
        $result = get_auth_header();
        $this->assertNull($result);
    }

    public function testGetBearerTokenExtractsToken()
    {
        $_SERVER['Authorization'] = 'Bearer my-secret-token';
        $result = get_bearer_token();
        $this->assertEquals('my-secret-token', $result);
    }

    public function testGetBearerTokenWithComplexToken()
    {
        $_SERVER['Authorization'] = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0';
        $result = get_bearer_token();
        $this->assertEquals('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0', $result);
    }

    public function testGetBearerTokenIgnoresNonBearerHeader()
    {
        $_SERVER['Authorization'] = 'Basic dXNlcjpwYXNz';
        $result = get_bearer_token();
        $this->assertNull($result);
    }

    public function testGetBearerTokenReturnsNullWhenNoHeader()
    {
        $result = get_bearer_token();
        $this->assertNull($result);
    }

    public function testGetBearerTokenHandlesMalformedBearer()
    {
        $_SERVER['Authorization'] = 'Bearer';
        $result = get_bearer_token();
        $this->assertNull($result);
    }

    public function testGetBearerTokenCaseSensitive()
    {
        $_SERVER['Authorization'] = 'bearer lowercase-token';
        $result = get_bearer_token();
        $this->assertNull($result); // Should not match lowercase 'bearer'
    }

    public function testGetBearerTokenWithExtraSpaces()
    {
        $_SERVER['Authorization'] = 'Bearer  token-with-spaces';
        $result = get_bearer_token();
        $this->assertEquals('token-with-spaces', $result);
    }
}
