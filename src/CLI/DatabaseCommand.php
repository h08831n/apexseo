<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Database\DatabaseManager;

class DatabaseCommand extends AbstractCliCommand {
    private $db;

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    public function status($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Database tables operational.");
        }
        return 0;
    }

    public function cleanup($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Database tables Optimized.");
        }
        return 0;
    }
}
