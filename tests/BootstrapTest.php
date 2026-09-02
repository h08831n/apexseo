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
    protected function setUp(): void {
        parent::setUp();
        Plugin::reset();
    }

    protected function tearDown(): void {
        Plugin::reset();
        parent::tearDown();
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

    public function testAllTestClassesHaveVoidLifecycleSignatures() {
        $testDir = __DIR__;
        $files = glob($testDir . '/*Test.php');
        $lifecycleMethods = ['setUp', 'tearDown', 'setUpBeforeClass', 'tearDownAfterClass'];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            foreach ($lifecycleMethods as $method) {
                if (preg_match_all('/function\s+' . $method . '\s*\([^)]*\)\s*(:[^{]+)?\s*\{/i', $content, $matches)) {
                    foreach ($matches[1] as $returnType) {
                        $this->assertNotEmpty(
                            trim($returnType),
                            "Method {$method}() in " . basename($file) . " must have explicit ': void' return type."
                        );
                        $this->assertStringContainsString(
                            'void',
                            $returnType,
                            "Method {$method}() in " . basename($file) . " must return void."
                        );
                    }
                }
            }
        }
    }
}
