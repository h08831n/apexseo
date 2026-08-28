<?php
namespace ApexSEO\SEO\Robots;

class RobotsTxtManager {
    public function filterRobotsTxt(string $output, bool $public): string {
        if (!$public) {
            return "User-agent: *\nDisallow: /\n";
        }
        $sitemapUrl = home_url('/sitemap_index.xml');
        return $output . "\nSitemap: {$sitemapUrl}\n";
    }
}
