<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Database\DatabaseManager;

/**
 * WP-CLI Command for 301/302 Redirect Rules Management (APEX-185, APEX-186).
 *
 * ## EXAMPLES
 *     wp apexseo redirect add /old-page/ /new-page/ 301
 *     wp apexseo redirect list --format=table
 */
class RedirectCommand extends AbstractCliCommand {
    /**
     * Add a new redirect rule.
     *
     * ## OPTIONS
     * <source_url>
     * : The relative or absolute source path to redirect from.
     *
     * <target_url>
     * : The target URL or path to redirect to.
     *
     * [<status_code>]
     * : HTTP redirection status code (301, 302, 307, 308, 410).
     * ---
     * default: 301
     * ---
     *
     * [--regex]
     * : Treat source_url as a regular expression pattern.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function add($args = [], $assocArgs = []) {
        if (count($args) < 2) {
            $this->error('Both <source_url> and <target_url> are required.');
            return 1;
        }

        $sourceUrl  = trim($args[0]);
        $targetUrl  = trim($args[1]);
        $statusCode = !empty($args[2]) ? (int) $args[2] : 301;
        $isRegex    = !empty($assocArgs['regex']) ? 1 : 0;

        $parsedPath = parse_url($sourceUrl, PHP_URL_PATH);
        $sourcePath = '/' . ltrim(!empty($parsedPath) ? $parsedPath : $sourceUrl, '/');
        $targetPath = '/' . ltrim(parse_url($targetUrl, PHP_URL_PATH) ?: $targetUrl, '/');

        // Loop detection
        if ($sourcePath === $targetPath || $sourceUrl === $targetUrl) {
            $this->error('Source URL and target URL cannot be identical (redirect loop).');
            return 1;
        }

        $db = $this->container->get(DatabaseManager::class);
        $table = $db->getPrefix() . 'apex_redirects';
        $sourceHash = md5($sourcePath);

        // Check if exists
        $existing = $db->getVar($db->prepare("SELECT id FROM {$table} WHERE source_url_hash = %s LIMIT 1", $sourceHash));
        if ($existing) {
            $this->error(sprintf('Redirect rule for "%s" already exists (ID: %d).', $sourcePath, $existing));
            return 1;
        }

        $inserted = $db->insert($table, [
            'source_url'      => $sourcePath,
            'source_url_hash' => $sourceHash,
            'target_url'      => sanitize_text_field($targetUrl),
            'status_code'     => $statusCode,
            'is_regex'        => $isRegex,
            'status'          => 'active',
            'hits_count'      => 0,
            'created_at'      => gmdate('Y-m-d H:i:s'),
        ]);

        if ($inserted) {
            $this->success(sprintf('Redirect created successfully! [ID: %d] %s -> %s (%d)', $db->getInsertId(), $sourcePath, $targetUrl, $statusCode));
            return 0;
        } else {
            $this->error('Failed to create redirect in database.');
            return 1;
        }
    }

    /**
     * List all registered redirect rules.
     *
     * ## OPTIONS
     * [--format=<format>]
     * : Output format (table, json, csv, ids, count).
     * ---
     * default: table
     * ---
     *
     * [--per-page=<int>]
     * : Number of redirects to fetch.
     * ---
     * default: 100
     * ---
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function list($args = [], $assocArgs = []) {
        $format  = isset($assocArgs['format']) ? $assocArgs['format'] : 'table';
        $perPage = isset($assocArgs['per-page']) ? max(1, min(500, (int) $assocArgs['per-page'])) : 100;

        $db = $this->container->get(DatabaseManager::class);
        $table = $db->getPrefix() . 'apex_redirects';

        $query = $db->prepare("SELECT id, source_url, target_url, status_code, hits_count, status, created_at FROM {$table} ORDER BY id DESC LIMIT %d", $perPage);
        $results = $db->getResults($query);

        $items = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $items[] = [
                    'id'          => is_object($row) ? $row->id : $row['id'],
                    'source_url'  => is_object($row) ? $row->source_url : $row['source_url'],
                    'target_url'  => is_object($row) ? $row->target_url : $row['target_url'],
                    'status_code' => is_object($row) ? $row->status_code : $row['status_code'],
                    'hits_count'  => is_object($row) ? $row->hits_count : $row['hits_count'],
                    'status'      => is_object($row) ? $row->status : $row['status'],
                    'created_at'  => is_object($row) ? $row->created_at : $row['created_at'],
                ];
            }
        }

        $this->formatItems($format, $items, ['id', 'source_url', 'target_url', 'status_code', 'hits_count', 'status', 'created_at']);
        return 0;
    }
}
