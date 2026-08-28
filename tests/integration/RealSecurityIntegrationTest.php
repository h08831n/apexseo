<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real Security Integration Test.
 */
class RealSecurityIntegrationTest extends TestCase {
    public function testSecurityManagerRejectsUnauthorizedActions() {
        if (!class_exists('\\ApexSEO\\Core\\Security\\SecurityManager')) {
            $this->markTestSkipped('SecurityManager not available.');
        }

        $sec = new \ApexSEO\Core\Security\SecurityManager();
        $this->assertFalse($sec->verifyNonce('invalid_nonce_token', 'apexseo_action'));
    }
}
