<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * SEO Document Title Presenter.
 */
class TitlePresenter implements ServiceContractInterface {
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
     * Generate document title string.
     *
     * @param array $context Context dictionary.
     * @param string|null $customTemplate Explicit title template.
     * @return string Formatted title.
     */
    public function render(array $context = [], $customTemplate = null) {
        // Post custom override
        if (!empty($context['custom_title'])) {
            return $this->variableEngine->replace($context['custom_title'], $context);
        }

        if ($customTemplate !== null) {
            return $this->variableEngine->replace($customTemplate, $context);
        }

        // Default templates based on context type
        $type = isset($context['page_type']) ? $context['page_type'] : 'single';
        $sep = isset($context['sep']) ? $context['sep'] : '-';
        $context['sep'] = $sep;

        switch ($type) {
            case 'home':
            case 'frontpage':
                $template = '%%sitename%% %%sep%% %%sitedesc%%';
                break;
            case 'category':
            case 'taxonomy':
                $template = '%%term_title%% Archive %%sep%% %%sitename%%';
                break;
            case 'author':
                $template = '%%author_name%%, Author at %%sitename%%';
                break;
            case 'search':
                $query = isset($context['search_query']) ? $context['search_query'] : '';
                return sprintf('Search Results for "%s" %s %s', $query, $sep, $this->variableEngine->resolveToken('sitename', $context));
            case '404':
                return sprintf('Page Not Found %s %s', $sep, $this->variableEngine->resolveToken('sitename', $context));
            case 'single':
            case 'page':
            default:
                $template = '%%title%% %%sep%% %%sitename%%';
                break;
        }

        return $this->variableEngine->replace($template, $context);
    }
}
