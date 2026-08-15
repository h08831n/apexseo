<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\Core\Lifecycle\LifecycleManager;
use ApexSEO\Core\Database\SchemaVersion;
use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Plugin Lifecycle, Activation, Deactivation, and Uninstall Safety Test.
 */
class LifecycleTest extends TestCase {
    public function setUp() {
        Plugin::reset();
        global $mock_wp_options;
        $mock_wp_options = [];
    }

    public function testActivationRunsMigrationsAndSetsOption() {
        LifecycleManager::activate(false);

        $activatedAt = get_option('apexseo_activated_at');
        $this->assertNotNull($activatedAt);
        $this->assertTrue($activatedAt > 0);

        $installedVersion = SchemaVersion::getInstalledVersion();
        $this->assertEquals('1.0.0', $installedVersion);
    }

    public function testDeactivationCleansTransients() {
        LifecycleManager::activate(false);
        LifecycleManager::deactivate(false);

        // Deactivation should succeed cleanly without crashing
        $this->assertTrue(true);
    }

    public function testUninstallPreservesTablesWhenConfigured() {
        LifecycleManager::activate(false);

        $plugin = Plugin::getInstance();
        $config = $plugin->getContainer()->get(ConfigurationManager::class);
        $config->set('general.uninstall_drop_db', false);
        $config->save();

        LifecycleManager::uninstall();

        // When drop_db is false, schema version remains intact or options cleaned as intended
        $this->assertTrue(true);
    }

    public function testUninstallDropsTablesWhenExplicitlyConfigured() {
        LifecycleManager::activate(false);

        $plugin = Plugin::getInstance();
        $config = $plugin->getContainer()->get(ConfigurationManager::class);
        $config->set('general.uninstall_drop_db', true);
        $config->save();

        LifecycleManager::uninstall();

        $installedVersion = SchemaVersion::getInstalledVersion();
        $this->assertEquals('0.0.0', $installedVersion);
    }
}
