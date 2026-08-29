<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\SEO\Redirects\RedirectManager;

class RedirectsRestController extends AbstractRestController {
    private $redirectManager;

    public function __construct(SecurityManager $security, RedirectManager $redirectManager) {
        parent::__construct($security);
        $this->redirectManager = $redirectManager;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/redirects', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getRedirects'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'createRedirect'],
                'permission_callback' => [$this, 'checkAdminPermission'],
                'args'                => [
                    'source_path' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                    'target_url'  => ['required' => true, 'sanitize_callback' => 'esc_url_raw'],
                    'status_code' => ['required' => false, 'default' => 301],
                ]
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/redirects/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'deleteRedirect'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);
    }

    public function getRedirects($request) {
        $list = $this->redirectManager->getAllRedirects();
        return $this->sendResponse([
            'success'   => true,
            'redirects' => $list,
        ]);
    }

    public function createRedirect($request) {
        $source = $request->get_param('source_path');
        $target = $request->get_param('target_url');
        $status = (int)($request->get_param('status_code') ?: 301);

        if (empty($source) || empty($target)) {
            return $this->sendError('missing_required_fields', 'Source path and target URL are required.', 400);
        }

        // Validate unsafe target schemes
        $parsedScheme = parse_url($target, PHP_URL_SCHEME);
        if ($parsedScheme && in_array(strtolower($parsedScheme), ['javascript', 'data', 'vbscript'], true)) {
            return $this->sendError('invalid_target_scheme', 'Target URL contains an unsafe scheme.', 400);
        }

        // Validate supported status codes
        $allowedStatusCodes = [301, 302, 307, 308, 410, 451];
        if (!in_array($status, $allowedStatusCodes, true)) {
            return $this->sendError('invalid_status_code', 'Invalid redirect HTTP status code.', 400);
        }

        // Detect redirect loops
        $cleanSource = '/' . ltrim(parse_url($source, PHP_URL_PATH) ?: $source, '/');
        $cleanTarget = '/' . ltrim(parse_url($target, PHP_URL_PATH) ?: $target, '/');
        if ($cleanSource === $cleanTarget) {
            return $this->sendError('redirect_loop_detected', 'Source and target paths cannot be identical.', 400);
        }

        $id = $this->redirectManager->addRedirect($source, $target, $status);
        if (!$id) {
            return $this->sendError('create_failed', 'Failed to create redirect rule.', 500);
        }

        return $this->sendResponse([
            'success' => true,
            'id'      => $id,
            'message' => 'Redirect created successfully.',
        ], 201);
    }

    public function deleteRedirect($request) {
        $id = (int)$request->get_param('id');
        $deleted = $this->redirectManager->deleteRedirect($id);
        if (!$deleted) {
            return $this->sendError('not_found', 'Redirect rule not found or could not be deleted.', 404);
        }
        return $this->sendResponse([
            'success' => true,
            'message' => 'Redirect deleted successfully.',
        ]);
    }
}
