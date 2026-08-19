<?php
namespace ApexSEO\Schema\Media;

use ApexSEO\Schema\Types\AbstractSchemaType;

/**
 * VideoObject Structured Data Schema Type (APEX-077).
 * Conforms to Google Video Rich Snippets and Carousel specifications.
 */
class VideoObjectSchema extends AbstractSchemaType {
    /**
     * Get Schema.org type name.
     *
     * @return string
     */
    public function getType() {
        return 'VideoObject';
    }

    /**
     * Determine if VideoObject schema applies to context.
     *
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = []) {
        return !empty($context['has_video']) || !empty($context['video_url']) || (!empty($context['schema_type']) && $context['schema_type'] === 'VideoObject');
    }

    /**
     * Generate Schema.org structured data array.
     *
     * @param array $context
     * @return array
     */
    public function generate(array $context = []) {
        $canonical = $this->getCanonicalUrl($context);
        $data = [
            '@type'        => 'VideoObject',
            '@id'          => $canonical . '#video',
            'name'         => isset($context['video_title']) ? $context['video_title'] : (isset($context['title']) ? $context['title'] : 'Video'),
            'description'  => isset($context['video_description']) ? $context['video_description'] : (isset($context['description']) ? $context['description'] : ''),
            'thumbnailUrl' => isset($context['video_thumbnail']) ? $context['video_thumbnail'] : (isset($context['featured_image']) ? $context['featured_image'] : ''),
            'uploadDate'   => isset($context['video_upload_date']) ? $context['video_upload_date'] : (isset($context['date_published']) ? $context['date_published'] : date('c')),
        ];

        if (!empty($context['video_content_url'])) {
            $data['contentUrl'] = $context['video_content_url'];
        }

        if (!empty($context['video_embed_url'])) {
            $data['embedUrl'] = $context['video_embed_url'];
        }

        if (!empty($context['video_duration'])) {
            $data['duration'] = $context['video_duration']; // e.g., PT1M33S
        }

        return $data;
    }
}
