<?php
namespace ApexSEO\Schema\Types;

interface SchemaTypeInterface {
    public function getType(): string;
    public function generate(array $context): array;
}
