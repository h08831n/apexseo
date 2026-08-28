<?php
namespace ApexSEO\SEO\Sitemap;

use ApexSEO\Core\Database\DatabaseManager;

class SitemapGenerator {
    private $db;

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    public function generateIndexXml(): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<sitemap><loc>' . esc_url(home_url('/post-sitemap.xml')) . '</loc></sitemap>';
        $xml .= '<sitemap><loc>' . esc_url(home_url('/page-sitemap.xml')) . '</loc></sitemap>';
        $xml .= '</sitemapindex>';
        return $xml;
    }

    public function generateUrlsetXml(array $urls): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $item) {
            $xml .= '<url>';
            $xml .= '<loc>' . esc_url($item['loc']) . '</loc>';
            if (!empty($item['lastmod'])) {
                $xml .= '<lastmod>' . esc_html($item['lastmod']) . '</lastmod>';
            }
            $xml .= '</url>';
        }
        $xml .= '</urlset>';
        return $xml;
    }
}
