<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Variables\VariableEngine;

class TitlePresenter {
    private $varEngine;

    public function __construct(VariableEngine $varEngine) {
        $this->varEngine = $varEngine;
    }

    public function render(array $context = []): string {
        $template = $context['template'] ?? '%%title%% %%sep%% %%sitename%%';
        $title = $this->varEngine->replace($template, $context);
        return trim(preg_replace('/\s+/', ' ', $title));
    }

    public function renderHtmlTag(array $context = []): string {
        $title = htmlspecialchars($this->render($context), ENT_QUOTES, 'UTF-8');
        return "<title>{$title}</title>";
    }
}
