<?php
namespace ApexSEO\Schema\Media;

use ApexSEO\Schema\Types\AbstractSchemaType;

class VideoObjectSchema extends AbstractSchemaType {
    public function getType(): string { return 'VideoObject'; }
    public function generate(array $context): array {
        return [
            '@context'     => $this->getContext(),
            '@type'        => 'VideoObject',
            'name'         => $context['video_title'] ?? ($context['title'] ?? 'Video'),
            'description'  => $context['description'] ?? '',
            'thumbnailUrl' => $context['thumbnail_url'] ?? '',
            'uploadDate'   => $context['upload_date'] ?? date('c'),
        ];
    }
}
