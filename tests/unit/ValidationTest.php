<?php

use PHPUnit\Framework\TestCase;

/**
 * Validation Functions Test
 *
 * Tests email, URL, UUID, and site URL validation functions
 */
class ValidationTest extends TestCase
{
    // ========== EMAIL VALIDATION TESTS ==========

    public function testValidEmail()
    {
        $this->assertTrue(sb_test_validate_email('test@example.com'));
        $this->assertTrue(sb_test_validate_email('user+alias@domain.co.uk'));
        $this->assertTrue(sb_test_validate_email('valid.email@subdomain.example.org'));
    }

    public function testInvalidEmailFormat()
    {
        $this->assertFalse(sb_test_validate_email('not-an-email'));
        $this->assertFalse(sb_test_validate_email('@example.com'));
        $this->assertFalse(sb_test_validate_email('user@'));
        $this->assertFalse(sb_test_validate_email(''));
    }

    public function testEmailXSSProtection()
    {
        $this->assertFalse(sb_test_validate_email('<script>alert(1)</script>@example.com'));
        $this->assertFalse(sb_test_validate_email('user@example.com<script>'));
        $this->assertFalse(sb_test_validate_email('javascript:alert(1)@example.com'));
    }

    public function testEmailTypeValidation()
    {
        $this->assertFalse(sb_test_validate_email(null));
        $this->assertFalse(sb_test_validate_email([]));
        $this->assertFalse(sb_test_validate_email(123));
    }

    public function testEmailLengthValidation()
    {
        // Too long (>254 chars)
        $longEmail = str_repeat('a', 250) . '@example.com';
        $this->assertFalse(sb_test_validate_email($longEmail));
    }

    // ========== URL PATH VALIDATION TESTS ==========

    public function testValidUrlPath()
    {
        $this->assertEquals('/test-page/', sb_test_validate_url_path('/test-page/'));
        $this->assertEquals('/products/item-123/', sb_test_validate_url_path('/products/item-123/'));
        $this->assertEquals('/', sb_test_validate_url_path('/'));
    }

    public function testInvalidUrlPathFormat()
    {
        $this->assertFalse(sb_test_validate_url_path('relative/path')); // Must start with /
        $this->assertFalse(sb_test_validate_url_path(''));
        $this->assertFalse(sb_test_validate_url_path('https://example.com/path')); // Must be path only
    }

    public function testUrlPathXSSProtection()
    {
        $this->assertFalse(sb_test_validate_url_path('/<script>alert(1)</script>/'));
        $this->assertFalse(sb_test_validate_url_path('/javascript:alert(1)'));
        $this->assertFalse(sb_test_validate_url_path('/path?onclick=alert(1)'));
    }

    public function testUrlPathInjectionProtection()
    {
        $this->assertFalse(sb_test_validate_url_path('/path"with"quotes/'));
        $this->assertFalse(sb_test_validate_url_path("/path'with'quotes/"));
        $this->assertFalse(sb_test_validate_url_path('/path%00null'));
    }

    public function testUrlPathTypeValidation()
    {
        $this->assertFalse(sb_test_validate_url_path(null));
        $this->assertFalse(sb_test_validate_url_path([]));
        $this->assertFalse(sb_test_validate_url_path(123));
    }

    // ========== UUID VALIDATION TESTS ==========

    public function testValidUUID()
    {
        $this->assertEquals(
            '123e4567-e89b-42d3-a456-426614174000',
            sb_test_validate_uuid('123e4567-e89b-42d3-a456-426614174000')
        );
        $this->assertEquals(
            '550e8400-e29b-41d4-a716-446655440000',
            sb_test_validate_uuid('550e8400-e29b-41d4-a716-446655440000')
        );
    }

    public function testInvalidUUIDFormat()
    {
        $this->assertFalse(sb_test_validate_uuid('not-a-uuid'));
        $this->assertFalse(sb_test_validate_uuid('12345678-1234-1234-1234-123456789012')); // Not v4
        $this->assertFalse(sb_test_validate_uuid(''));
        $this->assertFalse(sb_test_validate_uuid('123e4567e89b42d3a456426614174000')); // Missing dashes
    }

    public function testUUIDTypeValidation()
    {
        $this->assertFalse(sb_test_validate_uuid(null));
        $this->assertFalse(sb_test_validate_uuid([]));
        $this->assertFalse(sb_test_validate_uuid(123));
    }

    // ========== SITE URL VALIDATION TESTS ==========

    public function testValidSiteUrl()
    {
        $this->assertEquals(
            'https://example.com',
            sb_test_validate_site_url('https://example.com')
        );
        $this->assertEquals(
            'http://localhost:8080',
            sb_test_validate_site_url('http://localhost:8080')
        );
        $this->assertEquals(
            'https://subdomain.example.org/path',
            sb_test_validate_site_url('https://subdomain.example.org/path')
        );
    }

    public function testInvalidSiteUrlScheme()
    {
        $this->assertFalse(sb_test_validate_site_url('ftp://example.com'));
        $this->assertFalse(sb_test_validate_site_url('javascript:alert(1)'));
        $this->assertFalse(sb_test_validate_site_url('data:text/html,<script>'));
    }

    public function testSiteUrlXSSProtection()
    {
        $this->assertFalse(sb_test_validate_site_url('https://example.com<script>'));
        $this->assertFalse(sb_test_validate_site_url('https://example.com/path?onclick=alert(1)'));
    }

    public function testSiteUrlTypeValidation()
    {
        $this->assertFalse(sb_test_validate_site_url(null));
        $this->assertFalse(sb_test_validate_site_url([]));
        $this->assertFalse(sb_test_validate_site_url(123));
    }
}
