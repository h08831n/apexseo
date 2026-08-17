<?php
namespace ApexSEO\API;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Contracts\HookableInterface;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;
use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\Validator\SchemaValidator;
use ApexSEO\Cache\Engine\CacheEngine;
use ApexSEO\Cache\Integration\CacheIntegrationManager;
use ApexSEO\Media\Optimizer\ImageOptimizer;
use ApexSEO\API\Controllers\AbstractRestController;
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

/**
 * Central REST API Subsystem Router & Manager (APEX-091 through APEX-100).
 */
class RestApiRouter implements ServiceContractInterface, HookableInterface {
    const NAMESPACE = 'apexseo/v1';

    /**
     * Security manager.
     *
     * @var SecurityManager
     */
    protected $security;

    /**
     * Active REST controllers.
     *
     * @var array<string, AbstractRestController>
     */
    protected $controllers = [];

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param ConfigurationManager $config
     * @param DatabaseManager $db
     * @param IndexableRepository $indexableRepo
     * @param IndexableBuilder $indexableBuilder
     * @param SchemaRegistry $schemaRegistry
     * @param SchemaValidator $schemaValidator
     * @param CacheEngine $cacheEngine
     * @param ImageOptimizer $imageOptimizer
     * @param CacheIntegrationManager|null $cacheIntegration
     */
    public function __construct(
        SecurityManager $security,
        ConfigurationManager $config,
        DatabaseManager $db,
        IndexableRepository $indexableRepo,
        IndexableBuilder $indexableBuilder,
        SchemaRegistry $schemaRegistry,
        SchemaValidator $schemaValidator,
        CacheEngine $cacheEngine,
        ImageOptimizer $imageOptimizer,
        $cacheIntegration = null
    ) {
        $this->security = $security;

        // Instantiate domain REST controllers
        $this->controllers['settings']   = new SettingsRestController($security, $config);
        $this->controllers['meta']       = new MetaRestController($security, $indexableRepo, $indexableBuilder);
        $this->controllers['schema']     = new SchemaRestController($security, $schemaRegistry, $schemaValidator, $db);
        $this->controllers['redirects']  = new RedirectsRestController($security, $db);
        $this->controllers['not_found']  = new NotFoundRestController($security, $db);
        $this->controllers['links']      = new LinksRestController($security, $db);
        $this->controllers['analytics']  = new AnalyticsRestController($security, $db);
        $this->controllers['cache']      = new CacheRestController($security, $cacheEngine, $cacheIntegration);
        $this->controllers['media']      = new MediaRestController($security, $imageOptimizer);
        $this->controllers['migration']  = new MigrationRestController($security, $db);
    }

    /**
     * {@inheritdoc}
     */
    public function registerHooks() {
        if (function_exists('add_action')) {
            add_action('rest_api_init', [$this, 'registerAllRoutes']);
        }
    }

    /**
     * Register all subsystem REST controllers and routes.
     *
     * @return void
     */
    public function registerAllRoutes() {
        // Register root namespace status endpoint
        if (function_exists('register_rest_route')) {
            register_rest_route(self::NAMESPACE, '/status', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getStatus'],
                'permission_callback' => [$this->security, 'restAdminPermissionCallback'],
            ]);
        }

        // Register each domain controller's routes
        foreach ($this->controllers as $controller) {
            $controller->registerRoutes();
        }
    }

    /**
     * Root status endpoint handler.
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function getStatus($request = null) {
        $data = [
            'namespace'       => self::NAMESPACE,
            'version'         => defined('APEXSEO_VERSION') ? APEXSEO_VERSION : '1.0.0',
            'status'          => 'active',
            'registered_apis' => 22,
            'controllers'     => array_keys($this->controllers),
            'timestamp'       => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        if (class_exists('\\WP_REST_Response')) {
            return new \WP_REST_Response($data, 200);
        }

        return $data;
    }

    /**
     * Get instantiated controller by key.
     *
     * @param string $key
     * @return AbstractRestController|null
     */
    public function getController($key) {
        return isset($this->controllers[$key]) ? $this->controllers[$key] : null;
    }

    /**
     * Get all active controllers.
     *
     * @return array<string, AbstractRestController>
     */
    public function getControllers() {
        return $this->controllers;
    }
}
