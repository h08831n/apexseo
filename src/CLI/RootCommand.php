<?php
namespace ApexSEO\CLI;

class RootCommand extends AbstractCliCommand {
    public function __invoke($args, $assocArgs): void {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::line("Apex SEO Unified Platform CLI (Version 1.0.0)");
            \WP_CLI::line("Usage: wp apexseo <subcommand>");
        }
    }
}
