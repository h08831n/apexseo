<?php
namespace ApexSEO\Schema\Types;

class FAQPageSchema extends AbstractSchemaType {
    public function getType(): string { return 'FAQPage'; }
    public function generate(array $context): array {
        return [
            '@context'   => $this->getContext(),
            '@type'      => 'FAQPage',
            'mainEntity' => $context['faqs'] ?? [],
        ];
    }
}
