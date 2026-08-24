<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\SEO\Analysis\ContentAnalysisService;

/**
 * REST API Controller for On-Page Content Intelligence & Readability (APEX-048 through APEX-054).
 *
 * Exposes:
 * - GET  /apexseo/v1/analysis/post/{id}
 * - POST /apexseo/v1/analysis/post/{id}
 */
class AnalysisRestController extends AbstractRestController {
    /**
     * Content analysis service.
     *
     * @var ContentAnalysisService
     */
    protected $analysisService;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param ContentAnalysisService $analysisService
     */
    public function __construct(SecurityManager $security, ContentAnalysisService $analysisService) {
        parent::__construct($security);
        $this->analysisService = $analysisService;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes() {
        // GET /apexseo/v1/analysis/post/{id}
        $this->registerRoute('/analysis/post/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getPostAnalysis'],
            'permission_callback' => [$this, 'checkAnalysisPermission'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && (int) $param > 0;
                    },
                ],
                'force' => [
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => false,
                ],
            ],
        ]);

        // POST /apexseo/v1/analysis/post/{id}
        $this->registerRoute('/analysis/post/(?P<id>\d+)', [
            'methods'             => 'POST',
            'callback'            => [$this, 'recomputePostAnalysis'],
            'permission_callback' => [$this, 'checkAnalysisPermission'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && (int) $param > 0;
                    },
                ],
            ],
        ]);
    }

    /**
     * Permission callback for content analysis inspection.
     *
     * @param \WP_REST_Request|array $request
     * @return bool|\WP_Error
     */
    public function checkAnalysisPermission($request = null) {
        $postId = 0;
        if ($request instanceof \WP_REST_Request) {
            $postId = (int) $request->get_param('id');
        } elseif (is_array($request) && isset($request['id'])) {
            $postId = (int) $request['id'];
        }

        if (function_exists('current_user_can')) {
            if ($postId > 0 && current_user_can('edit_post', $postId)) {
                return true;
            }
            if (current_user_can('edit_posts') || current_user_can('manage_options')) {
                return true;
            }
            return $this->error('apexseo_forbidden', 'You do not have permission to view analysis for this post.', 403);
        }

        return $this->checkEditorPermission($request);
    }

    /**
     * Get persisted analysis for a post, with optional on-demand recomputation.
     *
     * @param \WP_REST_Request|array $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function getPostAnalysis($request) {
        $postId = $request instanceof \WP_REST_Request ? (int) $request->get_param('id') : (int) ($request['id'] ?? 0);
        $force = false;

        if ($request instanceof \WP_REST_Request) {
            $force = (bool) $request->get_param('force') || (bool) $request->get_param('recompute');
        } elseif (is_array($request)) {
            $force = !empty($request['force']) || !empty($request['recompute']);
        }

        if ($postId <= 0) {
            return $this->error('apexseo_invalid_id', 'Invalid or missing post ID.', 400);
        }

        if ($force) {
            $analysis = $this->analysisService->analyzePost($postId, true);
        } else {
            $analysis = $this->analysisService->getPersistedAnalysis($postId);
            if (!$analysis) {
                // If not yet analyzed, compute and persist
                $analysis = $this->analysisService->analyzePost($postId, false);
            }
        }

        if (!$analysis) {
            return $this->error('apexseo_not_found', sprintf('Post #%d not found or has no analyzable content.', $postId), 404);
        }

        return $this->success($analysis, 200);
    }

    /**
     * Force on-demand recomputation of content analysis for a post.
     *
     * @param \WP_REST_Request|array $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function recomputePostAnalysis($request) {
        $postId = $request instanceof \WP_REST_Request ? (int) $request->get_param('id') : (int) ($request['id'] ?? 0);

        if ($postId <= 0) {
            return $this->error('apexseo_invalid_id', 'Invalid or missing post ID.', 400);
        }

        $analysis = $this->analysisService->analyzePost($postId, true);
        if (!$analysis) {
            return $this->error('apexseo_not_found', sprintf('Post #%d not found or failed to analyze.', $postId), 404);
        }

        return $this->success($analysis, 200);
    }
}
