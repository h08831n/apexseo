<?php
namespace ApexSEO\Tests;

use ApexSEO\Autoloader;

/**
 * PSR-4 Autoloader Test.
 */
class AutoloaderTest extends TestCase {
    public function testAutoloaderLoadsExistingCoreClass() {
        $loaded = Autoloader::loadClass('ApexSEO\\Core\\Container\\Container');
        $this->assertTrue($loaded);
        $this->assertTrue(class_exists('ApexSEO\\Core\\Container\\Container', false));
    }

    public function testAutoloaderIgnoresForeignNamespace() {
        $loaded = Autoloader::loadClass('Symfony\\Component\\HttpFoundation\\Request');
        $this->assertFalse($loaded);
    }

    public function testAutoloaderReturnsFalseForNonExistentClass() {
        $loaded = Autoloader::loadClass('ApexSEO\\Core\\NonExistentClassXYZ');
        $this->assertFalse($loaded);
    }
}
