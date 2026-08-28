<?php
namespace ApexSEO\SEO\Analysis;

use ApexSEO\Core\Database\DatabaseManager;

class LinkGraphScanner {
    private $db;

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    public function scanHtmlLinks(string $html, int $sourceId = 0): array {
        preg_match_all('/<a\s+[^>]*href=["']([^"']+)["'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);
        $links = [];
        $siteUrl = home_url();

        foreach ($matches as $m) {
            $href = $m[1];
            $anchor = strip_tags($m[2]);
            $isInternal = (strpos($href, $siteUrl) === 0 || strpos($href, '/') === 0);
            $links[] = [
                'url'      => $href,
                'anchor'   => $anchor,
                'internal' => $isInternal,
            ];
        }

        return $links;
    }

    public function getInternalLinkSuggestions(int $postId): array {
        return [
            ['title' => 'Sample Target Page', 'url' => home_url('/sample-page/'), 'relevance' => 0.85]
        ];
    }
}
