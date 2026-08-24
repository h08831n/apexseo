<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Renders optional Meta Keywords tags for legacy compatibility (APEX-016).
 *
 * NOTE: Major search engines (Google, Bing) do not use meta keywords for ranking.
 * This capability is provided strictly for legacy workflows and is disabled by default.
 */
class MetaKeywordsPresenter {
    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Option key.
     */
    const OPTION_KEY = 'apexseo_enable_meta_keywords';

    /**
     * Constructor.
     *
     * @param ConfigurationManager|null $config
     */
    public function __construct($config = null) {
        $this->config = $config;
    }

    /**
     * Check if meta keywords feature is explicitly enabled.
     *
     * @return bool
     */
    public function isEnabled() {
        if ($this->config !== null) {
            $val = $this->config->get('enable_meta_keywords', null);
            if ($val !== null) {
                return (bool) $val;
            }
        }

        if (function_exists('get_option')) {
            return (bool) get_option(self::OPTION_KEY, false);
        }

        return false;
    }

    /**
     * Render raw sanitized keywords string for context.
     *
     * @param SeoContext|Indexable|array|string $context
     * @return string Empty string if disabled or no keywords
     */
    public function render($context) {
        if (!$this->isEnabled()) {
            return '';
        }

        $raw = '';

        if (is_string($context)) {
            $raw = $context;
        } elseif ($context instanceof Indexable) {
            $raw = !empty($context->focus_keywords) ? (is_array($context->focus_keywords) ? implode(', ', $context->focus_keywords) : $context->focus_keywords) : '';
            if (empty($raw) && !empty($context->focus_keyword)) {
                $raw = $context->focus_keyword;
            }
        } elseif ($context instanceof SeoContext) {
            $raw = !empty($context->focus_keywords) ? (is_array($context->focus_keywords) ? implode(', ', $context->focus_keywords) : $context->focus_keywords) : '';
            if (empty($raw) && !empty($context->focus_keyword)) {
                $raw = $context->focus_keyword;
            }
        } elseif (is_array($context)) {
            if (!empty($context['focus_keywords'])) {
                $raw = is_array($context['focus_keywords']) ? implode(', ', $context['focus_keywords']) : $context['focus_keywords'];
            } elseif (!empty($context['focus_keyword'])) {
                $raw = $context['focus_keyword'];
            } elseif (!empty($context['keywords'])) {
                $raw = is_array($context['keywords']) ? implode(', ', $context['keywords']) : $context['keywords'];
            }
        }

        if (empty($raw)) {
            return '';
        }

        return $this->sanitizeKeywords($raw);
    }

    /**
     * Render full HTML tag: <meta name="keywords" content="..." />
     *
     * @param SeoContext|Indexable|array|string $context
     * @return string
     */
    public function renderHtmlTag($context) {
        $keywords = $this->render($context);
        if (empty($keywords)) {
            return '';
        }

        $escaped = function_exists('esc_attr') ? esc_attr($keywords) : htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8');
        return '<meta name="keywords" content="' . $escaped . '" />' . "\n";
    }

    /**
     * Sanitize keywords list: strip HTML tags, deduplicate, trim, and prevent stuffing.
     *
     * @param string $keywords
     * @return string
     */
    public function sanitizeKeywords($keywords) {
        // Strip HTML tags and shortcodes
        $clean = strip_tags($keywords);
        if (function_exists('strip_shortcodes')) {
            $clean = strip_shortcodes($clean);
        }

        // Split by comma
        $parts = explode(',', $clean);
        $unique = [];

        foreach ($parts as $p) {
            $kw = trim($p);
            $kw = preg_replace('/\s+/', ' ', $kw);
            if (!empty($kw) && !in_array(strtolower($kw), array_map('strtolower', $unique), true)) {
                // Max 100 characters per single keyword phrase
                if (mb_strlen($kw, 'UTF-8') > 100) {
                    $kw = mb_substr($kw, 0, 100, 'UTF-8');
                }
                $unique[] = $kw;
            }
        }

        // Limit to max 20 keywords to prevent spam stuffing
        $unique = array_slice($unique, 0, 20);

        return implode(', ', $unique);
    }
}
