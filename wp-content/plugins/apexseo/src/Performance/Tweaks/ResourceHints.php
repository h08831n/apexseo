<?php
namespace ApexSEO\Performance\Tweaks;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Modern Resource Hints Manager (dns-prefetch, preconnect, preload, fetchpriority).
 */
class ResourceHints implements ServiceContractInterface {
    /**
     * @var array<string>
     */
    protected $dnsPrefetchDomains = [];

    /**
     * @var array<string>
     */
    protected $preconnectOrigins = [];

    /**
     * @var array<array{url: string, as: string, type?: string, crossorigin?: bool}>
     */
    protected $preloadResources = [];

    /**
     * Add DNS Prefetch domain.
     *
     * @param string $domain
     * @return self
     */
    public function addDnsPrefetch($domain) {
        $clean = trim($domain);
        if (!in_array($clean, $this->dnsPrefetchDomains, true)) {
            $this->dnsPrefetchDomains[] = $clean;
        }
        return $this;
    }

    /**
     * Add Preconnect origin.
     *
     * @param string $origin
     * @return self
     */
    public function addPreconnect($origin) {
        $clean = trim($origin);
        if (!in_array($clean, $this->preconnectOrigins, true)) {
            $this->preconnectOrigins[] = $clean;
        }
        return $this;
    }

    /**
     * Add Preload resource.
     *
     * @param string $url
     * @param string $as ('style', 'script', 'font', 'image')
     * @param array $options
     * @return self
     */
    public function addPreload($url, $as, array $options = []) {
        $this->preloadResources[] = array_merge([
            'url' => $url,
            'as'  => $as,
        ], $options);
        return $this;
    }

    /**
     * Render HTML resource hint tags.
     *
     * @return string
     */
    public function renderHtml() {
        $html = '';

        foreach ($this->dnsPrefetchDomains as $domain) {
            $html .= sprintf('<link rel="dns-prefetch" href="//%s" />' . "\n", esc_attr(ltrim($domain, '/')));
        }

        foreach ($this->preconnectOrigins as $origin) {
            $html .= sprintf('<link rel="preconnect" href="%s" crossorigin />' . "\n", esc_url($origin));
        }

        foreach ($this->preloadResources as $res) {
            $crossorigin = !empty($res['crossorigin']) ? ' crossorigin' : '';
            $type = !empty($res['type']) ? sprintf(' type="%s"', esc_attr($res['type'])) : '';
            $html .= sprintf('<link rel="preload" href="%s" as="%s"%s%s />' . "\n", esc_url($res['url']), esc_attr($res['as']), $type, $crossorigin);
        }

        return $html;
    }
}
