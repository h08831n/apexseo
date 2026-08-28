<?php
namespace ApexSEO\CLI;

use ApexSEO\SEO\Redirects\RedirectManager;

class RedirectCommand extends AbstractCliCommand {
    private $redirectManager;

    public function __construct(RedirectManager $redirectManager) {
        $this->redirectManager = $redirectManager;
    }

    public function list($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::line("Source | Target | Code");
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
