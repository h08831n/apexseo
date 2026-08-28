<?php
namespace ApexSEO\Performance\Tweaks;

class ResourceHints {
    private $dnsPrefetch = [];
    private $preconnect = [];
    private $preload = [];

    public function addDnsPrefetch(string $domain): void {
        $this->dnsPrefetch[] = $domain;
    }

    public function addPreconnect(string $url): void {
        $this->preconnect[] = $url;
    }

    public function addPreload(string $url, string $as, array $attributes = []): void {
        $this->preload[] = [
            'url'        => $url,
            'as'         => $as,
            'attributes' => $attributes,
        ];
    }

    public function renderHtml(): string {
        $tags = [];
        foreach ($this->dnsPrefetch as $domain) {
            $tags[] = sprintf('<link rel="dns-prefetch" href="//%s" />', esc_attr(ltrim($domain, '/')));
        }
        foreach ($this->preconnect as $url) {
            $tags[] = sprintf('<link rel="preconnect" href="%s" crossorigin />', esc_url($url));
        }
        foreach ($this->preload as $item) {
            $attrStr = '';
            foreach ($item['attributes'] as $k => $v) {
                $attrStr .= is_bool($v) ? ($v ? " {$k}" : '') : sprintf(' %s="%s"', esc_attr($k), esc_attr($v));
            }
            $tags[] = sprintf('<link rel="preload" href="%s" as="%s"%s />', esc_url($item['url']), esc_attr($item['as']), $attrStr);
        }
        return implode("
", $tags);
    }
}
