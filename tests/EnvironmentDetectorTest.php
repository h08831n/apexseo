<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Environment\EnvironmentDetector;
use ApexSEO\Core\Environment\Server\ServerAdapterInterface;

/**
 * Environment Detector Test.
 */
class EnvironmentDetectorTest extends TestCase {
    public function testPhpAndWordPressVersionDetection() {
        $detector = new EnvironmentDetector();

        $phpVer = $detector->getPhpVersion();
        $this->assertNotNull($phpVer);
        $this->assertTrue(version_compare($phpVer, '7.4.0', '>='));

        $this->assertTrue($detector->isSupportedPhp());
        $this->assertTrue($detector->isSupportedWordPress());
    }

    public function testExtensionDetection() {
        $detector = new EnvironmentDetector();

        // Check loaded/unloaded extensions
        $this->assertEquals(extension_loaded('mbstring'), $detector->hasExtension('mbstring'));
        $this->assertTrue($detector->hasExtension('json'));

        $status = $detector->getExtensionStatus('json');
        $this->assertEquals(EnvironmentDetector::STATUS_AVAILABLE, $status);

        $fakeStatus = $detector->getExtensionStatus('non_existent_fake_ext_xyz');
        $this->assertEquals(EnvironmentDetector::STATUS_UNAVAILABLE, $fakeStatus);
    }

    public function testServerAdapterResolution() {
        $detector = new EnvironmentDetector();
        $adapter = $detector->getServerAdapter();

        $this->assertInstanceOf(ServerAdapterInterface::class, $adapter);
    }
}
