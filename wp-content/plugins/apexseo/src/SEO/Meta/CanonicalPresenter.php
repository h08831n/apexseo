<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Canonical URL Presenter.
 */
class CanonicalPresenter implements ServiceContractInterface {
    /**
     * Compute the canonical URL for the current request context.
     *
     * @param array $context
     * @return string
     */
    public function render(array $context = []) {
        // 1. Explicit post/page override
        if (!empty($context['custom_canonical'])) {
            return esc_url_raw($context['custom_canonical']);
        }

        // 2. Single Post / Page
        if (!empty($context['post_id'])) {
            if (function_exists('get_permalink')) {
                $permalink = get_permalink((int) $context['post_id']);
                if (!empty($permalink)) {
                    return $this->cleanUrl($permalink);
                }
            }
        }

        // 3. Term / Taxonomy
        if (!empty($context['term_id']) && !empty($context['taxonomy'])) {
            if (function_exists('get_term_link')) {
                $link = get_term_link((int) $context['term_id'], $context['taxonomy']);
                if (!is_wp_error($link) && !empty($link)) {
                    return $this->cleanUrl($link);
                }
            }
        }

        // 4. Fallback URL in context or Home URL
        if (!empty($context['current_url'])) {
            return $this->cleanUrl($context['current_url']);
        }

        return function_exists('home_url') ? home_url('/') : 'https://example.com/';
    }

    /**
     * Clean tracking parameters and normalize URL trailing slash.
     *
     * @param string $url
     * @return string
     */
    public function cleanUrl($url) {
        $parts = parse_url($url);
        if (!$parts) {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : 'https://';
        $host   = isset($parts['host']) ? $parts['host'] : '';
        $port   = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path   = isset($parts['path']) ? $parts['path'] : '/';

        // Filter tracking query params like utm_*, fbclid, gclid
        $query = '';
        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            $filtered = [];
            foreach ($queryParams as $key => $val) {
                if (strpos($key, 'utm_') === 0 || in_array($key, ['fbclid', 'gclid', '_ga', 'mc_cid', 'mc_eid'], true)) {
                    continue;
                }
                $filtered[$key] = $val;
            }
            if (!empty($filtered)) {
                $query = '?' . http_build_query($filtered);
            }
        }

        return $scheme . $host . $port . $path . $query;
    }
}
