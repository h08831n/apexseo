<?php
namespace ApexSEO\SEO\Breadcrumbs;

class BreadcrumbGenerator {
    public function generate(array $items = []): array {
        $crumbs = [
            ['title' => 'Home', 'url' => home_url('/')]
        ];
        foreach ($items as $item) {
            $crumbs[] = $item;
        }
        return $crumbs;
    }

    public function renderHtml(array $items = []): string {
        $crumbs = $this->generate($items);
        $links = [];
        foreach ($crumbs as $c) {
            $links[] = sprintf('<a href="%s">%s</a>', esc_url($c['url']), esc_html($c['title']));
        }
        return '<nav class="apex-breadcrumbs">' . implode(' &raquo; ', $links) . '</nav>';
    }
}
