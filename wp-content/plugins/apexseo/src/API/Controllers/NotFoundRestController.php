<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * REST API Controller for 404 Error Log Monitoring (API-13, API-14).
 */
class NotFoundRestController extends AbstractRestController {
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
        // GET /apexseo/v1/monitor/404 (API-13)
        $this->registerRoute('/monitor/404', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get404Logs'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // DELETE /apexseo/v1/monitor/404 (API-14)
        $this->registerRoute('/monitor/404', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'clear404Logs'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);
    }

    /**
     * Get 404 error log entries (API-13).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function get404Logs($request = null) {
        $table = $this->db->getPrefix() . 'apex_404_logs';
        $results = $this->db->getResults("SELECT * FROM {$table} ORDER BY hits DESC, last_accessed DESC LIMIT 100");

        return $this->success([
            'success' => true,
            'logs'    => is_array($results) ? $results : [],
            'count'   => is_array($results) ? count($results) : 0,
        ]);
    }

    /**
     * Clear all or specific 404 error logs (API-14).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function clear404Logs($request = null) {
        $table = $this->db->getPrefix() . 'apex_404_logs';
        $this->db->query("TRUNCATE TABLE {$table}");

        return $this->success([
            'success' => true,
            'cleared' => true,
        ]);
    }
}
