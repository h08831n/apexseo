<?php
namespace ApexSEO\SEO\Builder;

use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\SEO\Models\Indexable;

class IndexableBuilder {
    private $varEngine;
    private $tplManager;

    public function __construct(VariableEngine $varEngine, TemplateManager $tplManager) {
        $this->varEngine = $varEngine;
        $this->tplManager = $tplManager;
    }

    public function buildForObject(int $objectId, string $objectType = 'post'): Indexable {
        $post = get_post($objectId);
        $title = $post ? $post->post_title : '';
        $permalink = $post ? get_permalink($objectId) : '';

        $context = [
            'title'     => $title,
            'sitename'  => get_bloginfo('name'),
            'sep'       => '|',
        ];

        $renderedTitle = $this->varEngine->replace($this->tplManager->getTitleTemplate($objectType), $context);

        return new Indexable([
            'object_id'       => $objectId,
            'object_type'     => $objectType,
            'title'           => $renderedTitle,
            'permalink'       => $permalink,
            'canonical_url'   => $permalink,
            'robots_index'    => 1,
            'robots_follow'   => 1,
        ]);
    }
}
