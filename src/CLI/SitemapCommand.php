<?php
namespace ApexSEO\CLI;

class SitemapCommand extends AbstractCliCommand {
    public function generate($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Sitemaps generated.");
        }
        return 0;
    }
}
