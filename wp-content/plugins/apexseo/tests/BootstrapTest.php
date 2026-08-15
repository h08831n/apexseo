<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Environment\EnvironmentDetector;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Plugin Bootstrap and Core Container Integration Test.
 */
class BootstrapTest extends TestCase {
    public function setUp() {
        Plugin::reset();
    }

    public function tearDown() {
        Plugin::reset();
    }

    public function testPluginSingletonInstance() {
        $plugin1 = Plugin::getInstance();
        $plugin2 = Plugin::getInstance();

        $this->assertInstanceOf(Plugin::class, $plugin1);
        $this->assertSame($plugin1, $plugin2);
    }

    public function testPluginContainerInitialization() {
        $plugin = Plugin::getInstance();
        $container = $plugin->getContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue($container->has(EnvironmentDetector::class));
        $this->assertTrue($container->has(ConfigurationManager::class));
        $this->assertTrue($container->has(DatabaseManager::class));
    }

    public function testPluginBootSequence() {
        $plugin = Plugin::getInstance();
        $this->assertFalse($plugin->isBooted());

        $plugin->boot();
        $this->assertTrue($plugin->isBooted());

        // Idempotency check: calling boot again does not throw
        $plugin->boot();
        $this->assertTrue($plugin->isBooted());
    }
}
