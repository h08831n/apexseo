<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * REST API Controller for Analytics & Rank Tracking (API-16, API-17).
 */
class AnalyticsRestController extends AbstractRestController {
    /**
     * Database manager.
     *
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param DatabaseManager $db
     */
    public function __construct(SecurityManager $security, DatabaseManager $db) {
        parent::__construct($security);
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes() {
        // GET /apexseo/v1/analytics/overview (API-16)
        $this->registerRoute('/analytics/overview', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getOverview'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // GET /apexseo/v1/analytics/rank-tracker (API-17)
        $this->registerRoute('/analytics/rank-tracker', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getRankTracker'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);
    }

    /**
     * Get overall SEO health score, indexables count, and keyword stats (API-16).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function getOverview($request = null) {
        $indexableTable = $this->db->getPrefix() . 'apex_indexables';
        $redirectTable  = $this->db->getPrefix() . 'apex_redirects';
        $notFoundTable  = $this->db->getPrefix() . 'apex_404_logs';

        $totalIndexables = $this->db->getVar("SELECT COUNT(*) FROM {$indexableTable}");
        $totalRedirects  = $this->db->getVar("SELECT COUNT(*) FROM {$redirectTable}");
        $total404Errors  = $this->db->getVar("SELECT COUNT(*) FROM {$notFoundTable}");

        // Average score
        $avgScore = $this->db->getVar("SELECT AVG(seo_score) FROM {$indexableTable} WHERE seo_score > 0");

        return $this->success([
            'success' => true,
            'metrics' => [
                'total_indexables' => (int) $totalIndexables,
                'total_redirects'  => (int) $totalRedirects,
                'total_404_errors' => (int) $total404Errors,
                'avg_seo_score'    => $avgScore ? round((float) $avgScore, 1) : 85.0,
            ],
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * Get keyword rank tracker positions and distribution (API-17).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function getRankTracker($request = null) {
        $table = $this->db->getPrefix() . 'apex_analytics_keywords';
        $results = $this->db->getResults("SELECT * FROM {$table} ORDER BY position ASC LIMIT 100");

        return $this->success([
            'success'  => true,
            'keywords' => is_array($results) ? $results : [],
            'count'    => is_array($results) ? count($results) : 0,
        ]);
    }
}
