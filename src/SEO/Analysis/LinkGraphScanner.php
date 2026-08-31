<?php
namespace ApexSEO\SEO\Analysis;

use ApexSEO\Core\Database\DatabaseManager;

class LinkGraphScanner {
    /**
     * @var DatabaseManager|null
     */
    private $db;

    /**
     * @var string
     */
    private $siteUrl;

    /**
     * @var string
     */
    private $siteHost;

    /**
     * @var array
     */
    private $allowedInternalHosts = [];

    public function __construct(?DatabaseManager $db = null, ?string $siteUrl = null) {
        $this->db = $db;
        $this->siteUrl = $siteUrl ? rtrim($siteUrl, '/') : (function_exists('home_url') ? rtrim(home_url(), '/') : '');
        $this->siteHost = parse_url($this->siteUrl, PHP_URL_HOST) ?: '';
        if ($this->siteHost) {
            $this->allowedInternalHosts[] = strtolower($this->siteHost);
        }
    }

    public function setAllowedInternalHosts(array $hosts): void {
        foreach ($hosts as $host) {
            $h = strtolower(trim((string)$host));
            if ($h !== '' && !in_array($h, $this->allowedInternalHosts, true)) {
                $this->allowedInternalHosts[] = $h;
            }
        }
    }

    public function isInternalUrl(string $url): bool {
        $trimmed = trim($url);
        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, 'javascript:') === 0 || strpos($trimmed, 'mailto:') === 0 || strpos($trimmed, 'tel:') === 0) {
            return false;
        }

        if (strpos($trimmed, '/') === 0 && strpos($trimmed, '//') !== 0) {
            return true;
        }

        $host = parse_url($trimmed, PHP_URL_HOST);
        if ($host) {
            $lowerHost = strtolower($host);
            if ($this->siteHost && $lowerHost === strtolower($this->siteHost)) {
                return true;
            }
            if (in_array($lowerHost, $this->allowedInternalHosts, true)) {
                return true;
            }
            return false;
        }

        if (!preg_match('~^[a-z0-9+.-]+://~i', $trimmed) && strpos($trimmed, '//') !== 0) {
            return true;
        }

        return false;
    }

    public function scan(string $html): array {
        preg_match_all('~<a\b(?:\s+[^>]*?|\s*?)href=(?:(["\'])(.*?)\1|([^\s>]+))([^>]*?)>(.*?)</a>~is', $html, $matches, PREG_SET_ORDER);

        $totalLinks = 0;
        $internalCount = 0;
        $externalCount = 0;
        $nofollowCount = 0;
        $links = [];

        foreach ($matches as $m) {
            $href = isset($m[2]) && $m[2] !== '' ? trim($m[2]) : (isset($m[3]) ? trim($m[3]) : '');
            $attributes = isset($m[4]) ? $m[4] : '';
            $rawAnchor = isset($m[5]) ? $m[5] : '';
            $anchor = trim(strip_tags($rawAnchor));

            // Skip anchor-only or javascript pseudo links
            if ($href === '' || strpos($href, '#') === 0 || preg_match('~^(javascript|mailto|tel):~i', $href)) {
                continue;
            }

            $totalLinks++;

            // Extract rel attributes
            $rel = '';
            if (preg_match('~\brel=(?:(["\'])(.*?)\1|([^\s>]+))~i', $attributes, $relMatches)) {
                $rel = isset($relMatches[2]) && $relMatches[2] !== '' ? $relMatches[2] : (isset($relMatches[3]) ? $relMatches[3] : '');
            }
            $relTokens = preg_split('~\s+~', strtolower($rel), -1, PREG_SPLIT_NO_EMPTY);
            $isNofollow = in_array('nofollow', $relTokens, true);
            $isSponsored = in_array('sponsored', $relTokens, true);
            $isUgc = in_array('ugc', $relTokens, true);

            if ($isNofollow) {
                $nofollowCount++;
            }

            $isInternal = $this->isInternalUrl($href);
            if ($isInternal) {
                $internalCount++;
                $linkType = 'internal';
            } else {
                $externalCount++;
                $linkType = 'external';
            }

            // Clean url: remove fragment and normalize relative if siteUrl provided
            $cleanUrl = $href;
            if (strpos($cleanUrl, '#') !== false) {
                $cleanUrl = substr($cleanUrl, 0, strpos($cleanUrl, '#'));
            }
            $cleanUrl = rtrim($cleanUrl, '/');

            if ($isInternal && strpos($cleanUrl, 'http') !== 0 && $this->siteUrl) {
                if (strpos($cleanUrl, '/') === 0) {
                    $cleanUrl = $this->siteUrl . $cleanUrl;
                } else {
                    $cleanUrl = $this->siteUrl . '/' . $cleanUrl;
                }
            }

            $links[] = [
                'url'          => $cleanUrl,
                'link_type'    => $linkType,
                'anchor_text'  => $anchor,
                'is_nofollow'  => $isNofollow,
                'is_sponsored' => $isSponsored,
                'is_ugc'       => $isUgc,
            ];
        }

        return [
            'total_links'    => $totalLinks,
            'internal_links' => $internalCount,
            'external_links' => $externalCount,
            'nofollow_links' => $nofollowCount,
            'links'          => $links,
        ];
    }

    public function scanHtmlLinks(string $html, int $sourceId = 0): array {
        $scan = $this->scan($html);
        $result = [];
        foreach ($scan['links'] as $link) {
            $result[] = [
                'url'      => $link['url'],
                'anchor'   => $link['anchor_text'],
                'internal' => ($link['link_type'] === 'internal'),
            ];
        }
        return $result;
    }

    public function getInternalLinkSuggestions(int $postId): array {
        $sampleUrl = $this->siteUrl ? $this->siteUrl . '/sample-page/' : (function_exists('home_url') ? home_url('/sample-page/') : '/sample-page/');
        return [
            ['title' => 'Sample Target Page', 'url' => $sampleUrl, 'relevance' => 0.85]
        ];
    }
}
