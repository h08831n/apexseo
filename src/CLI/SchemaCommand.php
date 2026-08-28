<?php
namespace ApexSEO\CLI;

class SchemaCommand extends AbstractCliCommand {
    public function validate($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Schema valid.");
        }
        return 0;
    }
}
