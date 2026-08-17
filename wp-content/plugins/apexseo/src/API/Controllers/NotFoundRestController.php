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
        $params  = $request instanceof \WP_REST_Request ? $request->get_params() : $request;
        $page    = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $perPage = isset($params['per_page']) ? max(1, min(100, (int) $params['per_page'])) : 100;
        $offset  = ($page - 1) * $perPage;

        $table   = $this->db->getPrefix() . 'apex_404_logs';
        $total   = (int) $this->db->getVar("SELECT COUNT(*) FROM {$table}");

        $query   = $this->db->prepare("SELECT * FROM {$table} ORDER BY hits DESC, last_accessed DESC LIMIT %d OFFSET %d", $perPage, $offset);
        $results = $this->db->getResults($query);

        return $this->success([
            'success'     => true,
            'logs'        => is_array($results) ? $results : [],
            'count'       => is_array($results) ? count($results) : 0,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ($total > 0) ? (int) ceil($total / $perPage) : 0,
        ]);
    }

    /**
     * Clear all or specific 404 error logs (API-14).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function clear404Logs($request = null) {
        $params = $request instanceof \WP_REST_Request ? $request->get_params() : $request;
        $id     = isset($params['id']) ? (int) $params['id'] : 0;

        $table = $this->db->getPrefix() . 'apex_404_logs';

        if ($id > 0) {
            $this->db->delete($table, ['id' => $id]);
        } else {
            $this->db->query("TRUNCATE TABLE {$table}");
        }

        return $this->success([
            'success' => true,
            'cleared' => true,
            'id'      => $id > 0 ? $id : 'all',
        ]);
    }
}
