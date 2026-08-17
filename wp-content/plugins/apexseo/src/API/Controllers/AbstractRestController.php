<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Exceptions\ApexException;

/**
 * Base REST API Controller for Apex SEO Platform.
 */
abstract class AbstractRestController {
    /**
     * REST namespace.
     *
     * @var string
     */
    protected $namespace = 'apexseo/v1';

    /**
     * Security manager instance.
     *
     * @var SecurityManager
     */
    protected $security;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     */
    public function __construct(SecurityManager $security) {
        $this->security = $security;
    }

    /**
     * Register routes with WordPress.
     *
     * @return void
     */
    abstract public function registerRoutes();

    /**
     * Create standardized success response.
     *
     * @param mixed $data
     * @param int $status
     * @return \WP_REST_Response|array
     */
    protected function success($data, $status = 200) {
        if (class_exists('\\WP_REST_Response')) {
            return new \WP_REST_Response($data, $status);
        }
        return ['data' => $data, 'status' => $status];
    }

    /**
     * Create standardized error response.
     *
     * @param string $code
     * @param string $message
     * @param int $status
     * @param array $extraData
     * @return \WP_Error|array
     */
    protected function error($code, $message, $status = 400, array $extraData = []) {
        $data = array_merge(['status' => $status], $extraData);
        if (class_exists('\\WP_Error')) {
            return new \WP_Error($code, $message, $data);
        }
        return ['error' => $code, 'message' => $message, 'data' => $data];
    }

    /**
     * Permission check: Admin capability (manage_options).
     *
     * @param \WP_REST_Request|null $request
     * @return bool|\WP_Error
     */
    public function checkAdminPermission($request = null) {
        return $this->security->restAdminPermissionCallback($request);
    }

    /**
     * Permission check: Editor capability (edit_posts).
     *
     * @param \WP_REST_Request|null $request
     * @return bool|\WP_Error
     */
    public function checkEditorPermission($request = null) {
        return $this->security->restEditorPermissionCallback($request);
    }

    /**
     * Permission check: Media upload capability (upload_files).
     *
     * @param \WP_REST_Request|null $request
     * @return bool|\WP_Error
     */
    public function checkUploadPermission($request = null) {
        if (function_exists('current_user_can') && (current_user_can('upload_files') || current_user_can('manage_options'))) {
            return true;
        }

        if (class_exists('\\WP_Error')) {
            return new \WP_Error(
                'rest_forbidden',
                'You do not have permission to upload/optimize media files.',
                ['status' => 403]
            );
        }

        return false;
    }

    /**
     * Register a route directly.
     *
     * @param string $route
     * @param array $args
     * @return void
     */
    protected function registerRoute($route, array $args) {
        if (function_exists('register_rest_route')) {
            register_rest_route($this->namespace, $route, $args);
        }
    }
}
