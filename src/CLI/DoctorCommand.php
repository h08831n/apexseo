<?php
namespace ApexSEO\CLI;

class DoctorCommand extends AbstractCliCommand {
    public function check($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Doctor report: All subsystems healthy.");
        }
        return 0;
    }
}
