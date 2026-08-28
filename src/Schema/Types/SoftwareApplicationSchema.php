<?php
namespace ApexSEO\Schema\Types;

class SoftwareApplicationSchema extends AbstractSchemaType {
    public function getType(): string { return 'SoftwareApplication'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'SoftwareApplication',
            'name'     => $context['title'] ?? 'App',
        ];
    }
}
