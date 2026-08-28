<?php
namespace ApexSEO\API;

use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\API\Controllers\SettingsRestController;
use ApexSEO\API\Controllers\MetaRestController;
use ApexSEO\API\Controllers\SchemaRestController;
use ApexSEO\API\Controllers\RedirectsRestController;
use ApexSEO\API\Controllers\NotFoundRestController;
use ApexSEO\API\Controllers\LinksRestController;
use ApexSEO\API\Controllers\AnalyticsRestController;
use ApexSEO\API\Controllers\CacheRestController;
use ApexSEO\API\Controllers\MediaRestController;
use ApexSEO\API\Controllers\MigrationRestController;
use ApexSEO\API\Controllers\AnalysisRestController;

class RestApiRouter {
    const NAMESPACE = 'apexseo/v1';

    private $container;
    private $security;
    private $controllers = [];

    public function __construct(ContainerInterface $container, SecurityManager $security) {
        $this->container = $container;
        $this->security = $security;
        $this->initControllers();
    }

    private function initControllers(): void {
        $controllerClasses = [
            'settings'   => SettingsRestController::class,
            'meta'       => MetaRestController::class,
            'schema'     => SchemaRestController::class,
            'redirects'  => RedirectsRestController::class,
            'not_found'  => NotFoundRestController::class,
            'links'      => LinksRestController::class,
            'analytics'  => AnalyticsRestController::class,
            'cache'      => CacheRestController::class,
            'media'      => MediaRestController::class,
            'migration'  => MigrationRestController::class,
            'analysis'   => AnalysisRestController::class,
        ];

        foreach ($controllerClasses as $key => $class) {
            if ($this->container->has($class)) {
                $this->controllers[$key] = $this->container->get($class);
            }
        }
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getStatus'],
                'permission_callback' => [$this->security, 'checkAdminPermission'],
            ]
        ]);

        foreach ($this->controllers as $controller) {
            if (method_exists($controller, 'registerRoutes')) {
                $controller->registerRoutes();
            }
        }
    }

    public function getStatus($request) {
        return new \WP_REST_Response([
            'success'     => true,
            'plugin'      => 'APEX SEO',
            'version'     => '1.0.0',
            'status'      => 'operational',
            'controllers' => array_keys($this->controllers),
        ], 200);
    }

    public function getControllers(): array {
        return $this->controllers;
    }

    public function getController(string $key) {
        return $this->controllers[$key] ?? null;
    }
}
