<?php
namespace ApexSEO\Schema\Types;

abstract class AbstractSchemaType implements SchemaTypeInterface {
    protected function getContext(): string {
        return 'https://schema.org';
    }
}
