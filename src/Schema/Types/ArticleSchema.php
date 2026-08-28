<?php
namespace ApexSEO\Schema\Types;

class ArticleSchema extends AbstractSchemaType {
    public function getType(): string { return 'Article'; }
    public function generate(array $context): array {
        return [
            '@context'         => $this->getContext(),
            '@type'            => 'Article',
            'headline'         => $context['title'] ?? '',
            'description'      => $context['description'] ?? '',
            'mainEntityOfPage' => $context['canonical_url'] ?? '',
            'author'           => [
                '@type' => 'Person',
                'name'  => $context['author_name'] ?? 'Author',
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $context['sitename'] ?? 'Site',
            ]
        ];
    }
}
