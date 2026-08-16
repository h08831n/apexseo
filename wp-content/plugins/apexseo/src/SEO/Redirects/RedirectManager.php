<?php
namespace ApexSEO\SEO\Redirects;

use ApexSEO\Core\Database\DatabaseManager;

/**
 * High-Speed Redirection Engine operating against wp_apex_redirects.
 */
class RedirectManager {
    /**
     * Database manager.
     *
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table;

    /**
     * In-memory cache for fast lookups.
     *
     * @var array<string, array|null>
     */
    protected $lookupCache = [];

    /**
     * Constructor.
     *
     * @param DatabaseManager $db
     */
    public function __construct(DatabaseManager $db) {
        $this->db = $db;
        $this->table = $db->getPrefix() . 'apex_redirects';
    }

    /**
     * Add a redirect rule.
     *
     * @param string $sourceUrl
     * @param string $targetUrl
     * @param int $statusCode
     * @param string $matchType 'exact'|'prefix'|'regex'
     * @param bool $isRegex
     * @return int|false Insert ID on success, false on failure
     */
    public function addRedirect($sourceUrl, $targetUrl, $statusCode = 301, $matchType = 'exact', $isRegex = false) {
        $sourceUrl = '/' . ltrim(trim($sourceUrl), '/');
        $hash = md5($sourceUrl);

        $wpdb = $this->db->getWpdb();
        $data = [
            'source_url'      => $sourceUrl,
            'source_url_hash' => $hash,
            'target_url'      => trim($targetUrl),
            'status_code'     => (int) $statusCode,
            'match_type'      => $matchType,
            'is_regex'        => $isRegex ? 1 : 0,
            'hits_count'      => 0,
            'status'          => 'active',
        ];

        $res = $wpdb->insert(
            $this->table,
            $data,
            ['%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s']
        );

        if ($res !== false) {
            $insertId = $wpdb->insert_id;
            $this->lookupCache[$sourceUrl] = [
                'id'          => $insertId,
                'target'      => $data['target_url'],
                'status'      => $data['status_code'],
                'match_type'  => $matchType,
            ];
            return $insertId;
        }

        return false;
    }

    /**
     * Match an incoming request URI against active redirect rules.
     *
     * @param string $requestUri
     * @return array{id: int, target: string, status: int}|null
     */
    public function matchRedirect($requestUri) {
        $cleanUri = '/' . ltrim(parse_url($requestUri, PHP_URL_PATH), '/');

        if (array_key_exists($cleanUri, $this->lookupCache)) {
            return $this->lookupCache[$cleanUri];
        }

        $hash = md5($cleanUri);
        $wpdb = $this->db->getWpdb();

        // 1. Direct Exact Hash Match (O(1) Indexed query)
        $query = $wpdb->prepare(
            "SELECT id, target_url, status_code FROM `{$this->table}` WHERE `source_url_hash` = %s AND `status` = 'active' LIMIT 1",
            $hash
        );

        $row = $wpdb->get_row($query, ARRAY_A);
        if ($row) {
            $match = [
                'id'     => (int) $row['id'],
                'target' => $row['target_url'],
                'status' => (int) $row['status_code'],
            ];
            $this->lookupCache[$cleanUri] = $match;
            return $match;
        }

        // 2. Trailing slash variation match
        $altUri = substr($cleanUri, -1) === '/' ? rtrim($cleanUri, '/') : $cleanUri . '/';
        $altHash = md5($altUri);
        $altRow = $wpdb->get_row(
            $wpdb->prepare("SELECT id, target_url, status_code FROM `{$this->table}` WHERE `source_url_hash` = %s AND `status` = 'active' LIMIT 1", $altHash),
            ARRAY_A
        );

        if ($altRow) {
            $match = [
                'id'     => (int) $altRow['id'],
                'target' => $altRow['target_url'],
                'status' => (int) $altRow['status_code'],
            ];
            $this->lookupCache[$cleanUri] = $match;
            return $match;
        }

        $this->lookupCache[$cleanUri] = null;
        return null;
    }

    /**
     * Intercept request in template_redirect and trigger HTTP redirect if matched.
     *
     * @return void
     */
    public function interceptAndRedirect() {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        $uri = sanitize_text_field($_SERVER['REQUEST_URI']);
        $match = $this->matchRedirect($uri);

        if ($match && !empty($match['target'])) {
            $wpdb = $this->db->getWpdb();
            // Increment hit counter asynchronously/efficiently
            $wpdb->query(
                $wpdb->prepare("UPDATE `{$this->table}` SET `hits_count` = `hits_count` + 1, `last_accessed_at` = NOW() WHERE `id` = %d", $match['id'])
            );

            if (function_exists('wp_safe_redirect')) {
                wp_safe_redirect($match['target'], $match['status']);
                exit;
            } elseif (!headers_sent()) {
                header("Location: {$match['target']}", true, $match['status']);
                exit;
            }
        }
    }
}
