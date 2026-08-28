<?php
namespace ApexSEO\SEO\Admin;

use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Models\Indexable;

class MetaSaver {
    private $repository;

    public function __construct(IndexableRepository $repository) {
        $this->repository = $repository;
    }

    public function savePostMeta(int $postId, array $data): bool {
        $indexable = $this->repository->find($postId, 'post');
        if (!$indexable) {
            $indexable = new Indexable(['object_id' => $postId, 'object_type' => 'post']);
        }

        if (isset($data['title'])) $indexable->setTitle($data['title']);
        if (isset($data['description'])) $indexable->setDescription($data['description']);
        if (isset($data['canonical_url'])) $indexable->setCanonicalUrl($data['canonical_url']);
        if (isset($data['primary_focus_keyword'])) $indexable->setPrimaryFocusKeyword($data['primary_focus_keyword']);

        return (bool) $this->repository->save($indexable);
    }
}
