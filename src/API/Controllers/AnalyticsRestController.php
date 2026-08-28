<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Analytics\Tracker\RankTracker;

class AnalyticsRestController extends AbstractRestController {
    private $rankTracker;
    private $db;

    public function __construct(SecurityManager $security, RankTracker $rankTracker, ?DatabaseManager $db = null) {
        parent::__construct($security);
        $this->rankTracker = $rankTracker;
        $this->db = $db;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/analytics/overview', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getOverview'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/analytics/rank-tracker', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getRankings'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);
    }

    public function getOverview($request) {
        $keywords = $this->rankTracker->getKeywords();
        $indexedPages = 0;
        if ($this->db) {
            $table = $this->db->getTableName(DatabaseManager::TABLE_INDEXABLES);
            $count = $this->db->get_var("SELECT COUNT(*) FROM {$table}");
            $indexedPages = (int)$count;
        }

        $top10 = 0;
        foreach ($keywords as $kw) {
            if (isset($kw['position']) && (int)$kw['position'] > 0 && (int)$kw['position'] <= 10) {
                $top10++;
            }
        }

        return $this->sendResponse([
            'success' => true,
            'overview' => [
                'tracked_keywords' => count($keywords),
                'top_10_count'     => $top10,
                'indexed_pages'    => $indexedPages,
            ]
        ]);
    }

    public function getRankings($request) {
        $keywords = $this->rankTracker->getKeywords();
        return $this->sendResponse([
            'success'  => true,
            'rankings' => $keywords,
        ]);
    }
}
