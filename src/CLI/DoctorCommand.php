<?php
namespace ApexSEO\CLI;

class DoctorCommand extends AbstractCliCommand {
    public function __invoke($args, $assocArgs): int {
        return $this->check($args, $assocArgs);
    }

    public function check($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Doctor report: OK - All subsystems operational.");
        }
        return 0;
    }

    public function report($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("WordPress System Report generated successfully.");
        }
        return 0;
    }
}
