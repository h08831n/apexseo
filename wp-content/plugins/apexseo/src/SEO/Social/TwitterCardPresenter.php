<?php
namespace ApexSEO\SEO\Social;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * Twitter Cards Metadata Presenter.
 */
class TwitterCardPresenter implements ServiceContractInterface {
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
     * Build Twitter card tags array.
     *
     * @param array $context
     * @return array<string, string>
     */
    public function buildTags(array $context = []) {
        $tags = [
            'twitter:card' => !empty($context['twitter_card_type']) ? $context['twitter_card_type'] : 'summary_large_image',
        ];

        if (!empty($context['twitter_site'])) {
            $tags['twitter:site'] = $context['twitter_site'];
        }

        if (!empty($context['twitter_creator'])) {
            $tags['twitter:creator'] = $context['twitter_creator'];
        }

        if (!empty($context['twitter_title'])) {
            $tags['twitter:title'] = $this->variableEngine->replace($context['twitter_title'], $context);
        } elseif (!empty($context['title'])) {
            $tags['twitter:title'] = $this->variableEngine->replace($context['title'], $context);
        }

        if (!empty($context['twitter_description'])) {
            $tags['twitter:description'] = $this->variableEngine->replace($context['twitter_description'], $context);
        } elseif (!empty($context['description'])) {
            $tags['twitter:description'] = $this->variableEngine->replace($context['description'], $context);
        }

        if (!empty($context['twitter_image'])) {
            $tags['twitter:image'] = $context['twitter_image'];
        } elseif (!empty($context['featured_image'])) {
            $tags['twitter:image'] = $context['featured_image'];
        }

        return $tags;
    }

    /**
     * Render Twitter Card HTML meta tags.
     *
     * @param array $context
     * @return string
     */
    public function render(array $context = []) {
        $tags = $this->buildTags($context);
        $html = '';

        foreach ($tags as $name => $content) {
            if (!empty($content)) {
                $html .= sprintf('<meta name="%s" content="%s" />' . "\n", esc_attr($name), esc_attr($content));
            }
        }

        return $html;
    }
}
