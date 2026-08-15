<?php
namespace ApexSEO\Core\REST;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Contracts\HookableInterface;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Exceptions\ApexException;

/**
 * REST API Namespace and Route Registration Foundation for Apex SEO Platform.
 */
class RestManager implements ServiceContractInterface, HookableInterface {
    const NAMESPACE = 'apexseo/v1';

    /**
     * Security manager instance.
     *
     * @var SecurityManager
     */
    protected $security;

    /**
     * Registered routes array.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     */
    public function __construct(SecurityManager $security) {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function registerHooks() {
        if (function_exists('add_action')) {
            add_action('rest_api_init', [$this, 'initRoutes']);
        }
    }

    /**
     * Initialize registered REST routes with WordPress.
     *
     * @return void
     */
    public function initRoutes() {
        // Register namespace root status/health check
        $this->registerRoute('/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getStatusResponse'],
            'permission_callback' => [$this->security, 'restAdminPermissionCallback'],
        ]);

        foreach ($this->routes as $route => $args) {
            if (function_exists('register_rest_route')) {
                register_rest_route(self::NAMESPACE, $route, $args);
            }
        }
    }

    /**
     * Register a route definition under the apexseo/v1 namespace.
     *
     * @param string $route Route sub-pattern.
     * @param array $args Endpoint arguments.
     * @return self
     */
    public function registerRoute($route, array $args) {
        $this->routes[$route] = $args;
        return $this;
    }

    /**
     * Root status endpoint handler.
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function getStatusResponse($request = null) {
        $data = [
            'namespace' => self::NAMESPACE,
            'version'   => defined('APEXSEO_VERSION') ? APEXSEO_VERSION : '1.0.0',
            'status'    => 'active',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        if (class_exists('\\WP_REST_Response')) {
            return new \WP_REST_Response($data, 200);
        }

        return $data;
    }

    /**
     * Normalize exception or error into a standardized REST response.
     *
     * @param \Exception|\WP_Error|string $error
     * @param int $statusCode HTTP status code.
     * @return \WP_Error
     */
    public function formatError($error, $statusCode = 500) {
        $message = is_string($error) ? $error : (is_object($error) && method_exists($error, 'getMessage') ? $error->getMessage() : 'Internal error');
        $code    = ($error instanceof ApexException) ? 'apexseo_error_' . $error->getCode() : 'apexseo_rest_error';

        if (class_exists('\\WP_Error')) {
            return new \WP_Error($code, $message, ['status' => $statusCode]);
        }

        return ['error' => $message, 'status' => $statusCode];
    }

    /**
     * Get registered routes.
     *
     * @return array
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * Get REST API namespace string.
     *
     * @return string
     */
    public function getNamespace() {
        return self::NAMESPACE;
    }
}
