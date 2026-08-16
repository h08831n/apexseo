<?php
namespace ApexSEO\SEO\Social;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * Renders Twitter / X Card meta tags.
 */
class TwitterCardPresenter {
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
     * Render Twitter Card meta tags HTML block.
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function render($context) {
        $tags = $this->buildTags($context);
        $output = '';

        foreach ($tags as $name => $content) {
            if ($content === null || $content === '') {
                continue;
            }

            if (strpos($name, 'image') !== false) {
                $escapedContent = function_exists('esc_url') ? esc_url($content) : htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            } else {
                $escapedContent = function_exists('esc_attr') ? esc_attr($content) : htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            }

            $output .= sprintf('<meta name="%s" content="%s" />' . "\n", $name, $escapedContent);
        }

        return $output;
    }

    /**
     * Build raw name => value map for Twitter Card tags.
     *
     * @param SeoContext|Indexable|array $context
     * @return array<string, string|null>
     */
    public function buildTags($context) {
        $tags = [];
        $tags['twitter:card'] = 'summary_large_image';

        if ($context instanceof Indexable) {
            $tags['twitter:title'] = !empty($context->twitter_title) ? $context->twitter_title : (!empty($context->og_title) ? $context->og_title : $context->title);
            $tags['twitter:description'] = !empty($context->twitter_description) ? $context->twitter_description : (!empty($context->og_description) ? $context->og_description : $context->description);
            $tags['twitter:image'] = !empty($context->twitter_image) ? $context->twitter_image : $context->og_image;
            return $tags;
        }

        if ($context instanceof SeoContext) {
            $tags['twitter:card'] = $context->twitter_card ? $context->twitter_card : 'summary_large_image';
            $tags['twitter:title'] = !empty($context->twitter_title) ? $context->twitter_title : (!empty($context->og_title) ? $context->og_title : $context->title);
            $tags['twitter:description'] = !empty($context->twitter_description) ? $context->twitter_description : (!empty($context->og_description) ? $context->og_description : $context->excerpt);
            $tags['twitter:image'] = !empty($context->twitter_image) ? $context->twitter_image : (!empty($context->og_image) ? $context->og_image : $context->featured_image);
            return $tags;
        }

        if (is_array($context)) {
            $tags['twitter:card'] = isset($context['twitter_card']) ? $context['twitter_card'] : 'summary_large_image';
            $tags['twitter:title'] = isset($context['twitter_title']) ? $context['twitter_title'] : (isset($context['title']) ? $context['title'] : '');
            $tags['twitter:description'] = isset($context['twitter_description']) ? $context['twitter_description'] : (isset($context['description']) ? $context['description'] : '');
            $tags['twitter:image'] = isset($context['twitter_image']) ? $context['twitter_image'] : (isset($context['featured_image']) ? $context['featured_image'] : null);
            return $tags;
        }

        return $tags;
    }
}
