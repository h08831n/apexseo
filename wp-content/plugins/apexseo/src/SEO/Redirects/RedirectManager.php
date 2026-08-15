<?php
namespace ApexSEO\SEO\Redirects;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * High-Speed Redirection Manager (301, 302, 307, 308, 410, 451).
 */
class RedirectManager implements ServiceContractInterface {
    /**
     * Database manager.
     *
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * In-memory redirect rules cache.
     *
     * @var array
     */
    protected $rulesCache = [];

    /**
     * Constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager) {
        $this->databaseManager = $databaseManager;
    }

    /**
     * Add or update a redirect rule.
     *
     * @param string $sourcePath (e.g. '/old-url/')
     * @param string $targetUrl  (e.g. '/new-url/' or 'https://external.com')
     * @param int $statusCode    (301, 302, 307, 308, 410, 451)
     * @param array $options     (regex, query_match, group)
     * @return bool
     */
    public function addRedirect($sourcePath, $targetUrl, $statusCode = 301, array $options = []) {
        $cleanSource = trim($sourcePath);
        $this->rulesCache[$cleanSource] = [
            'target'     => trim($targetUrl),
            'status'     => (int) $statusCode,
            'is_regex'   => !empty($options['is_regex']),
            'keep_query' => isset($options['keep_query']) ? (bool) $options['keep_query'] : true,
        ];

        return true;
    }

    /**
     * Match a requested URI against redirect rules.
     *
     * @param string $requestUri
     * @return array|null [target, status] or null if no match.
     */
    public function matchRedirect($requestUri) {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $query = parse_url($requestUri, PHP_URL_QUERY);

        // 1. Direct path match
        if (isset($this->rulesCache[$path])) {
            $rule = $this->rulesCache[$path];
            $target = $rule['target'];
            if ($rule['keep_query'] && !empty($query)) {
                $separator = (strpos($target, '?') !== false) ? '&' : '?';
                $target .= $separator . $query;
            }
            return [
                'target' => $target,
                'status' => $rule['status'],
            ];
        }

        // 2. Regex matching
        foreach ($this->rulesCache as $source => $rule) {
            if ($rule['is_regex']) {
                if (@preg_match('#' . $source . '#i', $requestUri, $matches)) {
                    $target = preg_replace('#' . $source . '#i', $rule['target'], $requestUri);
                    return [
                        'target' => $target,
                        'status' => $rule['status'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get all cached redirect rules.
     *
     * @return array
     */
    public function getRules() {
        return $this->rulesCache;
    }
}
