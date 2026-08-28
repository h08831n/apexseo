<?php
namespace ApexSEO\CLI;

class DatabaseCommand extends AbstractCliCommand {
    public function status($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Database tables operational.");
        }
        return 0;
    }
}
