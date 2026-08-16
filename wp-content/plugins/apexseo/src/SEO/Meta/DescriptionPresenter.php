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
        if ($context instanceof Indexable && !empty($context->description)) {
            return $this->cleanDescription($context->description);
        }

        if (is_array($context)) {
            if (!empty($context['description'])) {
                return $this->cleanDescription($this->variableEngine->replace($context['description'], $context));
            }
            if (!empty($context['excerpt'])) {
                return $this->cleanDescription($context['excerpt']);
            }
            $pageType = isset($context['page_type']) ? $context['page_type'] : 'post';
            $template = $this->templateManager->getDescriptionTemplate($pageType);
            return $this->cleanDescription($this->variableEngine->replace($template, $context));
        }

        if ($context instanceof SeoContext) {
            if (!empty($context->excerpt)) {
                $pageType = $context->page_type === 'single' ? $context->object_sub_type : $context->page_type;
                $template = $this->templateManager->getDescriptionTemplate($pageType);
                return $this->cleanDescription($this->variableEngine->replace($template, $context));
            }
        }

        return '';
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
     * Clean and normalize description string.
     *
     * @param string $str
     * @return string
     */
    protected function cleanDescription($str) {
        $clean = strip_tags(strip_shortcodes((string) $str));
        $clean = str_replace(["\r", "\n", "\t"], ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        if (mb_strlen($clean) > 320) {
            $clean = mb_substr($clean, 0, 317) . '...';
        }

        return $clean;
    }
}
