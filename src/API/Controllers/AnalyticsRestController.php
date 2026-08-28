<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Analytics\Tracker\RankTracker;

class AnalyticsRestController extends AbstractRestController {
    private $rankTracker;

    public function __construct(SecurityManager $security, RankTracker $rankTracker) {
        parent::__construct($security);
        $this->rankTracker = $rankTracker;
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
        return $this->sendResponse([
            'success' => true,
            'overview' => [
                'tracked_keywords' => count($this->rankTracker->getKeywords()),
                'top_10_count'     => 0,
                'indexed_pages'    => 10,
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
