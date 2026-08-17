<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Media\Optimizer\ImageOptimizer;

/**
 * REST API Controller for Media & Image Optimization (API-20, API-21).
 */
class MediaRestController extends AbstractRestController {
    /**
     * Image optimizer engine.
     *
     * @var ImageOptimizer
     */
    protected $optimizer;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param ImageOptimizer $optimizer
     */
    public function __construct(SecurityManager $security, ImageOptimizer $optimizer) {
        parent::__construct($security);
        $this->optimizer = $optimizer;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes() {
        // POST /apexseo/v1/media/optimize (API-20)
        $this->registerRoute('/media/optimize', [
            'methods'             => 'POST',
            'callback'            => [$this, 'optimizeSingle'],
            'permission_callback' => [$this, 'checkUploadPermission'],
            'args'                => [
                'attachment_id' => [
                    'required' => true,
                    'type'     => 'integer',
                ],
            ],
        ]);

        // POST /apexseo/v1/media/bulk-optimize (API-21)
        $this->registerRoute('/media/bulk-optimize', [
            'methods'             => 'POST',
            'callback'            => [$this, 'bulkOptimize'],
            'permission_callback' => [$this, 'checkAdminPermission'],
            'args'                => [
                'attachment_ids' => [
                    'required' => false,
                    'type'     => 'array',
                    'default'  => [],
                ],
                'batch_size' => [
                    'required' => false,
                    'type'     => 'integer',
                    'default'  => 10,
                ],
            ],
        ]);
    }

    /**
     * Optimize single media attachment into WebP/AVIF (API-20).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function optimizeSingle($request) {
        $attachmentId = $request instanceof \WP_REST_Request ? (int) $request->get_param('attachment_id') : (isset($request['attachment_id']) ? (int) $request['attachment_id'] : 0);

        if ($attachmentId <= 0) {
            return $this->error('apexseo_invalid_attachment', 'Valid positive attachment_id required.', 400);
        }

        // Validate that attachment exists and is an image when WP functions are present
        if (function_exists('wp_attachment_is_image') && !wp_attachment_is_image($attachmentId)) {
            return $this->error('apexseo_invalid_media_type', 'Target attachment is not a recognized image.', 422);
        }

        $result = $this->optimizer->optimizeAttachment($attachmentId);

        if (empty($result['success'])) {
            return $this->error('apexseo_optimization_failed', isset($result['error']) ? $result['error'] : 'Optimization failed', 500);
        }

        return $this->success([
            'success'       => true,
            'attachment_id' => $attachmentId,
            'original_size' => isset($result['original_size']) ? (int) $result['original_size'] : 0,
            'optimized_size'=> isset($result['optimized_size']) ? (int) $result['optimized_size'] : 0,
            'saved_bytes'   => isset($result['saved_bytes']) ? (int) $result['saved_bytes'] : 0,
            'saved_percent' => isset($result['saved_percent']) ? (float) $result['saved_percent'] : 0.0,
            'webp_url'      => isset($result['webp_url']) ? $result['webp_url'] : null,
            'avif_url'      => isset($result['avif_url']) ? $result['avif_url'] : null,
        ]);
    }

    /**
     * Execute synchronous batched media optimization (API-21).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function bulkOptimize($request) {
        $params        = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;
        $rawIds        = isset($params['attachment_ids']) && is_array($params['attachment_ids']) ? $params['attachment_ids'] : [];
        $rawBatchSize  = isset($params['batch_size']) ? (int) $params['batch_size'] : 10;

        // Clean, sanitize, deduplicate, and limit IDs
        $cleanedIds = array_values(array_unique(array_filter(array_map('intval', $rawIds), function($id) {
            return $id > 0;
        })));

        // Bound attachment IDs array to max 100
        if (count($cleanedIds) > 100) {
            $cleanedIds = array_slice($cleanedIds, 0, 100);
        }

        // Bound batch size: min 1, max 50
        $batchSize = max(1, min(50, $rawBatchSize));

        $batch = array_slice($cleanedIds, 0, $batchSize);
        $processed = [];
        $errors    = [];
        $totalSavedBytes = 0;

        foreach ($batch as $id) {
            // Check image format if WP function available
            if (function_exists('wp_attachment_is_image') && !wp_attachment_is_image($id)) {
                $errors[] = ['id' => $id, 'error' => 'Attachment is not an image'];
                continue;
            }

            $res = $this->optimizer->optimizeAttachment($id);
            if (!empty($res['success'])) {
                $processed[] = $id;
                $totalSavedBytes += isset($res['saved_bytes']) ? (int) $res['saved_bytes'] : 0;
            } else {
                $errors[] = ['id' => $id, 'error' => isset($res['error']) ? $res['error'] : 'Optimization failed'];
            }
        }

        return $this->success([
            'success'           => true,
            'processed_count'   => count($processed),
            'processed_ids'     => $processed,
            'errors'            => $errors,
            'total_saved_bytes' => $totalSavedBytes,
            'has_more'          => count($cleanedIds) > $batchSize,
            'remaining_count'   => max(0, count($cleanedIds) - count($processed) - count($errors)),
        ]);
    }
}
