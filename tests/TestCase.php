<?php
namespace ApexSEO\Tests;

/**
 * Authoritative PHPUnit 9.6 Base Test Case for Apex SEO.
 * Inherits cleanly from PHPUnit\Framework\TestCase with helper assertion aliases.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {
    /**
     * @var int Assertion count helper
     */
    public static $assertionCount = 0;

    /**
     * Legacy assertion alias.
     */
    public function assertStringContains($needle, $haystack, string $message = ''): void {
        $this->assertStringContainsString((string)$needle, (string)$haystack, $message);
    }

    /**
     * Legacy assertion alias.
     */
    public function assertStringNotContains($needle, $haystack, string $message = ''): void {
        $this->assertStringNotContainsString((string)$needle, (string)$haystack, $message);
    }
}
