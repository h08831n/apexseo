<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real Phase 5A Frontend Head Meta & Schema Rendering Integration Test.
 */
class RealPhase5AHeadRenderingTest extends TestCase {
    public function testHeadActionRendersSeoMetaTags() {
        if (!function_exists('wp_head')) {
            $this->markTestSkipped('Real WordPress theme head rendering not available.');
        }

        ob_start();
        wp_head();
        $headOutput = ob_get_clean();

        $this->assertNotEmpty($headOutput, 'wp_head() should produce rendered HTML output.');
        $this->assertStringContainsString('canonical', strtolower($headOutput), 'Head must contain canonical link tag.');
    }

    public function testJsonLdSchemaOutputGeneration() {
        if (!class_exists('\\ApexSEO\\Schema\\SchemaRegistry')) {
            $this->markTestSkipped('Apex SEO Schema Subsystem not available.');
        }

        $registry = new \ApexSEO\Schema\SchemaRegistry();
        $this->assertNotEmpty($registry->getSupportedTypes(), 'Schema registry must have supported schema types.');
    }
}
