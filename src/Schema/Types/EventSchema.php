<?php
namespace ApexSEO\Schema\Types;

class EventSchema extends AbstractSchemaType {
    public function getType(): string { return 'Event'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'Event',
            'name'     => $context['title'] ?? 'Event',
        ];
    }
}
