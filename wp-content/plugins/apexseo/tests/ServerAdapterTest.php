<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Environment\Server\ApacheAdapter;
use ApexSEO\Core\Environment\Server\NginxAdapter;
use ApexSEO\Core\Environment\Server\LiteSpeedAdapter;
use ApexSEO\Core\Environment\Server\OpenLiteSpeedAdapter;
use ApexSEO\Core\Environment\Server\GenericServerAdapter;

/**
 * Server Adapters Capabilities and Directives Test.
 */
class ServerAdapterTest extends TestCase {
    public function testApacheAdapterCapabilities() {
        $adapter = new ApacheAdapter();
        $this->assertEquals('apache', $adapter->getServerType());
        $this->assertTrue($adapter->supportsHtaccess());
        $this->assertFalse($adapter->supportsNginxDirectives());
        $this->assertFalse($adapter->supportsLiteSpeedEngine());

        $gzipDirectives = $adapter->generateDirectGzipRules(['/wp-content/cache/']);
        $this->assertTrue(strpos($gzipDirectives, '<IfModule mod_rewrite.c>') !== false);
        $this->assertTrue(strpos($gzipDirectives, '.gz') !== false);
    }

    public function testNginxAdapterCapabilities() {
        $adapter = new NginxAdapter();
        $this->assertEquals('nginx', $adapter->getServerType());
        $this->assertFalse($adapter->supportsHtaccess());
        $this->assertTrue($adapter->supportsNginxDirectives());

        $gzipDirectives = $adapter->generateDirectGzipRules(['/wp-content/cache/']);
        $this->assertTrue(strpos($gzipDirectives, 'gzip_static on;') !== false);
    }

    public function testLiteSpeedAdapterCapabilities() {
        $adapter = new LiteSpeedAdapter();
        $this->assertEquals('litespeed', $adapter->getServerType());
        $this->assertTrue($adapter->supportsHtaccess());
        $this->assertTrue($adapter->supportsLiteSpeedEngine());
        $this->assertTrue($adapter->supportsEsi());

        $headers = $adapter->getCacheControlHeaders(['max_age' => 86400, 'tag' => 'apex_post_1']);
        $this->assertEquals('public, max-age=86400', $headers['X-LiteSpeed-Cache-Control']);
        $this->assertEquals('apex_post_1', $headers['X-LiteSpeed-Tag']);
    }

    public function testOpenLiteSpeedAdapterCapabilities() {
        $adapter = new OpenLiteSpeedAdapter();
        $this->assertEquals('openlitespeed', $adapter->getServerType());
        $this->assertTrue($adapter->supportsHtaccess());
        $this->assertTrue($adapter->supportsLiteSpeedEngine());
    }

    public function testGenericServerAdapterCapabilities() {
        $adapter = new GenericServerAdapter();
        $this->assertEquals('generic', $adapter->getServerType());
        $this->assertFalse($adapter->supportsHtaccess());
        $this->assertFalse($adapter->supportsNginxDirectives());
        $this->assertFalse($adapter->supportsLiteSpeedEngine());
    }
}
