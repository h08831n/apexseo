<?php
namespace ApexSEO\Schema;

class SchemaGraphBuilder {
    private $registry;

    public function __construct(SchemaRegistry $registry) {
        $this->registry = $registry;
    }

    public function buildGraph(string $primaryType, array $context = []): array {
        $schemaType = $this->registry->get($primaryType);
        $primary = $schemaType ? $schemaType->generate($context) : [];

        $webSiteType = $this->registry->get('WebSite');
        $website = $webSiteType ? $webSiteType->generate($context) : [];

        return [
            '@context' => 'https://schema.org',
            '@graph'   => array_values(array_filter([$primary, $website]))
        ];
    }
}
