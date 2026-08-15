<?php
namespace ApexSEO\Schema\Types;

/**
 * Schema.org Article / BlogPosting / NewsArticle Generator.
 */
class ArticleSchema extends AbstractSchemaType {
    /**
     * @var string
     */
    protected $type;

    /**
     * Constructor.
     *
     * @param string $type ('Article', 'BlogPosting', 'NewsArticle')
     */
    public function __construct($type = 'Article') {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getType() {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $context = []) {
        $pageType = isset($context['page_type']) ? $context['page_type'] : '';
        return in_array($pageType, ['single', 'post', 'article'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $context = []) {
        $headline = !empty($context['title']) ? $context['title'] : 'Article';
        $canonical = !empty($context['canonical_url']) ? $context['canonical_url'] : '';
        $datePublished = !empty($context['date_published']) ? $context['date_published'] : date('c');
        $dateModified = !empty($context['date_modified']) ? $context['date_modified'] : $datePublished;
        $authorName = !empty($context['author_name']) ? $context['author_name'] : 'Author';
        $publisherName = function_exists('get_bloginfo') ? get_bloginfo('name') : 'Apex SEO Site';
        $publisherLogo = !empty($context['site_logo']) ? $context['site_logo'] : null;

        $schema = [
            '@type'            => $this->type,
            'headline'         => $headline,
            'datePublished'    => $datePublished,
            'dateModified'     => $dateModified,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $canonical,
            ],
            'author' => [
                '@type' => 'Person',
                'name'  => $authorName,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name'  => $publisherName,
                'logo'  => $publisherLogo ? [
                    '@type' => 'ImageObject',
                    'url'   => $publisherLogo,
                ] : null,
            ],
        ];

        if (!empty($context['description'])) {
            $schema['description'] = $context['description'];
        }

        if (!empty($context['featured_image'])) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url'   => $context['featured_image'],
            ];
        }

        return $this->cleanData($schema);
    }
}
