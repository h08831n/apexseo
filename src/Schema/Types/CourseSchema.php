<?php
namespace ApexSEO\Schema\Types;

class CourseSchema extends AbstractSchemaType {
    public function getType(): string { return 'Course'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'Course',
            'name'     => $context['title'] ?? 'Course',
        ];
    }
}
