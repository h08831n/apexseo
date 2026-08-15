<?php
namespace ApexSEO\Schema\Types;

/**
 * Schema.org WebSite & Sitelinks SearchBox Generator.
 */
class WebSiteSchema extends AbstractSchemaType {
    /**
     * {@inheritdoc}
     */
    public function getType() {
        return 'WebSite';
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $context = []) {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $context = []) {
        $name = function_exists('get_bloginfo') ? get_bloginfo('name') : 'Apex SEO WebSite';
        $url = function_exists('home_url') ? home_url('/') : 'https://example.com/';

        $schema = [
            '@type' => 'WebSite',
            'name'  => $name,
            'url'   => $url,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $url . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        if (function_exists('get_bloginfo') && get_bloginfo('description')) {
            $schema['description'] = get_bloginfo('description');
        }

        return $this->cleanData($schema);
    }
}
