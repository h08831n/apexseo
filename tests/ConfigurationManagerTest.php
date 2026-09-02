<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Configuration Manager Test.
 */
class ConfigurationManagerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        global $mock_wp_options;
        $mock_wp_options = [];
    }

    public function testDefaultValuesLoaded() {
        $config = new ConfigurationManager();

        $this->assertEquals('-', $config->get('seo.title_separator'));
        $this->assertEquals(true, $config->get('perf.page_cache_enabled'));
        $this->assertEquals('Organization', $config->get('schema.site_type'));
        $this->assertEquals(true, $config->isModuleEnabled('seo'));
    }

    public function testDotNotationGetAndSet() {
        $config = new ConfigurationManager();

        $config->set('seo.title_separator', '|');
        $this->assertEquals('|', $config->get('seo.title_separator'));

        $config->set('custom_domain.nested.key', 42);
        $this->assertEquals(42, $config->get('custom_domain.nested.key'));
    }

    public function testModuleStatusToggling() {
        $config = new ConfigurationManager();

        $this->assertTrue($config->isModuleEnabled('seo'));
        $config->setModuleStatus('seo', false);
        $this->assertFalse($config->isModuleEnabled('seo'));
    }

    public function testConfigurationPersistence() {
        $config = new ConfigurationManager();
        $config->set('seo.title_separator', '>>');
        $config->save('seo');

        // New instance reading saved option
        $config2 = new ConfigurationManager();
        $this->assertEquals('>>', $config2->get('seo.title_separator'));
    }
}
