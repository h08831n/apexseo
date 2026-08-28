<?php
namespace ApexSEO\Schema\Types;

class RecipeSchema extends AbstractSchemaType {
    public function getType(): string { return 'Recipe'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'Recipe',
            'name'     => $context['title'] ?? 'Recipe',
        ];
    }
}
