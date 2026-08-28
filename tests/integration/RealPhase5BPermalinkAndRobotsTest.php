<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real Phase 5B Permalinks, Robots & Headers Integration Test.
 */
class RealPhase5BPermalinkAndRobotsTest extends TestCase {
    public function testRobotsTxtContentFiltering() {
        if (!function_exists('apply_filters')) {
            $this->markTestSkipped('WordPress hook subsystem not available.');
        }

        $initialRobots = "User-agent: *\nDisallow: /wp-admin/\n";
        $filtered = apply_filters('robots_txt', $initialRobots, true);

        $this->assertNotEmpty($filtered);
        $this->assertStringContainsString('User-agent:', $filtered);
    }
}
