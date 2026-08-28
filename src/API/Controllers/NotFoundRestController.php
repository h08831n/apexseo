<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Analytics\Monitor\FourOhFourMonitor;

class NotFoundRestController extends AbstractRestController {
    private $monitor;

    public function __construct(SecurityManager $security, FourOhFourMonitor $monitor) {
        parent::__construct($security);
        $this->monitor = $monitor;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/404', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getLogs'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/404/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'deleteLog'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/404/purge', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'purgeLogs'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);
    }

    public function getLogs($request) {
        $limit = (int)($request->get_param('limit') ?: 50);
        $logs = $this->monitor->getLogs($limit);
        return $this->sendResponse([
            'success' => true,
            'logs'    => $logs,
        ]);
    }

    public function deleteLog($request) {
        $id = (int)$request->get_param('id');
        $deleted = $this->monitor->deleteLog($id);
        return $this->sendResponse([
            'success' => (bool)$deleted,
            'message' => $deleted ? '404 log entry deleted.' : 'Log entry not found.',
        ]);
    }

    public function purgeLogs($request) {
        $this->monitor->purgeAll();
        return $this->sendResponse([
            'success' => true,
            'message' => 'All 404 log entries purged.',
        ]);
    }
}
