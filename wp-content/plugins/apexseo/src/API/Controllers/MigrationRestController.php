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
        $params        = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;
        $source        = isset($params['source']) ? sanitize_key($params['source']) : '';
        $rawBatchSize  = isset($params['batch_size']) ? (int) $params['batch_size'] : 500;
        $rawOffset     = isset($params['offset']) ? (int) $params['offset'] : 0;

        $batchSize = max(1, min(1000, $rawBatchSize));
        $offset    = max(0, $rawOffset);

        $sourceMetaKeys = [
            'yoast'     => ['title' => '_yoast_wpseo_title', 'desc' => '_yoast_wpseo_metadesc', 'kw' => '_yoast_wpseo_focuskw'],
            'rank_math' => ['title' => 'rank_math_title', 'desc' => 'rank_math_description', 'kw' => 'rank_math_focus_keyword'],
            'aioseo'    => ['title' => '_aioseo_title', 'desc' => '_aioseo_description', 'kw' => '_aioseo_keywords'],
            'seopress'  => ['title' => '_seopress_titles_title', 'desc' => '_seopress_titles_desc', 'kw' => '_seopress_analysis_target_kw'],
            'tsf'       => ['title' => '_genesis_title', 'desc' => '_genesis_description', 'kw' => null],
            'redirection' => ['title' => null, 'desc' => null, 'kw' => null],
            'wp_rocket' => ['title' => null, 'desc' => null, 'kw' => null],
        ];

        if (!array_key_exists($source, $sourceMetaKeys)) {
            return $this->error('apexseo_invalid_migration_source', 'Unsupported migration source.', 422);
        }

        $migratedCount = 0;
        $errors        = [];
        $indexableTable = $this->db->getPrefix() . 'apex_indexables';
        $postMetaTable  = $this->db->getPrefix() . 'postmeta';

        $keys = array_filter(array_values($sourceMetaKeys[$source]));

        if (!empty($keys)) {
            $inPlaceholders = implode(', ', array_fill(0, count($keys), '%s'));
            $queryArgs = array_merge($keys, [$batchSize, $offset]);
            $sql = "SELECT post_id, meta_key, meta_value FROM {$postMetaTable} WHERE meta_key IN ({$inPlaceholders}) ORDER BY post_id ASC LIMIT %d OFFSET %d";
            $prepared = $this->db->prepare($sql, ...$queryArgs);
            $rows = $this->db->getResults($prepared);

            if (is_array($rows)) {
                $metaMap = $sourceMetaKeys[$source];
                foreach ($rows as $row) {
                    $postId = (int) $row->post_id;
                    $val    = (string) $row->meta_value;

                    if (!empty($metaMap['title']) && $row->meta_key === $metaMap['title']) {
                        $upsert = $this->db->prepare(
                            "INSERT INTO {$indexableTable} (object_type, object_id, title) VALUES (%s, %d, %s) ON DUPLICATE KEY UPDATE title = VALUES(title)",
                            'post',
                            $postId,
                            $val
                        );
                        $this->db->query($upsert);
                        $migratedCount++;
                    } elseif (!empty($metaMap['desc']) && $row->meta_key === $metaMap['desc']) {
                        $upsert = $this->db->prepare(
                            "INSERT INTO {$indexableTable} (object_type, object_id, description) VALUES (%s, %d, %s) ON DUPLICATE KEY UPDATE description = VALUES(description)",
                            'post',
                            $postId,
                            $val
                        );
                        $this->db->query($upsert);
                        $migratedCount++;
                    } elseif (!empty($metaMap['kw']) && $row->meta_key === $metaMap['kw']) {
                        $upsert = $this->db->prepare(
                            "INSERT INTO {$indexableTable} (object_type, object_id, primary_focus_keyword) VALUES (%s, %d, %s) ON DUPLICATE KEY UPDATE primary_focus_keyword = VALUES(primary_focus_keyword)",
                            'post',
                            $postId,
                            $val
                        );
                        $this->db->query($upsert);
                        $migratedCount++;
                    }
                }
            }
        } elseif ($source === 'redirection') {
            // Check for WordPress redirection plugin tables
            $redirTable = $this->db->getPrefix() . 'redirection_items';
            $apexRedirTable = $this->db->getPrefix() . 'apex_redirects';
            if ($this->db->hasTable($redirTable)) {
                $redirSql = $this->db->prepare("SELECT url, action_data, action_code FROM {$redirTable} LIMIT %d OFFSET %d", $batchSize, $offset);
                $rows = $this->db->getResults($redirSql);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $sUrl = (string) $row->url;
                        $sHash = md5($sUrl);
                        $tUrl = (string) $row->action_data;
                        $sCode = (int) $row->action_code ?: 301;
                        $upsert = $this->db->prepare(
                            "INSERT INTO {$apexRedirTable} (source_url, source_url_hash, target_url, status_code, status, hits_count) VALUES (%s, %s, %s, %d, 'active', 0) ON DUPLICATE KEY UPDATE target_url = VALUES(target_url), status_code = VALUES(status_code)",
                            $sUrl,
                            $sHash,
                            $tUrl,
                            $sCode
                        );
                        $this->db->query($upsert);
                        $migratedCount++;
                    }
                }
            }
        }

        $nextOffset = $offset + $migratedCount;
        $isCompleted = ($migratedCount < $batchSize);

        return $this->success([
            'success'          => true,
            'source'           => $source,
            'status'           => $isCompleted ? 'completed' : 'processing',
            'migrated_records' => $migratedCount,
            'current_offset'   => $offset,
            'next_offset'      => $isCompleted ? null : $nextOffset,
            'batch_size'       => $batchSize,
            'errors'           => $errors,
        ]);
    }
}
