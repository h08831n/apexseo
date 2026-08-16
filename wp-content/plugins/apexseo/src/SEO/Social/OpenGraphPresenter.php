<?php
namespace ApexSEO\SEO\Social;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * Renders Open Graph meta tags (Facebook, LinkedIn, Pinterest, Discord, Slack).
 */
class OpenGraphPresenter {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Constructor.
     *
     * @param VariableEngine|null $variableEngine
     */
    public function __construct($variableEngine = null) {
        $this->variableEngine = $variableEngine !== null ? $variableEngine : new VariableEngine();
    }

    /**
     * Render OpenGraph meta tags HTML block.
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function render($context) {
        $tags = $this->buildTags($context);
        $output = '';

        foreach ($tags as $property => $content) {
            if ($content === null || $content === '') {
                continue;
            }

            if (strpos($property, 'image') !== false || strpos($property, 'url') !== false) {
                $escapedContent = function_exists('esc_url') ? esc_url($content) : htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            } else {
                $escapedContent = function_exists('esc_attr') ? esc_attr($content) : htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            }

            $output .= sprintf('<meta property="%s" content="%s" />' . "\n", $property, $escapedContent);
        }

        return $output;
    }

    /**
     * Build raw property => value map for Open Graph tags.
     *
     * @param SeoContext|Indexable|array $context
     * @return array<string, string|null>
     */
    public function buildTags($context) {
        $tags = [];

        $siteName = get_option('blogname', 'WordPress');
        $locale = function_exists('get_locale') ? get_locale() : 'en_US';

        $tags['og:locale'] = $locale;
        $tags['og:site_name'] = $siteName;

        if ($context instanceof Indexable) {
            $tags['og:type'] = $context->object_sub_type === 'page' ? 'website' : 'article';
            $tags['og:title'] = !empty($context->og_title) ? $context->og_title : $context->title;
            $tags['og:description'] = !empty($context->og_description) ? $context->og_description : $context->description;
            $tags['og:url'] = $context->canonical_url ? $context->canonical_url : $context->permalink;
            $tags['og:image'] = $context->og_image;
            return $tags;
        }

        if ($context instanceof SeoContext) {
            $tags['og:type'] = $context->og_type ? $context->og_type : 'article';
            $tags['og:title'] = !empty($context->og_title) ? $context->og_title : (!empty($context->title) ? $context->title : $siteName);
            $tags['og:description'] = !empty($context->og_description) ? $context->og_description : $context->excerpt;
            $tags['og:url'] = !empty($context->canonical_url) ? $context->canonical_url : $context->permalink;
            $tags['og:image'] = !empty($context->og_image) ? $context->og_image : $context->featured_image;

            if ($tags['og:type'] === 'article') {
                if (!empty($context->date_published)) {
                    $tags['article:published_time'] = $context->date_published;
                }
                if (!empty($context->date_modified)) {
                    $tags['article:modified_time'] = $context->date_modified;
                }
                if (!empty($context->author_name)) {
                    $tags['article:author'] = $context->author_name;
                }
            }
            return $tags;
        }

        if (is_array($context)) {
            $tags['og:type'] = isset($context['og_type']) ? $context['og_type'] : 'article';
            $tags['og:title'] = isset($context['og_title']) ? $context['og_title'] : (isset($context['title']) ? $context['title'] : $siteName);
            $tags['og:description'] = isset($context['og_description']) ? $context['og_description'] : (isset($context['description']) ? $context['description'] : '');
            $tags['og:url'] = isset($context['canonical_url']) ? $context['canonical_url'] : (isset($context['permalink']) ? $context['permalink'] : '');
            $tags['og:image'] = isset($context['og_image']) ? $context['og_image'] : (isset($context['featured_image']) ? $context['featured_image'] : null);
            return $tags;
        }

        return $tags;
    }
}
