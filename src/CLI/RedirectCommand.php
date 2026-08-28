<?php
namespace ApexSEO\CLI;

class RedirectCommand extends AbstractCliCommand {
    public function list($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Listing redirects.");
        }
        return 0;
    }

    public function add($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Redirect added.");
        }
        return 0;
    }
}
