<?php
namespace ApexSEO\CLI;

use ApexSEO\Schema\SchemaValidator;

class SchemaCommand extends AbstractCliCommand {
    private $validator;

    public function __construct(SchemaValidator $validator) {
        $this->validator = $validator;
    }

    public function validate($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Schema is Valid.");
        }
        return 0;
    }
}
