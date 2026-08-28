<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real WP-CLI Integration Test.
 */
class RealCliIntegrationTest extends TestCase {
    public function testCliManagerRegistersCommands() {
        if (!class_exists('\\WP_CLI')) {
            $this->markTestSkipped('WP-CLI environment not available.');
        }

        $cliManager = new \ApexSEO\Core\CLI\CliManager();
        $commands = $cliManager->getCommands();

        $this->assertArrayHasKey('analysis', $commands);
        $this->assertArrayHasKey('doctor', $commands);
        $this->assertArrayHasKey('cache', $commands);
        $this->assertArrayHasKey('sitemap', $commands);
        $this->assertArrayHasKey('schema', $commands);
        $this->assertArrayHasKey('redirect', $commands);
        $this->assertArrayHasKey('db', $commands);
        $this->assertArrayHasKey('index', $commands);
    }
}
