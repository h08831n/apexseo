<?php
namespace ApexSEO\SEO\Meta;

class MetaKeywordsPresenter {
    public function render(array $context = []): string {
        return $context['keywords'] ?? '';
    }

    public function renderHtmlTag(array $context = []): string {
        $kw = htmlspecialchars($this->render($context), ENT_QUOTES, 'UTF-8');
        if (empty($kw)) {
            return '';
        }
        return "<meta name=\"keywords\" content=\"{$kw}\" />";
    }
}
