<?php
namespace ApexSEO\SEO\Analysis;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Contracts\HookableInterface;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Logging\LoggerInterface;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Models\Indexable;

/**
 * Production Content Analysis Integration Service (APEX-048 through APEX-054).
 *
 * Coordinates real WordPress save_post workflows, cache invalidation, database persistence,
 * recursion guards, and non-blocking failure isolation for all on-page analyzers.
 */
class ContentAnalysisService implements ServiceContractInterface, HookableInterface {
    /**
     * Content analyzer engine.
     *
     * @var ContentAnalyzer
     */
    protected $contentAnalyzer;

    /**
     * Database manager.
     *
     * @var DatabaseManager|null
     */
    protected $db;

    /**
     * Indexable repository.
     *
     * @var IndexableRepository|null
     */
    protected $indexableRepository;

    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Recursion prevention registry for in-flight post IDs.
     *
     * @var array<int, bool>
     */
    protected static $inFlight = [];

    /**
     * Memory cache for analysis records during request lifecycle.
     *
     * @var array<int, array>
     */
    protected static $memoryCache = [];

    /**
     * Supported post types for automatic analysis.
     *
     * @var array<string>
     */
    protected $supportedPostTypes = ['post', 'page', 'product'];

    /**
     * Constructor.
     *
     * @param ContentAnalyzer $contentAnalyzer
     * @param DatabaseManager|null $db
     * @param IndexableRepository|null $indexableRepository
     * @param ConfigurationManager|null $config
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ContentAnalyzer $contentAnalyzer,
        $db = null,
        $indexableRepository = null,
        $config = null,
        $logger = null
    ) {
        $this->contentAnalyzer = $contentAnalyzer;
        $this->db = $db;
        $this->indexableRepository = $indexableRepository;
        $this->config = $config;
        $this->logger = $logger;

        $this->ensureTable();
    }

    /**
     * {@inheritdoc}
     */
    public function registerHooks() {
        if (function_exists('add_action')) {
            add_action('save_post', [$this, 'handleSavePost'], 20, 2);
            add_action('delete_post', [$this, 'handleDeletePost'], 20, 1);
        }
    }

    /**
     * Ensure the custom relational persistence table `wp_apex_content_analysis` exists.
     *
     * @return bool
     */
    public function ensureTable() {
        if (!$this->db) {
            return false;
        }

        $prefix = $this->db->getPrefix();
        $charsetCollate = $this->db->getCharsetCollate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_content_analysis` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_type` VARCHAR(32) NOT NULL DEFAULT 'post',
  `object_id` BIGINT UNSIGNED NOT NULL,
  `analyzer_version` VARCHAR(32) NOT NULL DEFAULT '1.0.0',
  `schema_version` VARCHAR(32) NOT NULL DEFAULT '1.0.0',
  `analysis_hash` VARCHAR(64) NOT NULL,
  `composite_score` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `seo_score` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `readability_score` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `keyword_metrics` LONGTEXT NULL,
  `readability_metrics` LONGTEXT NULL,
  `heading_metrics` LONGTEXT NULL,
  `link_metrics` LONGTEXT NULL,
  `passive_voice_metrics` LONGTEXT NULL,
  `transition_metrics` LONGTEXT NULL,
  `text_structure_metrics` LONGTEXT NULL,
  `analyzed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_object_analysis` (`object_type`, `object_id`),
  KEY `idx_analysis_hash` (`analysis_hash`),
  KEY `idx_analyzed_at` (`analyzed_at`)
) ENGINE=InnoDB {$charsetCollate};";

        return $this->db->delta($sql);
    }

    /**
     * Production save_post hook handler with full failure isolation and caching.
     *
     * @param int $postId
     * @param object|null $post
     * @return array|false Analysis result array on success, false if skipped or failed
     */
    public function handleSavePost($postId, $post = null) {
        $postId = (int) $postId;
        if ($postId <= 0) {
            return false;
        }

        // 1. Guard against autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        // 2. Guard against revisions and autosave helpers
        if (function_exists('wp_is_post_revision') && wp_is_post_revision($postId)) {
            return false;
        }
        if (function_exists('wp_is_post_autosave') && wp_is_post_autosave($postId)) {
            return false;
        }

        // 3. Prevent recursive execution during save_post cascades
        if (isset(self::$inFlight[$postId])) {
            return false;
        }

        // 4. Check global feature enablement
        if ($this->config && !$this->config->get('enable_content_analysis', true)) {
            return false;
        }

        // 5. Fetch and validate post object
        if (!$post || !is_object($post) || !isset($post->post_content)) {
            if (function_exists('get_post')) {
                $post = get_post($postId);
            }
        }

        if (!$post || !is_object($post)) {
            return false;
        }

        $postType = isset($post->post_type) ? $post->post_type : 'post';
        if (!in_array($postType, $this->supportedPostTypes, true)) {
            return false;
        }

        // 6. Execute analysis with complete failure isolation (never break WordPress save)
        try {
            self::$inFlight[$postId] = true;
            $result = $this->executePostAnalysis($postId, $post, false);
            return $result;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error(sprintf('Content analysis failed for post #%d: %s', $postId, $e->getMessage()));
            }
            return false;
        } finally {
            unset(self::$inFlight[$postId]);
        }
    }

    /**
     * Execute post analysis with caching, persistence, and link graph syncing.
     *
     * @param int $postId
     * @param object $post
     * @param bool $forceRecompute
     * @return array
     */
    public function executePostAnalysis($postId, $post, $forceRecompute = false) {
        $content = isset($post->post_content) ? (string) $post->post_content : '';
        $title = isset($post->post_title) ? (string) $post->post_title : '';

        // Extract primary and secondary keywords
        $primaryKeyword = '';
        $secondaryKeywords = [];

        if (isset($_POST['_apexseo_focus_keyword'])) {
            $primaryKeyword = sanitize_text_field($_POST['_apexseo_focus_keyword']);
        } elseif (function_exists('get_post_meta')) {
            $primaryKeyword = (string) get_post_meta($postId, '_apexseo_focus_keyword', true);
        }

        if (isset($_POST['_apexseo_secondary_keywords'])) {
            $rawSec = $_POST['_apexseo_secondary_keywords'];
            $secondaryKeywords = is_array($rawSec) ? array_map('sanitize_text_field', $rawSec) : array_filter(array_map('trim', explode(',', sanitize_text_field($rawSec))));
        } elseif (function_exists('get_post_meta')) {
            $rawSec = get_post_meta($postId, '_apexseo_secondary_keywords', true);
            if (is_array($rawSec)) {
                $secondaryKeywords = $rawSec;
            } elseif (is_string($rawSec) && !empty($rawSec)) {
                $secondaryKeywords = array_filter(array_map('trim', explode(',', $rawSec)));
            }
        }

        // Calculate deterministic analysis hash
        $hash = $this->calculateAnalysisHash($content, $title, $primaryKeyword, $secondaryKeywords);

        // Check if cached result exists in DB and hash is unchanged
        if (!$forceRecompute) {
            $existing = $this->getPersistedAnalysis($postId);
            if ($existing && isset($existing['analysis_hash']) && $existing['analysis_hash'] === $hash) {
                self::$memoryCache[$postId] = $existing;
                return $existing;
            }
        }

        // Run full analysis through ContentAnalyzer (APEX-048..054)
        $options = [
            'post_id'            => $postId,
            'primary_keyword'    => $primaryKeyword,
            'secondary_keywords' => $secondaryKeywords,
        ];

        $analysis = $this->contentAnalyzer->analyzeContent($content, $options);
        $analysis['analysis_hash'] = $hash;
        $analysis['post_id'] = $postId;
        $analysis['analyzed_at'] = gmdate('Y-m-d H:i:s');

        // Persist analysis to database table & update indexables
        $this->persistAnalysis($postId, $analysis);

        self::$memoryCache[$postId] = $analysis;
        return $analysis;
    }

    /**
     * Calculate deterministic content hash.
     *
     * @param string $content
     * @param string $title
     * @param string $primaryKeyword
     * @param array $secondaryKeywords
     * @return string MD5 hash
     */
    public function calculateAnalysisHash($content, $title, $primaryKeyword, array $secondaryKeywords = []) {
        $data = $content . '|' . $title . '|' . mb_strtolower(trim($primaryKeyword), 'UTF-8') . '|' . implode(',', $secondaryKeywords) . '|' . ContentAnalyzer::SCHEMA_VERSION;
        return md5($data);
    }

    /**
     * Persist analysis report into `wp_apex_content_analysis` and update `wp_apex_indexables`.
     *
     * @param int $postId
     * @param array $analysis
     * @return bool
     */
    public function persistAnalysis($postId, array $analysis) {
        $seoScore = (int) ($analysis['seo_score'] ?? 0);
        $readabilityScore = (int) ($analysis['readability_score'] ?? 0);
        $compositeScore = (int) round(($seoScore + $readabilityScore) / 2);
        $hash = $analysis['analysis_hash'] ?? md5((string) microtime(true));

        // 1. Persist to wp_apex_content_analysis
        if ($this->db) {
            $table = $this->db->getPrefix() . 'apex_content_analysis';

            $data = [
                'object_type'            => 'post',
                'object_id'              => $postId,
                'analyzer_version'       => $analysis['analyzer_version'] ?? ContentAnalyzer::ANALYZER_VERSION,
                'schema_version'         => $analysis['schema_version'] ?? ContentAnalyzer::SCHEMA_VERSION,
                'analysis_hash'          => $hash,
                'composite_score'        => $compositeScore,
                'seo_score'              => $seoScore,
                'readability_score'      => $readabilityScore,
                'keyword_metrics'        => json_encode($analysis['keywords'] ?? []),
                'readability_metrics'    => json_encode($analysis['readability'] ?? []),
                'heading_metrics'        => json_encode($analysis['headings'] ?? []),
                'link_metrics'           => json_encode($analysis['links'] ?? []),
                'passive_voice_metrics'  => json_encode($analysis['passive_voice'] ?? []),
                'transition_metrics'     => json_encode($analysis['transition_words'] ?? []),
                'text_structure_metrics' => json_encode($analysis['text_structure'] ?? []),
                'analyzed_at'            => gmdate('Y-m-d H:i:s'),
            ];

            // Check if record already exists
            $checkQuery = $this->db->prepare("SELECT id FROM {$table} WHERE object_type = %s AND object_id = %d LIMIT 1", 'post', $postId);
            $existingId = $this->db->getVar($checkQuery);

            if ($existingId) {
                $this->db->update($table, $data, ['id' => (int) $existingId]);
            } else {
                $this->db->insert($table, $data);
            }
        }

        // 2. Also persist to post meta as fallback
        if (function_exists('update_post_meta')) {
            update_post_meta($postId, '_apexseo_analysis_hash', $hash);
            update_post_meta($postId, '_apexseo_seo_score', $seoScore);
            update_post_meta($postId, '_apexseo_readability_score', $readabilityScore);
            update_post_meta($postId, '_apexseo_content_analysis', $analysis);
        }

        // 3. Update Indexable record in wp_apex_indexables if repository exists
        if ($this->indexableRepository) {
            $indexable = $this->indexableRepository->findByObject('post', $postId);
            if ($indexable) {
                $indexable->seo_score = $seoScore;
                $indexable->readability_score = $readabilityScore;

                if (isset($analysis['links'])) {
                    $indexable->link_count_internal = (int) ($analysis['links']['internal_links'] ?? 0);
                    $indexable->link_count_external = (int) ($analysis['links']['external_links'] ?? 0);
                }

                $this->indexableRepository->save($indexable);
            }
        }

        return true;
    }

    /**
     * Retrieve persisted analysis for a post.
     *
     * @param int $postId
     * @return array|null
     */
    public function getPersistedAnalysis($postId) {
        $postId = (int) $postId;
        if ($postId <= 0) {
            return null;
        }

        if (isset(self::$memoryCache[$postId])) {
            return self::$memoryCache[$postId];
        }

        if ($this->db) {
            $table = $this->db->getPrefix() . 'apex_content_analysis';
            $query = $this->db->prepare("SELECT * FROM {$table} WHERE object_type = %s AND object_id = %d LIMIT 1", 'post', $postId);
            $row = $this->db->getRow($query);

            if ($row) {
                $result = [
                    'schema_version'      => $row->schema_version,
                    'analyzer_version'    => $row->analyzer_version,
                    'analysis_hash'       => $row->analysis_hash,
                    'composite_score'     => (int) $row->composite_score,
                    'seo_score'           => (int) $row->seo_score,
                    'readability_score'   => (int) $row->readability_score,
                    'keywords'            => json_decode($row->keyword_metrics, true) ?: [],
                    'readability'         => json_decode($row->readability_metrics, true) ?: [],
                    'headings'            => json_decode($row->heading_metrics, true) ?: [],
                    'links'               => json_decode($row->link_metrics, true) ?: [],
                    'passive_voice'       => json_decode($row->passive_voice_metrics, true) ?: [],
                    'transition_words'    => json_decode($row->transition_metrics, true) ?: [],
                    'text_structure'      => json_decode($row->text_structure_metrics, true) ?: [],
                    'analyzed_at'         => $row->analyzed_at,
                    'post_id'             => $postId,
                ];
                self::$memoryCache[$postId] = $result;
                return $result;
            }
        }

        // Fallback to post_meta
        if (function_exists('get_post_meta')) {
            $metaAnalysis = get_post_meta($postId, '_apexseo_content_analysis', true);
            if (is_array($metaAnalysis)) {
                self::$memoryCache[$postId] = $metaAnalysis;
                return $metaAnalysis;
            }
        }

        return null;
    }

    /**
     * Explicit public analysis trigger (for REST API & WP-CLI).
     *
     * @param int $postId
     * @param bool $forceRecompute
     * @return array|null
     */
    public function analyzePost($postId, $forceRecompute = false) {
        $postId = (int) $postId;
        $post = function_exists('get_post') ? get_post($postId) : null;
        if (!$post) {
            return null;
        }

        return $this->executePostAnalysis($postId, $post, $forceRecompute);
    }

    /**
     * Delete analysis when a post is deleted.
     *
     * @param int $postId
     * @return bool
     */
    public function handleDeletePost($postId) {
        $postId = (int) $postId;
        unset(self::$memoryCache[$postId]);

        if ($this->db) {
            $table = $this->db->getPrefix() . 'apex_content_analysis';
            $this->db->query($this->db->prepare("DELETE FROM {$table} WHERE object_type = %s AND object_id = %d", 'post', $postId));
        }

        return true;
    }

    /**
     * Get underlying ContentAnalyzer engine.
     *
     * @return ContentAnalyzer
     */
    public function getContentAnalyzer() {
        return $this->contentAnalyzer;
    }
}
