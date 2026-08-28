<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;

class MetaRestController extends AbstractRestController {
    private $repository;
    private $builder;

    public function __construct(SecurityManager $security, IndexableRepository $repository, IndexableBuilder $builder) {
        parent::__construct($security);
        $this->repository = $repository;
        $this->builder = $builder;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/meta/(?P<type>[a-zA-Z0-9_-]+)/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getMeta'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'saveMeta'],
                'permission_callback' => [$this, 'checkEditorPermission'],
                'args'                => [
                    'title' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
                    'description' => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
                    'canonical_url' => ['required' => false, 'sanitize_callback' => 'esc_url_raw'],
                    'primary_focus_keyword' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
                ]
            ]
        ]);
    }

    public function getMeta($request) {
        $type = $request->get_param('type');
        $id = (int)$request->get_param('id');

        $indexable = $this->repository->find((int)$id, $type);
        if (!$indexable) {
            $indexable = $this->builder->buildForObject($id, $type);
        }

        return $this->sendResponse([
            'success' => true,
            'data'    => $indexable ? $indexable->toArray() : [],
        ]);
    }

    public function saveMeta($request) {
        $type = $request->get_param('type');
        $id = (int)$request->get_param('id');
        $params = $request->get_json_params() ?: $request->get_params();

        $indexable = $this->repository->find($id, $type);
        if (!$indexable) {
            $indexable = $this->builder->buildForObject($id, $type);
        }

        if (isset($params['title'])) $indexable->setTitle($params['title']);
        if (isset($params['description'])) $indexable->setDescription($params['description']);
        if (isset($params['canonical_url'])) $indexable->setCanonicalUrl($params['canonical_url']);
        if (isset($params['primary_focus_keyword'])) $indexable->setPrimaryFocusKeyword($params['primary_focus_keyword']);
        if (isset($params['robots_index'])) $indexable->setRobotsIndex((bool)$params['robots_index']);
        if (isset($params['robots_follow'])) $indexable->setRobotsFollow((bool)$params['robots_follow']);

        $saved = $this->repository->save($indexable);

        return $this->sendResponse([
            'success' => (bool)$saved,
            'message' => 'SEO meta saved successfully.',
            'data'    => $indexable->toArray(),
        ]);
    }
}
