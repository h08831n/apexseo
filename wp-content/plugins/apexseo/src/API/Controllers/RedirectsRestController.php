<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * REST API Controller for 301/302 Redirect Management (API-09, API-10, API-11, API-12).
 */
class RedirectsRestController extends AbstractRestController {
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
        // GET /apexseo/v1/redirects (API-09)
        $this->registerRoute('/redirects', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getRedirects'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // POST /apexseo/v1/redirects (API-10)
        $this->registerRoute('/redirects', [
            'methods'             => 'POST',
            'callback'            => [$this, 'createRedirect'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // PUT /apexseo/v1/redirects/{id} (API-11)
        $this->registerRoute('/redirects/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'updateRedirect'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // DELETE /apexseo/v1/redirects/{id} (API-12)
        $this->registerRoute('/redirects/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'deleteRedirect'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);
    }

    /**
     * Get paginated redirect rules (API-09).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function getRedirects($request = null) {
        $table = $this->db->getPrefix() . 'apex_redirects';
        $results = $this->db->getResults("SELECT * FROM {$table} ORDER BY id DESC LIMIT 100");

        return $this->success([
            'success'   => true,
            'redirects' => is_array($results) ? $results : [],
            'count'     => is_array($results) ? count($results) : 0,
        ]);
    }

    /**
     * Create new redirect rule (API-10).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function createRedirect($request) {
        $params = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;

        $sourceUrl  = isset($params['source_url']) ? trim($params['source_url']) : '';
        $targetUrl  = isset($params['target_url']) ? trim($params['target_url']) : '';
        $statusCode = isset($params['status_code']) ? (int) $params['status_code'] : 301;
        $isRegex    = !empty($params['is_regex']) ? 1 : 0;

        if (empty($sourceUrl) || empty($targetUrl)) {
            return $this->error('apexseo_invalid_urls', 'source_url and target_url are required.', 422);
        }

        // Normalize source path
        $sourcePath = '/' . ltrim(parse_url($sourceUrl, PHP_URL_PATH), '/');
        $sourceHash = md5($sourcePath);

        $table = $this->db->getPrefix() . 'apex_redirects';
        $inserted = $this->db->insert($table, [
            'source_url'  => $sourcePath,
            'source_hash' => $sourceHash,
            'target_url'  => sanitize_text_field($targetUrl),
            'status_code' => in_array($statusCode, [301, 302, 307, 308]) ? $statusCode : 301,
            'is_regex'    => $isRegex,
            'hits'        => 0,
            'created_at'  => gmdate('Y-m-d H:i:s'),
        ]);

        if (!$inserted) {
            return $this->error('apexseo_redirect_save_failed', 'Failed to save redirect rule. Check for duplicates.', 500);
        }

        return $this->success([
            'success'     => true,
            'id'          => $this->db->getInsertId(),
            'source_url'  => $sourcePath,
            'target_url'  => $targetUrl,
            'status_code' => $statusCode,
        ], 201);
    }

    /**
     * Update existing redirect rule (API-11).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateRedirect($request) {
        $id     = $request instanceof \WP_REST_Request ? (int) $request->get_param('id') : (int) $request['id'];
        $params = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;

        if (!$id) {
            return $this->error('apexseo_invalid_id', 'Valid redirect ID required.', 400);
        }

        $table = $this->db->getPrefix() . 'apex_redirects';
        $updateData = [];

        if (isset($params['source_url'])) {
            $sourcePath = '/' . ltrim(parse_url($params['source_url'], PHP_URL_PATH), '/');
            $updateData['source_url']  = $sourcePath;
            $updateData['source_hash'] = md5($sourcePath);
        }
        if (isset($params['target_url'])) {
            $updateData['target_url'] = sanitize_text_field($params['target_url']);
        }
        if (isset($params['status_code'])) {
            $updateData['status_code'] = (int) $params['status_code'];
        }
        if (isset($params['is_regex'])) {
            $updateData['is_regex'] = (int) $params['is_regex'];
        }

        if (empty($updateData)) {
            return $this->error('apexseo_no_data', 'No update payload supplied.', 400);
        }

        $this->db->update($table, $updateData, ['id' => $id]);

        return $this->success([
            'success' => true,
            'id'      => $id,
            'updated' => true,
        ]);
    }

    /**
     * Delete redirect rule (API-12).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function deleteRedirect($request) {
        $id = $request instanceof \WP_REST_Request ? (int) $request->get_param('id') : (int) $request['id'];

        if (!$id) {
            return $this->error('apexseo_invalid_id', 'Valid redirect ID required.', 400);
        }

        $table = $this->db->getPrefix() . 'apex_redirects';
        $this->db->delete($table, ['id' => $id]);

        return $this->success([
            'success' => true,
            'id'      => $id,
            'deleted' => true,
        ]);
    }
}
