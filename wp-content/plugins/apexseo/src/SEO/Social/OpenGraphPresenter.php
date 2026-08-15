<?php
namespace ApexSEO\SEO\Social;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * Open Graph Social Metadata Presenter.
 */
class OpenGraphPresenter implements ServiceContractInterface {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Constructor.
     *
     * @param VariableEngine $variableEngine
     */
    public function __construct(VariableEngine $variableEngine) {
        $this->variableEngine = $variableEngine;
    }

    /**
     * Build dictionary of Open Graph properties.
     *
     * @param array $context
     * @return array<string, string>
     */
    public function buildTags(array $context = []) {
        $locale = function_exists('get_locale') ? get_locale() : 'en_US';
        $siteName = function_exists('get_bloginfo') ? get_bloginfo('name') : 'Apex SEO Site';

        $tags = [
            'og:locale'    => $locale,
            'og:site_name' => $siteName,
            'og:type'      => (!empty($context['page_type']) && in_array($context['page_type'], ['single', 'post'])) ? 'article' : 'website',
        ];

        if (!empty($context['og_title'])) {
            $tags['og:title'] = $this->variableEngine->replace($context['og_title'], $context);
        } elseif (!empty($context['title'])) {
            $tags['og:title'] = $this->variableEngine->replace($context['title'], $context);
        }

        if (!empty($context['og_description'])) {
            $tags['og:description'] = $this->variableEngine->replace($context['og_description'], $context);
        } elseif (!empty($context['description'])) {
            $tags['og:description'] = $this->variableEngine->replace($context['description'], $context);
        }

        if (!empty($context['canonical_url'])) {
            $tags['og:url'] = $context['canonical_url'];
        }

        if (!empty($context['og_image'])) {
            $tags['og:image'] = $context['og_image'];
        } elseif (!empty($context['featured_image'])) {
            $tags['og:image'] = $context['featured_image'];
        }

        return $tags;
    }

    /**
     * Render Open Graph HTML meta tags.
     *
     * @param array $context
     * @return string
     */
    public function render(array $context = []) {
        $tags = $this->buildTags($context);
        $html = '';

        foreach ($tags as $property => $content) {
            if (!empty($content)) {
                $html .= sprintf('<meta property="%s" content="%s" />' . "\n", esc_attr($property), esc_attr($content));
            }
        }

        return $html;
    }
}
