<?php
namespace ApexSEO\Schema\Types;

class ProductSchema extends AbstractSchemaType {
    public function getType(): string { return 'Product'; }
    public function generate(array $context): array {
        return [
            '@context'    => $this->getContext(),
            '@type'       => 'Product',
            'name'        => $context['product_name'] ?? ($context['title'] ?? 'Product'),
            'description' => $context['description'] ?? '',
        ];
    }
}
