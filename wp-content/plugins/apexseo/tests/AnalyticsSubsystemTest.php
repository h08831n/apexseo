<?php
namespace ApexSEO\Tests;

use ApexSEO\Analytics\Monitoring\FourOhFourMonitor;
use ApexSEO\Analytics\Tracking\RankTracker;
use ApexSEO\Core\Database\DatabaseManager;

class AnalyticsSubsystemTest extends TestCase {
    public function testFourOhFourLogging() {
        $db = new DatabaseManager();
        $monitor = new FourOhFourMonitor($db);

        $logged = $monitor->log404('/missing-broken-link/', 'https://google.com', 'Mozilla/5.0');
        $this->assertTrue($logged);

        $recent = $monitor->getRecent404s(10);
        $this->assertTrue(is_array($recent));
    }

    public function testRankTrackerKeywords() {
        $db = new DatabaseManager();
        $tracker = new RankTracker($db);

        $id = $tracker->trackKeyword('fastest seo plugin', 'https://example.com/fast-seo/');
        $this->assertTrue(is_numeric($id) || $id === true);

        $history = $tracker->recordRankPosition(1, 3);
        $this->assertTrue($history);
    }
}
