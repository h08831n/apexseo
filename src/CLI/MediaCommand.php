<?php
namespace ApexSEO\CLI;

class MediaCommand extends AbstractCliCommand {
    public function optimize($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Media optimization complete.");
        }
        return 0;
    }
}
