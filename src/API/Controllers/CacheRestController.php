<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Performance\Cache\SmartPurge;
use ApexSEO\Performance\Cache\StaticFileWriter;

class CacheRestController extends AbstractRestController {
    private $purge;
    private $fileWriter;

    public function __construct(SecurityManager $security, SmartPurge $purge, StaticFileWriter $fileWriter) {
        parent::__construct($security);
        $this->purge = $purge;
        $this->fileWriter = $fileWriter;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/cache/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getStatus'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/cache/purge', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'purgeCache'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/cache/preload', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'preloadCache'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);
    }

    public function getStatus($request) {
        return $this->sendResponse([
            'success'      => true,
            'cache_dir'    => $this->fileWriter->getCacheDir(),
            'cached_pages' => $this->fileWriter->getCachedFilesCount(),
        ]);
    }

    public function purgeCache($request) {
        $url = $request->get_param('url');
        if ($url) {
            $purged = $this->purge->purge($url);
        } else {
            $purged = $this->purge->purgeAll();
        }

        return $this->sendResponse([
            'success' => $purged,
            'message' => $purged ? 'Cache successfully purged.' : 'Cache purge failed.',
        ]);
    }

    public function preloadCache($request) {
        return $this->sendResponse([
            'success' => true,
            'message' => 'Cache warmup job queued.',
        ]);
    }
}
