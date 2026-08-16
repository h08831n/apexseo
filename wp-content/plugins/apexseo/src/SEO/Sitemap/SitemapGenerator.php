<?php
namespace ApexSEO\SEO\Sitemap;

/**
 * High-Performance XML Sitemap Generator.
 */
class SitemapGenerator {
    /**
     * Render XML URL set sitemap string.
     *
     * @param array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string|float}> $urls
     * @return string
     */
    public function renderUrlSitemap(array $urls) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $entry) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";

            if (!empty($entry['lastmod'])) {
                $xml .= '    <lastmod>' . htmlspecialchars($entry['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
            }
            if (!empty($entry['changefreq'])) {
                $xml .= '    <changefreq>' . htmlspecialchars($entry['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>' . "\n";
            }
            if (isset($entry['priority'])) {
                $xml .= '    <priority>' . sprintf('%.1f', (float) $entry['priority']) . '</priority>' . "\n";
            }

            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Render XML Sitemap Index string.
     *
     * @param array<int, array{loc: string, lastmod?: string}> $sitemaps
     * @return string
     */
    public function renderIndexSitemap(array $sitemaps) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sitemap) {
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($sitemap['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            if (!empty($sitemap['lastmod'])) {
                $xml .= '    <lastmod>' . htmlspecialchars($sitemap['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
            }
            $xml .= '  </sitemap>' . "\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }
}
