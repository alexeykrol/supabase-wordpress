<?php

use PHPUnit\Framework\TestCase;

/**
 * Registration Pairs Test
 *
 * Tests Thank You page lookup logic for Registration Pairs
 */
class RegistrationPairsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset WordPress options before each test
        sb_test_reset_options();
    }

    // ========== THANK YOU URL LOOKUP TESTS ==========

    public function testFindThankYouUrlForRegistrationPage()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/test-page/',
                'thankyou_page_url' => '/thank-you/'
            ]
        ];

        $result = sb_test_get_thankyou_url_for_registration('/test-page/', $pairs);

        $this->assertEquals('https://example.com/thank-you/', $result);
    }

    public function testNoMatchingPairReturnsNull()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/page-a/',
                'thankyou_page_url' => '/thanks-a/'
            ]
        ];

        $result = sb_test_get_thankyou_url_for_registration('/page-b/', $pairs);

        $this->assertNull($result);
    }

    public function testMultiplePairsFindsCorrectOne()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/page-a/',
                'thankyou_page_url' => '/thanks-a/'
            ],
            [
                'id' => '223e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/page-b/',
                'thankyou_page_url' => '/thanks-b/'
            ],
            [
                'id' => '323e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/page-c/',
                'thankyou_page_url' => '/thanks-c/'
            ]
        ];

        $result = sb_test_get_thankyou_url_for_registration('/page-b/', $pairs);

        $this->assertEquals('https://example.com/thanks-b/', $result);
    }

    public function testInvalidRegistrationUrlReturnsNull()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/test-page/',
                'thankyou_page_url' => '/thank-you/'
            ]
        ];

        // Invalid URL (XSS attempt)
        $result = sb_test_get_thankyou_url_for_registration('/<script>alert(1)</script>/', $pairs);

        $this->assertNull($result);
    }

    public function testEmptyPairsArrayReturnsNull()
    {
        $pairs = [];

        $result = sb_test_get_thankyou_url_for_registration('/test-page/', $pairs);

        $this->assertNull($result);
    }

    public function testExactMatchOnly()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/test/',
                'thankyou_page_url' => '/thanks/'
            ]
        ];

        // Should not match partial URLs
        $this->assertNull(sb_test_get_thankyou_url_for_registration('/test', $pairs));
        $this->assertNull(sb_test_get_thankyou_url_for_registration('/test/page/', $pairs));
        $this->assertNull(sb_test_get_thankyou_url_for_registration('/testing/', $pairs));

        // Should match exact URL
        $this->assertEquals(
            'https://example.com/thanks/',
            sb_test_get_thankyou_url_for_registration('/test/', $pairs)
        );
    }

    public function testCaseSensitiveMatching()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/Test-Page/',
                'thankyou_page_url' => '/thank-you/'
            ]
        ];

        // URLs are case-sensitive
        $this->assertNull(sb_test_get_thankyou_url_for_registration('/test-page/', $pairs));
        $this->assertEquals(
            'https://example.com/thank-you/',
            sb_test_get_thankyou_url_for_registration('/Test-Page/', $pairs)
        );
    }

    public function testReturnsAbsoluteUrl()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/page/',
                'thankyou_page_url' => '/thanks/'
            ]
        ];

        $result = sb_test_get_thankyou_url_for_registration('/page/', $pairs);

        // Should return absolute URL, not relative path
        $this->assertStringStartsWith('https://', $result);
        $this->assertStringContainsString('example.com', $result);
    }

    public function testHandlesTrailingSlashVariations()
    {
        $pairs = [
            [
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'registration_page_url' => '/test/',
                'thankyou_page_url' => '/thanks'
            ]
        ];

        // Thank you URL without trailing slash
        $result = sb_test_get_thankyou_url_for_registration('/test/', $pairs);
        $this->assertEquals('https://example.com/thanks', $result);
    }
}
