<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;

abstract class AbstractRestController {
    const NAMESPACE = 'apexseo/v1';

    /**
     * @var SecurityManager
     */
    protected $security;

    public function __construct(SecurityManager $security) {
        $this->security = $security;
    }

    abstract public function registerRoutes(): void;

    public function checkAdminPermission(): bool {
        return $this->security->checkAdminPermission();
    }

    public function checkEditorPermission(): bool {
        return $this->security->checkEditorPermission();
    }

    public function checkUploadPermission(): bool {
        return $this->security->checkUploadPermission();
    }

    protected function sendResponse(array $data, int $status = 200) {
        return new \WP_REST_Response($data, $status);
    }

    protected function sendError(string $code, string $message, int $status = 400) {
        return new \WP_REST_Response([
            'success' => false,
            'code'    => $code,
            'message' => $message,
        ], $status);
    }
}
