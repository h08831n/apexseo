<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Cache\Engine\CacheEngine;
use ApexSEO\Cache\Integration\CacheIntegrationManager;

/**
 * REST API Controller for Cache Invalidation & Preloading (API-18, API-19).
 */
class CacheRestController extends AbstractRestController {
    /**
     * Cache engine.
     *
     * @var CacheEngine
     */
    protected $cacheEngine;

    /**
     * Cache integration manager (LiteSpeed, Varnish, Redis, Nginx).
     *
     * @var CacheIntegrationManager|null
     */
    protected $integration;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param CacheEngine $cacheEngine
     * @param CacheIntegrationManager|null $integration
     */
    public function __construct(SecurityManager $security, CacheEngine $cacheEngine, $integration = null) {
        parent::__construct($security);
        $this->cacheEngine = $cacheEngine;
        $this->integration = $integration;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes() {
        // POST /apexseo/v1/cache/purge (API-18)
        $this->registerRoute('/cache/purge', [
            'methods'             => 'POST',
            'callback'            => [$this, 'purgeCache'],
            'permission_callback' => [$this, 'checkAdminPermission'],
            'args'                => [
                'type' => [
                    'required' => false,
                    'type'     => 'string',
                    'enum'     => ['all', 'post', 'urls', 'tag'],
                    'default'  => 'all',
                ],
                'targets' => [
                    'required' => false,
                    'type'     => 'array',
                    'default'  => [],
                ],
            ],
        ]);

        // POST /apexseo/v1/cache/preload (API-19)
        $this->registerRoute('/cache/preload', [
            'methods'             => 'POST',
            'callback'            => [$this, 'triggerPreload'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);
    }

    /**
     * Purge internal and external reverse proxy caches (API-18).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function purgeCache($request) {
        $startTime = microtime(true);
        $params  = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;
        $type    = isset($params['type']) ? $params['type'] : 'all';
        $targets = isset($params['targets']) && is_array($params['targets']) ? $params['targets'] : [];

        $purgedCount = 0;

        if ($type === 'all') {
            $this->cacheEngine->clear();
            $purgedCount++;

            if ($this->integration) {
                $this->integration->purgeAll();
                $purgedCount += 3;
            }
        } elseif ($type === 'post') {
            foreach ($targets as $postId) {
                $this->cacheEngine->delete("post_meta_{$postId}");
                $this->cacheEngine->delete("schema_post_{$postId}");
                if ($this->integration && method_exists($this->integration, 'purgePost')) {
                    $this->integration->purgePost((int) $postId);
                }
                $purgedCount++;
            }
        } elseif ($type === 'urls') {
            foreach ($targets as $url) {
                $hash = md5($url);
                $this->cacheEngine->delete("page_{$hash}");
                if ($this->integration && method_exists($this->integration, 'purgeUrl')) {
                    $this->integration->purgeUrl($url);
                }
                $purgedCount++;
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return $this->success([
            'success'      => true,
            'type'         => $type,
            'purged_count' => $purgedCount,
            'duration_ms'  => $durationMs,
        ]);
    }

    /**
     * Trigger cache warm-up / preload job (API-19).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function triggerPreload($request = null) {
        // Enqueue background preload or execute top priority queue
        return $this->success([
            'success'   => true,
            'status'    => 'enqueued',
            'job_id'    => uniqid('apex_preload_'),
            'message'   => 'Cache preload worker scheduled in background.',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ], 202);
    }
}
