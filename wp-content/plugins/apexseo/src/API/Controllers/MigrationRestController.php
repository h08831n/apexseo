<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * REST API Controller for SEO Plugin Data Migration (API-22).
 */
class MigrationRestController extends AbstractRestController {
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
        // POST /apexseo/v1/migration/run (API-22)
        $this->registerRoute('/migration/run', [
            'methods'             => 'POST',
            'callback'            => [$this, 'executeMigration'],
            'permission_callback' => [$this, 'checkAdminPermission'],
            'args'                => [
                'source' => [
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => ['yoast', 'rank_math', 'aioseo', 'seopress', 'tsf', 'redirection', 'wp_rocket'],
                ],
                'batch_size' => [
                    'required' => false,
                    'type'     => 'integer',
                    'default'  => 500,
                ],
                'offset' => [
                    'required' => false,
                    'type'     => 'integer',
                    'default'  => 0,
                ],
            ],
        ]);
    }

    /**
     * Execute step-by-step batched migration from legacy SEO plugins (API-22).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function executeMigration($request) {
        $params    = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;
        $source    = isset($params['source']) ? $params['source'] : '';
        $batchSize = isset($params['batch_size']) ? (int) $params['batch_size'] : 500;
        $offset    = isset($params['offset']) ? (int) $params['offset'] : 0;

        $validSources = ['yoast', 'rank_math', 'aioseo', 'seopress', 'tsf', 'redirection', 'wp_rocket'];
        if (!in_array($source, $validSources, true)) {
            return $this->error('apexseo_invalid_migration_source', 'Unsupported migration source.', 422);
        }

        $migratedCount = 0;
        $totalFound    = 0;
        $indexableTable = $this->db->getPrefix() . 'apex_indexables';

        // Migrate Yoast post meta
        if ($source === 'yoast') {
            $postMetaTable = $this->db->getPrefix() . 'postmeta';
            $rows = $this->db->getResults("SELECT post_id, meta_key, meta_value FROM {$postMetaTable} WHERE meta_key IN ('_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw') LIMIT {$batchSize} OFFSET {$offset}");
            
            if (is_array($rows)) {
                $totalFound = count($rows);
                foreach ($rows as $row) {
                    $postId = (int) $row->post_id;
                    if ($row->meta_key === '_yoast_wpseo_title') {
                        $this->db->query("INSERT INTO {$indexableTable} (object_type, object_id, title) VALUES ('post', {$postId}, '" . addslashes($row->meta_value) . "') ON DUPLICATE KEY UPDATE title = VALUES(title)");
                    } elseif ($row->meta_key === '_yoast_wpseo_metadesc') {
                        $this->db->query("INSERT INTO {$indexableTable} (object_type, object_id, description) VALUES ('post', {$postId}, '" . addslashes($row->meta_value) . "') ON DUPLICATE KEY UPDATE description = VALUES(description)");
                    } elseif ($row->meta_key === '_yoast_wpseo_focuskw') {
                        $this->db->query("INSERT INTO {$indexableTable} (object_type, object_id, primary_focus_keyword) VALUES ('post', {$postId}, '" . addslashes($row->meta_value) . "') ON DUPLICATE KEY UPDATE primary_focus_keyword = VALUES(primary_focus_keyword)");
                    }
                    $migratedCount++;
                }
            }
        } elseif ($source === 'rank_math') {
            $postMetaTable = $this->db->getPrefix() . 'postmeta';
            $rows = $this->db->getResults("SELECT post_id, meta_key, meta_value FROM {$postMetaTable} WHERE meta_key IN ('rank_math_title', 'rank_math_description', 'rank_math_focus_keyword') LIMIT {$batchSize} OFFSET {$offset}");
            
            if (is_array($rows)) {
                $totalFound = count($rows);
                foreach ($rows as $row) {
                    $postId = (int) $row->post_id;
                    if ($row->meta_key === 'rank_math_title') {
                        $this->db->query("INSERT INTO {$indexableTable} (object_type, object_id, title) VALUES ('post', {$postId}, '" . addslashes($row->meta_value) . "') ON DUPLICATE KEY UPDATE title = VALUES(title)");
                    } elseif ($row->meta_key === 'rank_math_description') {
                        $this->db->query("INSERT INTO {$indexableTable} (object_type, object_id, description) VALUES ('post', {$postId}, '" . addslashes($row->meta_value) . "') ON DUPLICATE KEY UPDATE description = VALUES(description)");
                    } elseif ($row->meta_key === 'rank_math_focus_keyword') {
                        $this->db->query("INSERT INTO {$indexableTable} (object_type, object_id, primary_focus_keyword) VALUES ('post', {$postId}, '" . addslashes($row->meta_value) . "') ON DUPLICATE KEY UPDATE primary_focus_keyword = VALUES(primary_focus_keyword)");
                    }
                    $migratedCount++;
                }
            }
        }

        $nextOffset = $offset + $migratedCount;
        $isCompleted = $migratedCount < $batchSize;

        return $this->success([
            'success'          => true,
            'source'           => $source,
            'status'           => $isCompleted ? 'completed' : 'processing',
            'migrated_records' => $migratedCount,
            'current_offset'   => $offset,
            'next_offset'      => $isCompleted ? null : $nextOffset,
        ]);
    }
}
