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
     * @return string Raw unescaped title string
     */
    public function render($context) {
        if ($context instanceof Indexable && !empty($context->title)) {
            return $context->title;
        }

        if (is_array($context)) {
            $rawTitle = isset($context['title']) ? $context['title'] : '';
            $sep = isset($context['sep']) ? $context['sep'] : $this->templateManager->getTitleSeparator();
            $sitename = isset($context['sitename']) ? $context['sitename'] : get_option('blogname', 'WordPress');
            $pageType = isset($context['page_type']) ? $context['page_type'] : 'post';

            if (!empty($rawTitle)) {
                $template = $this->templateManager->getTitleTemplate($pageType);
                return $this->variableEngine->replace($template, $context);
            }
        }

        if ($context instanceof SeoContext) {
            $pageType = $context->page_type === 'single' ? $context->object_sub_type : $context->page_type;
            $template = $this->templateManager->getTitleTemplate($pageType);
            return $this->variableEngine->replace($template, $context);
        }

        return get_option('blogname', 'WordPress');
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
