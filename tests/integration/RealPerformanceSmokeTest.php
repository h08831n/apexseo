<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real Performance Smoke Test.
 */
class RealPerformanceSmokeTest extends TestCase {
    public function testInProcessCoreBootstrapTiming() {
        if (!class_exists('\\ApexSEO\\Core\\Bootstrap\\Plugin')) {
            $this->markTestSkipped('Apex SEO Plugin class not available.');
        }

        $start = microtime(true);
        $plugin = \ApexSEO\Core\Bootstrap\Plugin::getInstance();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertNotNull($plugin);
        $this->assertLessThan(500, $elapsed, 'Plugin container singleton access should be under 500ms.');
    }
}
