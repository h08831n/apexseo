<?php
namespace ApexSEO\Schema\Validator;

class SchemaValidator {
    public function validate(array $schema): array {
        $errors = [];
        if (empty($schema['@context'])) {
            $errors[] = 'Missing @context';
        }
        if (empty($schema['@type'])) {
            $errors[] = 'Missing @type';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}
