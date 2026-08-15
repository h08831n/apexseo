<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Multisite\MultisiteManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Multisite Manager Test.
 */
class MultisiteManagerTest extends TestCase {
    public function testSingleSiteDefaults() {
        $manager = new MultisiteManager();

        $this->assertFalse($manager->isMultisite());
        $this->assertFalse($manager->isNetworkAdmin());
        $this->assertTrue($manager->isMainSite());
        $this->assertEquals(1, $manager->getCurrentBlogId());
        $this->assertEquals(1, $manager->getMainSiteId());
        $this->assertEquals([1], $manager->getSiteIds());
    }

    public function testRunInBlogContextExecution() {
        $manager = new MultisiteManager();

        $executed = false;
        $result = $manager->runInBlogContext(1, function() use (&$executed) {
            $executed = true;
            return 'blog_1_result';
        });

        $this->assertTrue($executed);
        $this->assertEquals('blog_1_result', $result);
    }
}
