<?php
namespace ApexSEO\SEO\Breadcrumbs;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Semantic HTML & JSON-LD Breadcrumb Trail Generator.
 */
class BreadcrumbGenerator implements ServiceContractInterface {
    /**
     * Build breadcrumb items list for context.
     *
     * @param array $context
     * @return array<int, array{title: string, url: string}>
     */
    public function getItems(array $context = []) {
        $items = [];

        // 1. Home Item
        $homeUrl = function_exists('home_url') ? home_url('/') : 'https://example.com/';
        $homeTitle = !empty($context['home_title']) ? $context['home_title'] : 'Home';
        $items[] = [
            'title' => $homeTitle,
            'url'   => $homeUrl,
        ];

        // 2. Category / Taxonomy ancestry if single post
        if (!empty($context['category'])) {
            $catUrl = !empty($context['category_url']) ? $context['category_url'] : $homeUrl . 'category/' . sanitize_title($context['category']) . '/';
            $items[] = [
                'title' => $context['category'],
                'url'   => $catUrl,
            ];
        }

        // 3. Current page item
        if (!empty($context['title'])) {
            $currentUrl = !empty($context['canonical_url']) ? $context['canonical_url'] : '';
            $items[] = [
                'title' => $context['title'],
                'url'   => $currentUrl,
            ];
        }

        return $items;
    }

    /**
     * Render accessible HTML breadcrumb navigation markup.
     *
     * @param array $context
     * @param string $separator
     * @return string HTML
     */
    public function renderHtml(array $context = [], $separator = '›') {
        $items = $this->getItems($context);
        if (count($items) <= 1) {
            return '';
        }

        $html = '<nav class="apex-breadcrumbs" aria-label="Breadcrumbs">' . "\n";
        $html .= '  <ol itemscope itemtype="https://schema.org/BreadcrumbList">' . "\n";

        $count = count($items);
        foreach ($items as $index => $item) {
            $position = $index + 1;
            $isLast = ($position === $count);

            $html .= sprintf('    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">%s', "\n");
            if (!$isLast && !empty($item['url'])) {
                $html .= sprintf('      <a itemprop="item" href="%s"><span itemprop="name">%s</span></a>', esc_url($item['url']), esc_html($item['title'])) . "\n";
            } else {
                $html .= sprintf('      <span itemprop="name" aria-current="page">%s</span>', esc_html($item['title'])) . "\n";
            }
            $html .= sprintf('      <meta itemprop="position" content="%d" />', $position) . "\n";
            if (!$isLast) {
                $html .= sprintf('      <span class="apex-breadcrumb-sep" aria-hidden="true">%s</span>', esc_html($separator)) . "\n";
            }
            $html .= '    </li>' . "\n";
        }

        $html .= '  </ol>' . "\n";
        $html .= '</nav>';

        return $html;
    }

    /**
     * Generate Schema.org BreadcrumbList structured data array.
     *
     * @param array $context
     * @return array
     */
    public function generateSchema(array $context = []) {
        $items = $this->getItems($context);
        $elements = [];

        foreach ($items as $index => $item) {
            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $item['title'],
                'item'     => !empty($item['url']) ? $item['url'] : null,
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}
