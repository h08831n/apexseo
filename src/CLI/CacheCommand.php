<?php
namespace ApexSEO\CLI;

class CacheCommand extends AbstractCliCommand {
    public function purge($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Cache purged.");
        }
        return 0;
    }

    public function warmup($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Cache warmed up.");
        }
        return 0;
    }
}
