<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\SEO\Analysis\ContentAnalysisService;

class AnalysisRestController extends AbstractRestController {
    private $analysisService;

    public function __construct(SecurityManager $security, ContentAnalysisService $analysisService) {
        parent::__construct($security);
        $this->analysisService = $analysisService;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/analysis/post/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getAnalysis'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'runAnalysis'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ]
        ]);
    }

    public function getAnalysis($request) {
        $id = (int)$request->get_param('id');
        $data = $this->analysisService->getAnalysis($id);
        return $this->sendResponse([
            'success'  => true,
            'analysis' => $data,
        ]);
    }

    public function runAnalysis($request) {
        $id = (int)$request->get_param('id');
        $content = $request->get_param('content') ?: '';
        $keyword = $request->get_param('keyword') ?: '';

        $analysis = $this->analysisService->analyzeContent($id, $content, $keyword);
        return $this->sendResponse([
            'success'  => true,
            'analysis' => $analysis,
        ]);
    }
}
