<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;

/**
 * Renders sanitized Meta Description tags for document head.
 */
class DescriptionPresenter {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Template manager.
     *
     * @var TemplateManager
     */
    protected $templateManager;

    /**
     * Constructor.
     *
     * @param VariableEngine|null $variableEngine
     * @param TemplateManager|null $templateManager
     */
    public function __construct($variableEngine = null, $templateManager = null) {
        $this->variableEngine = $variableEngine !== null ? $variableEngine : new VariableEngine();
        $this->templateManager = $templateManager !== null ? $templateManager : new TemplateManager();
    }

    /**
     * Render raw meta description string.
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function render($context) {
        $desc = '';

        if ($context instanceof Indexable && !empty($context->description)) {
            $desc = $this->cleanDescription($context->description);
        } elseif (is_array($context)) {
            if (!empty($context['description'])) {
                $desc = $this->cleanDescription($this->variableEngine->replace($context['description'], $context));
            } elseif (!empty($context['excerpt'])) {
                $desc = $this->cleanDescription($context['excerpt']);
            } else {
                $pageType = isset($context['page_type']) ? $context['page_type'] : 'post';
                $template = $this->templateManager->getDescriptionTemplate($pageType);
                $desc = $this->cleanDescription($this->variableEngine->replace($template, $context));
            }
        } elseif ($context instanceof SeoContext) {
            if (!empty($context->excerpt)) {
                $pageType = $context->page_type === 'single' ? (!empty($context->object_sub_type) ? $context->object_sub_type : 'post') : $context->page_type;
                if ($context->page_type === 'term') {
                    $pageType = !empty($context->object_sub_type) ? $context->object_sub_type : 'category';
                }
                $template = $this->templateManager->getDescriptionTemplate($pageType);
                $desc = $this->cleanDescription($this->variableEngine->replace($template, $context));
            } else {
                $pageType = $context->page_type;
                $template = $this->templateManager->getDescriptionTemplate($pageType);
                $desc = $this->cleanDescription($this->variableEngine->replace($template, $context));
            }
        }

        if (function_exists('apply_filters')) {
            $desc = apply_filters('apexseo_description', $desc, $context);
        }

        return $desc;
    }

    /**
     * Render full HTML tag: <meta name="description" content="..." />
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function renderHtmlTag($context) {
        $desc = $this->render($context);
        if (empty($desc)) {
            return '';
        }

        $escaped = function_exists('esc_attr') ? esc_attr($desc) : htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
        return '<meta name="description" content="' . $escaped . '" />' . "\n";
    }

    /**
     * Clean and normalize description string with smart word-boundary truncation (APEX-018).
     *
     * @param string $str
     * @param int $maxLength
     * @return string
     */
    public function cleanDescription($str, $maxLength = 160) {
        return $this->truncateToWordBoundary($str, $maxLength);
    }

    /**
     * Truncate text string at word boundary without slicing mid-word (APEX-018).
     *
     * @param string $str
     * @param int $maxLength
     * @return string
     */
    public function truncateToWordBoundary($str, $maxLength = 160) {
        $clean = strip_tags(strip_shortcodes((string) $str));
        $clean = str_replace(["\r", "\n", "\t"], ' ', $clean);
        $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        if (mb_strlen($clean, 'UTF-8') <= $maxLength) {
            return $clean;
        }

        $targetLen = $maxLength - 3;
        if ($targetLen <= 0) {
            return mb_substr($clean, 0, $maxLength, 'UTF-8');
        }

        $substr = mb_substr($clean, 0, $targetLen, 'UTF-8');
        $lastSpace = mb_strrpos($substr, ' ', 0, 'UTF-8');

        // If a word boundary exists reasonably close to the limit (at least 40% of targetLen)
        if ($lastSpace !== false && $lastSpace > (int)($targetLen * 0.4)) {
            return rtrim(mb_substr($clean, 0, $lastSpace, 'UTF-8'), ' .,;:!?-') . '...';
        }

        return mb_substr($clean, 0, $targetLen, 'UTF-8') . '...';
    }
}
