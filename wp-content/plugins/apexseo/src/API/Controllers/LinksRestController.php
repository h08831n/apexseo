<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * REST API Controller for Internal Link Suggestions & Graph (API-15).
 */
class LinksRestController extends AbstractRestController {
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
        // GET /apexseo/v1/links/suggestions (API-15)
        $this->registerRoute('/links/suggestions', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getSuggestions'],
            'permission_callback' => [$this, 'checkEditorPermission'],
            'args'                => [
                'post_id' => [
                    'required' => true,
                    'type'     => 'integer',
                ],
            ],
        ]);
    }

    /**
     * Get internal link suggestions based on keywords and content relevance (API-15).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function getSuggestions($request) {
        $postId = $request instanceof \WP_REST_Request ? (int) $request->get_param('post_id') : (isset($request['post_id']) ? (int) $request['post_id'] : 0);

        if ($postId <= 0) {
            return $this->error('apexseo_invalid_post_id', 'Valid positive post_id is required.', 400);
        }

        $table = $this->db->getPrefix() . 'apex_indexables';
        $currentQuery = $this->db->prepare("SELECT * FROM {$table} WHERE object_type = %s AND object_id = %d LIMIT 1", 'post', $postId);
        $current = $this->db->getRow($currentQuery);

        $suggestions = [];
        $keyword = (!empty($current) && !empty($current->primary_focus_keyword)) ? trim($current->primary_focus_keyword) : '';

        if (!empty($keyword)) {
            $likePattern = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword) . '%';
            $query = $this->db->prepare(
                "SELECT object_id, title, canonical_url, primary_focus_keyword 
                 FROM {$table} 
                 WHERE object_type = %s 
                   AND object_id != %d 
                   AND (title LIKE %s OR primary_focus_keyword LIKE %s) 
                 LIMIT 10",
                'post',
                $postId,
                $likePattern,
                $likePattern
            );
            $results = $this->db->getResults($query);
            if (is_array($results)) {
                $suggestions = $results;
            }
        }

        // Fallback to recent relevant indexables if no keyword match
        if (empty($suggestions)) {
            $query = $this->db->prepare(
                "SELECT object_id, title, canonical_url 
                 FROM {$table} 
                 WHERE object_type = %s AND object_id != %d 
                 ORDER BY id DESC LIMIT 5",
                'post',
                $postId
            );
            $results = $this->db->getResults($query);
            if (is_array($results)) {
                $suggestions = $results;
            }
        }

        return $this->success([
            'success'     => true,
            'post_id'     => $postId,
            'suggestions' => $suggestions,
        ]);
    }
}
