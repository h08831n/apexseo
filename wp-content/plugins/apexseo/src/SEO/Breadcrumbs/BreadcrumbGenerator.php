<?php
namespace ApexSEO\SEO\Breadcrumbs;

use ApexSEO\SEO\Models\SeoContext;

/**
 * Generates accessible HTML Breadcrumb trails and valid Schema.org BreadcrumbList JSON-LD.
 */
class BreadcrumbGenerator {
    /**
     * Build breadcrumb items array for a given context.
     *
     * @param SeoContext|array $context
     * @return array<int, array{title: string, url: string|null, position: int}>
     */
    public function getBreadcrumbItems($context) {
        $items = [];
        $homeTitle = 'Home';
        $homeUrl = function_exists('home_url') ? home_url('/') : '/';

        if (is_array($context)) {
            $homeTitle = isset($context['home_title']) ? $context['home_title'] : 'Home';
        }

        // 1. Root Home item
        $items[] = [
            'title'    => $homeTitle,
            'url'      => $homeUrl,
            'position' => 1,
        ];

        if (is_array($context)) {
            $pos = 2;
            if (!empty($context['category'])) {
                $items[] = [
                    'title'    => $context['category'],
                    'url'      => isset($context['category_url']) ? $context['category_url'] : null,
                    'position' => $pos++,
                ];
            }
            if (!empty($context['title'])) {
                $items[] = [
                    'title'    => $context['title'],
                    'url'      => isset($context['canonical_url']) ? $context['canonical_url'] : null,
                    'position' => $pos++,
                ];
            }
            return $items;
        }

        if ($context instanceof SeoContext) {
            $pos = 2;

            if ($context->page_type === 'front_page' || $context->page_type === 'home') {
                return $items;
            }

            // Category or Taxonomy term
            if (!empty($context->category)) {
                $items[] = [
                    'title'    => $context->category,
                    'url'      => null,
                    'position' => $pos++,
                ];
            } elseif (!empty($context->term_name)) {
                $items[] = [
                    'title'    => $context->term_name,
                    'url'      => null,
                    'position' => $pos++,
                ];
            }

            // Current item
            if (!empty($context->title)) {
                $items[] = [
                    'title'    => $context->title,
                    'url'      => $context->canonical_url,
                    'position' => $pos++,
                ];
            }
        }

        return $items;
    }

    /**
     * Render accessible HTML breadcrumb navigation.
     *
     * @param SeoContext|array $context
     * @return string
     */
    public function renderHtml($context) {
        $items = $this->getBreadcrumbItems($context);
        if (empty($items)) {
            return '';
        }

        $html = '<nav aria-label="Breadcrumb" class="apexseo-breadcrumbs">' . "\n";
        $html .= '  <ol itemscope itemtype="https://schema.org/BreadcrumbList">' . "\n";

        foreach ($items as $item) {
            $title = function_exists('esc_html') ? esc_html($item['title']) : htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
            $html .= sprintf('    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">' . "\n");

            if (!empty($item['url']) && $item['position'] < count($items)) {
                $url = function_exists('esc_url') ? esc_url($item['url']) : htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
                $html .= sprintf('      <a itemprop="item" href="%s"><span itemprop="name">%s</span></a>' . "\n", $url, $title);
            } else {
                $html .= sprintf('      <span itemprop="name">%s</span>' . "\n", $title);
            }

            $html .= sprintf('      <meta itemprop="position" content="%d" />' . "\n", $item['position']);
            $html .= '    </li>' . "\n";
        }

        $html .= '  </ol>' . "\n";
        $html .= '</nav>' . "\n";

        return $html;
    }

    /**
     * Generate Schema.org BreadcrumbList JSON-LD structure.
     *
     * @param SeoContext|array $context
     * @return array
     */
    public function generateSchema($context) {
        $items = $this->getBreadcrumbItems($context);
        $elements = [];

        foreach ($items as $item) {
            $el = [
                '@type'    => 'ListItem',
                'position' => $item['position'],
                'name'     => $item['title'],
            ];

            if (!empty($item['url'])) {
                $el['item'] = $item['url'];
            }

            $elements[] = $el;
        }

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}
