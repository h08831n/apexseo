<?php
namespace ApexSEO\SEO\Meta;

class CanonicalPresenter {
    public function render(array $context = []): string {
        return $context['canonical_url'] ?? '';
    }

    public function renderHtmlTag(array $context = []): string {
        $url = esc_url($this->render($context));
        if (empty($url)) {
            return '';
        }
        return "<link rel=\"canonical\" href=\"{$url}\" />";
    }
}
