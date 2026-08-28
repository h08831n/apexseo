<?php
namespace ApexSEO\CLI;

use ApexSEO\Performance\Cache\SmartPurge;

class CacheCommand extends AbstractCliCommand {
    private $purge;

    public function __construct(SmartPurge $purge) {
        $this->purge = $purge;
    }

    public function purge($args, $assocArgs): int {
        $purged = $this->purge->purgeAll();
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Cache Purged.");
        }
        return 0;
    }

    public function warmup($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::error("Cache warmup is not implemented.", false);
        }
        return 1;
    }
}
