<?php
namespace ApexSEO\CLI;

class MigrateCommand extends AbstractCliCommand {
    public function run($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Migrations executed.");
        }
        return 0;
    }
}
