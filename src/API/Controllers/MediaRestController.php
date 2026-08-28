<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Media\Optimizer\ImageOptimizer;

/**
 * Media REST Controller
 * Strict Part A & Part B implementation: Real conversion of domain failures into machine-readable REST errors.
 * Never outputs fake success or exposes sensitive credentials/internal paths.
 */
class MediaRestController extends AbstractRestController {
    private $optimizer;

    public function __construct(SecurityManager $security, ImageOptimizer $optimizer) {
        parent::__construct($security);
        $this->optimizer = $optimizer;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/media/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getStatus'],
                'permission_callback' => [$this, 'checkUploadPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/media/optimize', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'optimizeSingle'],
                'permission_callback' => [$this, 'checkUploadPermission'],
                'args'                => [
                    'attachment_id' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param) && (int)$param > 0;
                        }
                    ]
                ]
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/media/bulk-optimize', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'bulkOptimize'],
                'permission_callback' => [$this, 'checkUploadPermission'],
                'args'                => [
                    'attachment_ids' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return is_array($param);
                        }
                    ]
                ]
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/media/convert-webp', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'convertWebp'],
                'permission_callback' => [$this, 'checkUploadPermission'],
                'args'                => [
                    'attachment_id' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param) && (int)$param > 0;
                        }
                    ]
                ]
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/media/convert-avif', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'convertAvif'],
                'permission_callback' => [$this, 'checkUploadPermission'],
                'args'                => [
                    'attachment_id' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param) && (int)$param > 0;
                        }
                    ]
                ]
            ]
        ]);
    }

    public function getStatus($request) {
        return $this->sendResponse([
            'success'            => true,
            'available_binaries' => $this->optimizer->getAvailableBinaries(),
        ]);
    }

    public function optimizeSingle($request) {
        $attachmentId = (int)$request->get_param('attachment_id');
        if ($attachmentId <= 0) {
            return $this->sendError('invalid_attachment_id', 'A valid positive attachment ID is required.', 400);
        }

        $result = $this->optimizer->optimizeAttachment($attachmentId);
        if (is_wp_error($result)) {
            $errData = $result->get_error_data();
            $status = (is_array($errData) && isset($errData['status'])) ? (int)$errData['status'] : 400;
            return $this->sendError($result->get_error_code(), $result->get_error_message(), $status);
        }

        return $this->sendResponse($result, 200);
    }

    public function bulkOptimize($request) {
        $ids = $request->get_param('attachment_ids');
        if (!is_array($ids) || empty($ids)) {
            return $this->sendError('invalid_params', 'attachment_ids must be a non-empty array of IDs.', 400);
        }

        // Bounded batch execution
        $ids = array_slice(array_unique(array_filter(array_map('intval', $ids))), 0, 50);
        if (empty($ids)) {
            return $this->sendError('invalid_params', 'No valid numeric attachment IDs provided.', 400);
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($ids as $id) {
            $res = $this->optimizer->optimizeAttachment($id);
            if (is_wp_error($res)) {
                $failureCount++;
                $results[] = [
                    'attachment_id' => $id,
                    'success'       => false,
                    'code'          => $res->get_error_code(),
                    'message'       => $res->get_error_message(),
                ];
            } else {
                $successCount++;
                $results[] = array_merge(['attachment_id' => $id, 'success' => true], $res);
            }
        }

        $allSucceeded = ($failureCount === 0);
        return $this->sendResponse([
            'success'         => $allSucceeded,
            'processed_count' => count($ids),
            'success_count'   => $successCount,
            'failure_count'   => $failureCount,
            'items'           => $results,
        ], $allSucceeded ? 200 : 207);
    }

    public function convertWebp($request) {
        $attachmentId = (int)$request->get_param('attachment_id');
        $filePath = get_attached_file($attachmentId);
        if (!$filePath || !file_exists($filePath)) {
            return $this->sendError('source_file_missing', 'Attachment source file does not exist.', 404);
        }

        $result = $this->optimizer->convertToWebp($filePath);
        if (is_wp_error($result)) {
            $errData = $result->get_error_data();
            $status = (is_array($errData) && isset($errData['status'])) ? (int)$errData['status'] : 500;
            return $this->sendError($result->get_error_code(), $result->get_error_message(), $status);
        }

        return $this->sendResponse($result, 200);
    }

    public function convertAvif($request) {
        $attachmentId = (int)$request->get_param('attachment_id');
        $filePath = get_attached_file($attachmentId);
        if (!$filePath || !file_exists($filePath)) {
            return $this->sendError('source_file_missing', 'Attachment source file does not exist.', 404);
        }

        $result = $this->optimizer->convertToAvif($filePath);
        if (is_wp_error($result)) {
            $errData = $result->get_error_data();
            $status = (is_array($errData) && isset($errData['status'])) ? (int)$errData['status'] : 500;
            return $this->sendError($result->get_error_code(), $result->get_error_message(), $status);
        }

        return $this->sendResponse($result, 200);
    }
}
