<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;
use ApexSEO\SEO\Models\Indexable;

/**
 * REST API Controller for Post/Term Indexable Meta (API-03, API-04).
 */
class MetaRestController extends AbstractRestController {
    /**
     * Indexable repository.
     *
     * @var IndexableRepository
     */
    protected $repository;

    /**
     * Indexable builder.
     *
     * @var IndexableBuilder
     */
    protected $builder;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param IndexableRepository $repository
     * @param IndexableBuilder $builder
     */
    public function __construct(SecurityManager $security, IndexableRepository $repository, IndexableBuilder $builder) {
        parent::__construct($security);
        $this->repository = $repository;
        $this->builder    = $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes() {
        // GET /apexseo/v1/meta/{object_type}/{object_id} (API-03)
        $this->registerRoute('/meta/(?P<object_type>[a-z_-]+)/(?P<object_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getMeta'],
            'permission_callback' => '__return_true', // Public read for headless/editor contexts
            'args'                => [
                'object_type' => [
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => ['post', 'term', 'user'],
                ],
                'object_id' => [
                    'required' => true,
                    'type'     => 'integer',
                ],
            ],
        ]);

        // POST /apexseo/v1/meta/{object_type}/{object_id} (API-04)
        $this->registerRoute('/meta/(?P<object_type>[a-z_-]+)/(?P<object_id>\d+)', [
            'methods'             => 'POST',
            'callback'            => [$this, 'saveMeta'],
            'permission_callback' => [$this, 'checkObjectEditPermission'],
            'args'                => [
                'object_type' => [
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => ['post', 'term', 'user'],
                ],
                'object_id' => [
                    'required' => true,
                    'type'     => 'integer',
                ],
            ],
        ]);
    }

    /**
     * Check if user can edit this specific object.
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function checkObjectEditPermission($request) {
        $objectType = $request instanceof \WP_REST_Request ? $request->get_param('object_type') : 'post';
        $objectId   = $request instanceof \WP_REST_Request ? (int) $request->get_param('object_id') : 0;

        if ($this->security->canEditObject($objectId, $objectType)) {
            return true;
        }

        if (class_exists('\\WP_Error')) {
            return new \WP_Error(
                'rest_forbidden',
                'You do not have permission to edit metadata for this object.',
                ['status' => 403]
            );
        }

        return false;
    }

    /**
     * Get SEO Indexable metadata for an object (API-03).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function getMeta($request) {
        $objectType = $request instanceof \WP_REST_Request ? $request->get_param('object_type') : (isset($request['object_type']) ? $request['object_type'] : 'post');
        $objectId   = $request instanceof \WP_REST_Request ? (int) $request->get_param('object_id') : (isset($request['object_id']) ? (int) $request['object_id'] : 0);

        if (!$objectId) {
            return $this->error('apexseo_invalid_id', 'Valid object_id required.', 400);
        }

        $indexable = $this->repository->find($objectType, $objectId);

        if (!$indexable) {
            // Build fallback from WP object
            if ($objectType === 'post' && function_exists('get_post')) {
                $post = get_post($objectId);
                if ($post) {
                    $indexable = $this->builder->buildFromPost($post);
                }
            }
        }

        if (!$indexable) {
            return $this->error('apexseo_not_found', 'Resource not found.', 404);
        }

        return $this->success([
            'success'   => true,
            'indexable' => $indexable->toArray(),
        ]);
    }

    /**
     * Save SEO Indexable metadata for an object (API-04).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function saveMeta($request) {
        $objectType = $request instanceof \WP_REST_Request ? $request->get_param('object_type') : 'post';
        $objectId   = $request instanceof \WP_REST_Request ? (int) $request->get_param('object_id') : 0;
        $params     = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;

        if (!$objectId) {
            return $this->error('apexseo_invalid_id', 'Valid object_id required.', 400);
        }

        $indexable = $this->repository->find($objectType, $objectId);
        if (!$indexable) {
            $indexable = new Indexable();
            $indexable->object_type = $objectType;
            $indexable->object_id   = $objectId;
        }

        // Assign and sanitize payload fields
        if (isset($params['title'])) {
            $indexable->title = sanitize_text_field($params['title']);
        }
        if (isset($params['description'])) {
            $indexable->description = sanitize_textarea_field($params['description']);
        }
        if (isset($params['canonical_url'])) {
            $indexable->canonical_url = esc_url_raw($params['canonical_url']);
        }
        if (isset($params['is_robots_noindex'])) {
            $indexable->is_robots_noindex = (bool) $params['is_robots_noindex'];
        }
        if (isset($params['is_robots_nofollow'])) {
            $indexable->is_robots_nofollow = (bool) $params['is_robots_nofollow'];
        }
        if (isset($params['opengraph_title'])) {
            $indexable->opengraph_title = sanitize_text_field($params['opengraph_title']);
        }
        if (isset($params['opengraph_description'])) {
            $indexable->opengraph_description = sanitize_textarea_field($params['opengraph_description']);
        }
        if (isset($params['opengraph_image'])) {
            $indexable->opengraph_image = esc_url_raw($params['opengraph_image']);
        }
        if (isset($params['twitter_title'])) {
            $indexable->twitter_title = sanitize_text_field($params['twitter_title']);
        }
        if (isset($params['twitter_description'])) {
            $indexable->twitter_description = sanitize_textarea_field($params['twitter_description']);
        }
        if (isset($params['twitter_image'])) {
            $indexable->twitter_image = esc_url_raw($params['twitter_image']);
        }
        if (isset($params['schema_type'])) {
            $indexable->schema_type = sanitize_text_field($params['schema_type']);
        }
        if (isset($params['primary_focus_keyword'])) {
            $indexable->primary_focus_keyword = sanitize_text_field($params['primary_focus_keyword']);
        }

        $saved = $this->repository->save($indexable);

        if (!$saved) {
            return $this->error('apexseo_save_failed', 'Failed to save indexable metadata to database.', 500);
        }

        return $this->success([
            'success'   => true,
            'indexable' => $indexable->toArray(),
        ]);
    }
}
