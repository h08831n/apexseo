<?php
namespace ApexSEO\Tests;

use ApexSEO\Analytics\Monitor\FourOhFourMonitor;
use ApexSEO\Analytics\Tracker\RankTracker;
use ApexSEO\Core\Database\DatabaseManager;

class AnalyticsSubsystemTest extends TestCase {
    public function testFourOhFourLogging() {
        $db = new DatabaseManager();
        $monitor = new FourOhFourMonitor($db);

        $monitor->record404('/missing-broken-link/', '127.0.0.1', 'Mozilla/5.0');
        $recent = $monitor->getRecent404s();
        $this->assertTrue(is_array($recent));
        $this->assertCount(1, $recent);
        $this->assertEquals('/missing-broken-link/', $recent[0]['url']);
    }

    public function testRankTrackerKeywords() {
        $db = new DatabaseManager();
        $tracker = new RankTracker($db);

        $tracker->trackKeyword('fastest seo plugin', 3, 'https://example.com/fast-seo/');
        $keywords = $tracker->getTrackedKeywords();
        $this->assertTrue(is_array($keywords));
        $this->assertCount(1, $keywords);
        $this->assertEquals(3, $keywords[0]['position']);
    }
}
