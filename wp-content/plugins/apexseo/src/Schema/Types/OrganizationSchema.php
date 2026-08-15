<?php
namespace ApexSEO\Schema\Types;

/**
 * Schema.org Organization Entity Generator.
 */
class OrganizationSchema extends AbstractSchemaType {
    /**
     * {@inheritdoc}
     */
    public function getType() {
        return 'Organization';
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $context = []) {
        return !empty($context['is_organization']) || (isset($context['page_type']) && in_array($context['page_type'], ['home', 'frontpage'], true));
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $context = []) {
        $name = function_exists('get_bloginfo') ? get_bloginfo('name') : 'Apex SEO Organization';
        $url = function_exists('home_url') ? home_url('/') : 'https://example.com/';

        $schema = [
            '@type' => 'Organization',
            'name'  => $name,
            'url'   => $url,
        ];

        if (!empty($context['site_logo'])) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url'   => $context['site_logo'],
            ];
        }

        if (!empty($context['social_profiles']) && is_array($context['social_profiles'])) {
            $schema['sameAs'] = array_values($context['social_profiles']);
        }

        return $this->cleanData($schema);
    }
}
