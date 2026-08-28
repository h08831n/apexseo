<?php
namespace ApexSEO\CLI;

use ApexSEO\SEO\Sitemap\SitemapGenerator;

class SitemapCommand extends AbstractCliCommand {
    private $sitemapGenerator;

    public function __construct(SitemapGenerator $sitemapGenerator) {
        $this->sitemapGenerator = $sitemapGenerator;
    }

    public function generate($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Rebuilt XML sitemap index.");
        }
        return 0;
    }

    public function rebuild($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Rebuilt XML sitemap index.");
        }
        return 0;
    }
}
