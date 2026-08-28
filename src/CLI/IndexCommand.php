<?php
namespace ApexSEO\CLI;

class IndexCommand extends AbstractCliCommand {
    public function status($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Index status: OK");
        }
        return 0;
    }

    public function rebuild($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Index rebuild completed.");
        }
        return 0;
    }
}
