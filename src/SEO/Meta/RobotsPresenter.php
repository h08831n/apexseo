<?php
namespace ApexSEO\SEO\Meta;

class RobotsPresenter {
    public function render(array $context = []): string {
        $index = $context['robots_index'] ?? true;
        $follow = $context['robots_follow'] ?? true;

        $parts = [];
        $parts[] = $index ? 'index' : 'noindex';
        $parts[] = $follow ? 'follow' : 'nofollow';

        return implode(', ', $parts);
    }

    public function renderHtmlTag(array $context = []): string {
        $robots = htmlspecialchars($this->render($context), ENT_QUOTES, 'UTF-8');
        return "<meta name=\"robots\" content=\"{$robots}\" />";
    }
}
