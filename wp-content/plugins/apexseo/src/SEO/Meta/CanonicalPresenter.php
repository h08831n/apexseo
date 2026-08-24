<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;

/**
 * Renders high-fidelity canonical URL link elements and strips tracking parameters (APEX-019, APEX-020, APEX-021).
 */
class CanonicalPresenter {
    /**
     * Query parameters to strip for canonical normalization.
     *
     * @var array<string>
     */
    protected $blacklistedParams = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'utm_id',
        'fbclid',
        'gclid',
        'dclid',
        'msclkid',
        'zanpid',
        '_ga',
        '_gl',
        'mc_cid',
        'mc_eid',
    ];

    /**
     * Render the canonical URL string.
     *
     * @param SeoContext|Indexable|array|string $context
     * @return string
     */
    public function render($context) {
        if (is_string($context)) {
            return $this->cleanUrl($context);
        }

        // 404 pages must not emit a canonical URL (APEX-008, APEX-030)
        if ($context instanceof SeoContext && ($context->page_type === '404' || $context->object_type === '404')) {
            return '';
        }

        if ($context instanceof Indexable && ($context->object_type === '404' || $context->object_sub_type === '404')) {
            return '';
        }

        if (is_array($context) && (
            (isset($context['page_type']) && $context['page_type'] === '404') ||
            (isset($context['object_type']) && $context['object_type'] === '404')
        )) {
            return '';
        }

        // 1. Explicit Custom Canonical Override (APEX-020, APEX-021)
        if ($context instanceof Indexable && !empty($context->canonical_url)) {
            return $this->cleanUrl($context->canonical_url);
        }

        if ($context instanceof SeoContext) {
            $url = !empty($context->canonical_url) ? $context->canonical_url : $context->permalink;
            if (empty($url)) {
                $url = function_exists('home_url') ? home_url('/') : 'http://localhost/';
            }

            // Handle pagination on canonical if self-referential (APEX-012, APEX-021)
            if (empty($context->canonical_url) && $context->is_paged && $context->page_number > 1) {
                if (function_exists('get_pagenum_link')) {
                    $url = get_pagenum_link($context->page_number);
                } else {
                    $url = add_query_arg('paged', $context->page_number, $url);
                }
            }

            return $this->cleanUrl($url);
        }

        if (is_array($context)) {
            $url = isset($context['canonical_url']) ? $context['canonical_url'] : (isset($context['permalink']) ? $context['permalink'] : '');
            return $this->cleanUrl($url);
        }

        return '';
    }

    /**
     * Render full HTML tag: <link rel="canonical" href="..." />
     *
     * @param SeoContext|Indexable|array|string $context
     * @return string
     */
    public function renderHtmlTag($context) {
        $url = $this->render($context);
        if (empty($url)) {
            return '';
        }

        $escaped = function_exists('esc_url') ? esc_url($url) : htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return '<link rel="canonical" href="' . $escaped . '" />' . "\n";
    }

    /**
     * Clean, normalize, and validate canonical URL (APEX-020, APEX-021).
     *
     * Rules:
     * - Reject dangerous schemes (javascript:, data:, vbscript:, file:)
     * - Strip URL fragments (#section)
     * - Strip tracking parameters (utm_*, fbclid, etc.)
     * - Normalize host and scheme (http / https)
     * - Normalize path slashes
     *
     * @param string $url
     * @return string Empty string if invalid or dangerous
     */
    public function cleanUrl($url) {
        if (empty($url)) {
            return '';
        }

        $url = trim($url);

        // Security: reject dangerous protocols
        if (preg_match('/^(javascript|data|vbscript|file):/i', $url)) {
            return '';
        }

        // Strip fragment (#...)
        $fragmentPos = strpos($url, '#');
        if ($fragmentPos !== false) {
            $url = substr($url, 0, $fragmentPos);
        }

        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            // Check if relative path
            if (strpos($url, '/') === 0 && function_exists('home_url')) {
                return $this->cleanUrl(home_url($url));
            }
            return '';
        }

        $scheme = isset($parsed['scheme']) ? strtolower($parsed['scheme']) : 'https';
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $host = strtolower($parsed['host']);
        // Validate host name syntax
        if (!filter_var('https://' . $host, FILTER_VALIDATE_URL) && !preg_match('/^[a-z0-9\.\-]+$/i', $host)) {
            return '';
        }

        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = isset($parsed['path']) ? $parsed['path'] : '/';

        // Normalize double slashes in path except initial
        $path = preg_replace('#/+#', '/', $path);
        if (empty($path)) {
            $path = '/';
        }

        // Query parameters handling
        $query = '';
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            foreach ($this->blacklistedParams as $param) {
                unset($queryParams[$param]);
            }
            if (!empty($queryParams)) {
                $query = '?' . http_build_query($queryParams);
            }
        }

        return $scheme . '://' . $host . $port . $path . $query;
    }
}
