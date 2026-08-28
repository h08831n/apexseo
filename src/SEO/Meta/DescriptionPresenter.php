<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Variables\VariableEngine;

class DescriptionPresenter {
    private $varEngine;

    public function __construct(VariableEngine $varEngine) {
        $this->varEngine = $varEngine;
    }

    public function render(array $context = []): string {
        $template = $context['description'] ?? ($context['excerpt'] ?? '');
        $desc = $this->varEngine->replace($template, $context);
        return $this->cleanDescription($desc);
    }

    public function cleanDescription(string $text): string {
        $clean = strip_tags($text);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if (mb_strlen($clean) > 160) {
            $clean = mb_substr($clean, 0, 157) . '...';
        }
        return $clean;
    }

    public function renderHtmlTag(array $context = []): string {
        $desc = htmlspecialchars($this->render($context), ENT_QUOTES, 'UTF-8');
        if (empty($desc)) {
            return '';
        }
        return "<meta name=\"description\" content=\"{$desc}\" />";
    }
}
