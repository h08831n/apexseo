<?php
namespace ApexSEO\SEO\Social;

class OpenGraphPresenter {
    public function renderTags(array $context = []): array {
        return [
            'og:title'       => $context['og_title'] ?? ($context['title'] ?? ''),
            'og:description' => $context['og_description'] ?? ($context['description'] ?? ''),
            'og:url'         => $context['canonical_url'] ?? '',
            'og:type'        => $context['og_type'] ?? 'article',
            'og:site_name'   => $context['sitename'] ?? get_bloginfo('name'),
            'og:image'       => $context['og_image'] ?? '',
        ];
    }

    public function renderHtml(array $context = []): string {
        $tags = $this->renderTags($context);
        $html = [];
        foreach ($tags as $prop => $content) {
            if (!empty($content)) {
                $html[] = sprintf('<meta property="%s" content="%s" />', esc_attr($prop), esc_attr($content));
            }
        }
        return implode("
", $html);
    }
}
