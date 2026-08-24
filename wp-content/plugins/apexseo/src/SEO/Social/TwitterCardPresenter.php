<?php
namespace ApexSEO\SEO\Social;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Renders Twitter / X Card meta tags with site and creator handle support (APEX-033, APEX-036).
 */
class TwitterCardPresenter {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Option keys.
     */
    const OPTION_SITE_HANDLE = 'apexseo_twitter_site';
    const OPTION_CARD_TYPE   = 'apexseo_twitter_card_type';

    /**
     * Constructor.
     *
     * @param VariableEngine|null $variableEngine
     * @param ConfigurationManager|null $config
     */
    public function __construct($variableEngine = null, $config = null) {
        $this->variableEngine = $variableEngine !== null ? $variableEngine : new VariableEngine();
        $this->config = $config;
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
        $defaultCard = $this->getDefaultCardType();
        $tags['twitter:card'] = $defaultCard;

        // Twitter Site handle (APEX-036)
        $siteHandle = $this->getSiteHandle();
        if (!empty($siteHandle)) {
            $tags['twitter:site'] = $this->normalizeHandle($siteHandle);
        }

        $creatorHandle = null;

        if ($context instanceof Indexable) {
            $tags['twitter:card'] = !empty($context->twitter_card) ? $context->twitter_card : $defaultCard;
            $tags['twitter:title'] = !empty($context->twitter_title) ? $context->twitter_title : (!empty($context->og_title) ? $context->og_title : $context->title);
            $tags['twitter:description'] = !empty($context->twitter_description) ? $context->twitter_description : (!empty($context->og_description) ? $context->og_description : $context->description);
            $tags['twitter:image'] = !empty($context->twitter_image) ? $context->twitter_image : $context->og_image;
            if (!empty($context->twitter_creator)) {
                $creatorHandle = $context->twitter_creator;
            }
        } elseif ($context instanceof SeoContext) {
            $tags['twitter:card'] = $context->twitter_card ? $context->twitter_card : $defaultCard;
            $tags['twitter:title'] = !empty($context->twitter_title) ? $context->twitter_title : (!empty($context->og_title) ? $context->og_title : $context->title);
            $tags['twitter:description'] = !empty($context->twitter_description) ? $context->twitter_description : (!empty($context->og_description) ? $context->og_description : $context->excerpt);
            $tags['twitter:image'] = !empty($context->twitter_image) ? $context->twitter_image : (!empty($context->og_image) ? $context->og_image : $context->featured_image);
            if (!empty($context->twitter_creator)) {
                $creatorHandle = $context->twitter_creator;
            }
        } elseif (is_array($context)) {
            $tags['twitter:card'] = isset($context['twitter_card']) ? $context['twitter_card'] : $defaultCard;
            $tags['twitter:title'] = isset($context['twitter_title']) ? $context['twitter_title'] : (isset($context['og_title']) ? $context['og_title'] : (isset($context['title']) ? $context['title'] : ''));
            $tags['twitter:description'] = isset($context['twitter_description']) ? $context['twitter_description'] : (isset($context['og_description']) ? $context['og_description'] : (isset($context['description']) ? $context['description'] : ''));
            $tags['twitter:image'] = isset($context['twitter_image']) ? $context['twitter_image'] : (isset($context['og_image']) ? $context['og_image'] : (isset($context['featured_image']) ? $context['featured_image'] : null));
            if (isset($context['twitter_creator'])) {
                $creatorHandle = $context['twitter_creator'];
            }
        }

        // Twitter Creator handle (APEX-036)
        if (!empty($creatorHandle)) {
            $tags['twitter:creator'] = $this->normalizeHandle($creatorHandle);
        }

        return $tags;
    }

    /**
     * Normalize Twitter handle to always have a leading '@' without double '@'.
     *
     * @param string $handle
     * @return string
     */
    public function normalizeHandle($handle) {
        $clean = trim($handle);
        if (empty($clean)) {
            return '';
        }

        $clean = ltrim($clean, '@');
        // Validate valid Twitter username characters (alphanumeric and underscores, max 15 chars)
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', $clean);
        if (empty($clean)) {
            return '';
        }

        return '@' . $clean;
    }

    /**
     * Get site-wide Twitter handle.
     *
     * @return string
     */
    public function getSiteHandle() {
        if ($this->config !== null) {
            $h = $this->config->get('twitter_site', '');
            if (!empty($h)) {
                return (string) $h;
            }
        }

        if (function_exists('get_option')) {
            return (string) get_option(self::OPTION_SITE_HANDLE, '');
        }

        return '';
    }

    /**
     * Get default Twitter card type (summary or summary_large_image).
     *
     * @return string
     */
    public function getDefaultCardType() {
        if ($this->config !== null) {
            $t = $this->config->get('twitter_card_type', 'summary_large_image');
            if (!empty($t)) {
                return (string) $t;
            }
        }

        if (function_exists('get_option')) {
            return (string) get_option(self::OPTION_CARD_TYPE, 'summary_large_image');
        }

        return 'summary_large_image';
    }
}
