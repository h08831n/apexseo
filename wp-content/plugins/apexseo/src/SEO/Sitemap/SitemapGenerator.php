<?php
namespace ApexSEO\SEO\Sitemap;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * High-Performance XML Sitemap Engine.
 */
class SitemapGenerator implements ServiceContractInterface {
    /**
     * Max URLs per sitemap chunk.
     *
     * @var int
     */
    protected $maxEntries = 1000;

    /**
     * Generate XML Sitemap Index.
     *
     * @param array<string, string> $sitemaps Array of [sitemap_url => lastmod]
     * @return string XML
     */
    public function renderSitemapIndex(array $sitemaps = []) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="/main-sitemap.xsl"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $url => $lastmod) {
            $xml .= "  <sitemap>\n";
            $xml .= sprintf("    <loc>%s</loc>\n", esc_url($url));
            if (!empty($lastmod)) {
                $xml .= sprintf("    <lastmod>%s</lastmod>\n", esc_html($lastmod));
            }
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Generate standard URL XML Sitemap.
     *
     * @param array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string, images?: array}> $urls
     * @return string XML
     */
    public function renderUrlSitemap(array $urls = []) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($urls as $entry) {
            if (empty($entry['loc'])) {
                continue;
            }
            $xml .= "  <url>\n";
            $xml .= sprintf("    <loc>%s</loc>\n", esc_url($entry['loc']));

            if (!empty($entry['lastmod'])) {
                $xml .= sprintf("    <lastmod>%s</lastmod>\n", esc_html($entry['lastmod']));
            }
            if (!empty($entry['changefreq'])) {
                $xml .= sprintf("    <changefreq>%s</changefreq>\n", esc_html($entry['changefreq']));
            }
            if (!empty($entry['priority'])) {
                $xml .= sprintf("    <priority>%s</priority>\n", esc_html($entry['priority']));
            }

            // Image sitemap nodes
            if (!empty($entry['images']) && is_array($entry['images'])) {
                foreach ($entry['images'] as $img) {
                    if (!empty($img['loc'])) {
                        $xml .= "    <image:image>\n";
                        $xml .= sprintf("      <image:loc>%s</image:loc>\n", esc_url($img['loc']));
                        if (!empty($img['title'])) {
                            $xml .= sprintf("      <image:title>%s</image:title>\n", esc_html($img['title']));
                        }
                        $xml .= "    </image:image>\n";
                    }
                }
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
