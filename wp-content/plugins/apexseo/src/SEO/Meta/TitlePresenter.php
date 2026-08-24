<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;

/**
 * Renders high-precision SEO Title tags for document head.
 */
class TitlePresenter {
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
     * Render the title string for the given context or indexable.
     *
     * @param SeoContext|Indexable|array $context
     * @return string Sanitized unescaped title string
     */
    public function render($context) {
        $rawTitle = '';
        $isPaged = false;
        $contextArray = [];

        if ($context instanceof Indexable) {
            if (!empty($context->title)) {
                $rawTitle = $context->title;
            }
            $contextArray = $context->toArray();
        } elseif (is_array($context)) {
            $contextArray = $context;
            $pageType = isset($context['page_type']) ? $context['page_type'] : 'post';
            $isPaged = !empty($context['is_paged']) || (isset($context['page_number']) && (int) $context['page_number'] > 1);

            if (!empty($context['title'])) {
                $template = $this->templateManager->getTitleTemplate($pageType);
                $rawTitle = $this->variableEngine->replace($template, $context);
            } else {
                $template = $this->templateManager->getTitleTemplate($pageType);
                $rawTitle = $this->variableEngine->replace($template, $context);
            }
        } elseif ($context instanceof SeoContext) {
            $contextArray = $context->toArray();
            $isPaged = $context->is_paged || $context->page_number > 1;

            if ($context->page_type === 'single') {
                $pageType = !empty($context->object_sub_type) ? $context->object_sub_type : 'post';
            } elseif ($context->page_type === 'term') {
                $pageType = !empty($context->object_sub_type) ? $context->object_sub_type : 'category';
            } else {
                $pageType = $context->page_type;
            }

            $template = $this->templateManager->getTitleTemplate($pageType);
            $rawTitle = $this->variableEngine->replace($template, $context);
        }

        if (empty($rawTitle)) {
            $rawTitle = function_exists('get_option') ? get_option('blogname', 'WordPress') : 'WordPress';
        }

        // Apply pagination title modifier (APEX-012)
        if ($isPaged && !preg_match('/(?:page|صفحه)\s+\d+/i', $rawTitle)) {
            $pageModifierTpl = $this->templateManager->getPageModifierTemplate();
            $pageSuffix = $this->variableEngine->replace($pageModifierTpl, $contextArray);
            if (!empty($pageSuffix)) {
                $rawTitle .= ' ' . $pageSuffix;
            }
        }

        // Apply sanitization and separator cleanup (APEX-010)
        $sanitized = $this->sanitizeTitle($rawTitle, isset($contextArray['sep']) ? $contextArray['sep'] : $this->templateManager->getTitleSeparator());

        if (function_exists('apply_filters')) {
            $sanitized = apply_filters('apexseo_title', $sanitized, $context);
        }

        return $sanitized;
    }

    /**
     * Sanitize and normalize document title string (APEX-010).
     *
     * @param string $title
     * @param string $sep
     * @return string
     */
    public function sanitizeTitle($title, $sep = '-') {
        // Strip tags and shortcodes
        $clean = strip_tags(strip_shortcodes((string) $title));

        // Remove newlines, carriage returns, and tabs
        $clean = str_replace(["\r", "\n", "\t"], ' ', $clean);

        // Decode HTML entities so &amp; isn't double-escaped later
        $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');

        // Collapse multiple spaces
        $clean = preg_replace('/\s+/', ' ', $clean);

        // Clean up duplicate and dangling separators
        $sepEscaped = preg_quote(trim($sep), '/');
        if (!empty($sepEscaped)) {
            // Collapse duplicate separators: " | | " -> " | "
            $clean = preg_replace('/(\s*' . $sepEscaped . '\s*){2,}/', ' ' . trim($sep) . ' ', $clean);
            // Remove leading separator: " - Post Title" -> "Post Title"
            $clean = preg_replace('/^\s*' . $sepEscaped . '\s*/', '', $clean);
            // Remove trailing separator: "Post Title - " -> "Post Title"
            $clean = preg_replace('/\s*' . $sepEscaped . '\s*$/', '', $clean);
        }

        return trim($clean);
    }

    /**
     * Render full HTML tag: <title>Escaped Title</title>
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function renderHtmlTag($context) {
        $title = $this->render($context);
        $escaped = function_exists('esc_html') ? esc_html($title) : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return '<title>' . $escaped . '</title>' . "\n";
    }
}
