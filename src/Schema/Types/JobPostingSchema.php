<?php
namespace ApexSEO\Schema\Types;

class JobPostingSchema extends AbstractSchemaType {
    public function getType(): string { return 'JobPosting'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'JobPosting',
            'title'    => $context['job_title'] ?? ($context['title'] ?? 'Job'),
        ];
    }
}
