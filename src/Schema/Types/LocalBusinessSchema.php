<?php
namespace ApexSEO\Schema\Types;

class LocalBusinessSchema extends AbstractSchemaType {
    public function getType(): string { return 'LocalBusiness'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'LocalBusiness',
            'name'     => $context['business_name'] ?? get_bloginfo('name'),
            'address'  => $context['address'] ?? '123 Main St',
        ];
    }
}
