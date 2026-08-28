<?php
namespace ApexSEO\Schema\Types;

class OrganizationSchema extends AbstractSchemaType {
    public function getType(): string { return 'Organization'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'Organization',
            'name'     => $context['organization_name'] ?? get_bloginfo('name'),
            'url'      => home_url('/'),
        ];
    }
}
