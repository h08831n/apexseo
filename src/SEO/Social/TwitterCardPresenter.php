<?php
namespace ApexSEO\SEO\Social;

class TwitterCardPresenter {
    public function renderTags(array $context = []): array {
        return [
            'twitter:card'        => $context['twitter_card'] ?? 'summary_large_image',
            'twitter:title'       => $context['twitter_title'] ?? ($context['title'] ?? ''),
            'twitter:description' => $context['twitter_description'] ?? ($context['description'] ?? ''),
            'twitter:image'       => $context['twitter_image'] ?? ($context['og_image'] ?? ''),
        ];
    }

    public function renderHtml(array $context = []): string {
        $tags = $this->renderTags($context);
        $html = [];
        foreach ($tags as $name => $content) {
            if (!empty($content)) {
                $html[] = sprintf('<meta name="%s" content="%s" />', esc_attr($name), esc_attr($content));
            }
        }
        return implode("
", $html);
    }
}
