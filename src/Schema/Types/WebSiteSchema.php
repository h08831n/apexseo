<?php
namespace ApexSEO\Schema\Types;

class WebSiteSchema extends AbstractSchemaType {
    public function getType(): string { return 'WebSite'; }
    public function generate(array $context): array {
        return [
            '@context' => $this->getContext(),
            '@type'    => 'WebSite',
            'name'     => $context['sitename'] ?? get_bloginfo('name'),
            'url'      => home_url('/'),
        ];
    }
}
