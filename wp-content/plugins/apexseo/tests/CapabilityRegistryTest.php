<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Environment\CapabilityRegistry;
use ApexSEO\Core\Environment\EnvironmentDetector;

/**
 * Capability Registry Test.
 */
class CapabilityRegistryTest extends TestCase {
    public function testCapabilityRegistrationAndRetrieval() {
        $detector = new EnvironmentDetector();
        $registry = new CapabilityRegistry($detector);

        $registry->register('custom.test_cap', EnvironmentDetector::STATUS_AVAILABLE, 'TestProvider', ['speed' => 'fast']);

        $this->assertTrue($registry->isAvailable('custom.test_cap'));

        $cap = $registry->get('custom.test_cap');
        $this->assertEquals('custom.test_cap', $cap['id']);
        $this->assertEquals(EnvironmentDetector::STATUS_AVAILABLE, $cap['status']);
        $this->assertEquals('TestProvider', $cap['provider']);
    }

    public function testCoreRegisteredCapabilities() {
        $detector = new EnvironmentDetector();
        $registry = new CapabilityRegistry($detector);

        // Core local AST parser capability should always be AVAILABLE
        $this->assertTrue($registry->isAvailable('asset.local_critical_css_ast'));
        $this->assertTrue($registry->isAvailable('schema.jsonld_builder'));
        $this->assertTrue($registry->isAvailable('link.graph_analyzer'));
    }
}
