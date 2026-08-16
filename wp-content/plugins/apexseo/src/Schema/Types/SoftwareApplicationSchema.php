<?php
namespace ApexSEO\Schema\Types;

/**
 * SoftwareApplication Structured Data Schema Type (APEX-076).
 * Conforms to Google SoftwareApplication Rich Result specifications.
 */
class SoftwareApplicationSchema extends AbstractSchemaType {
    /**
     * Get Schema.org type name.
     *
     * @return string
     */
    public function getType() {
        return 'SoftwareApplication';
    }

    /**
     * Determine if SoftwareApplication schema applies to context.
     *
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context) {
        return !empty($context['is_software']) || (!empty($context['schema_type']) && $context['schema_type'] === 'SoftwareApplication');
    }

    /**
     * Generate Schema.org structured data array.
     *
     * @param array $context
     * @return array
     */
    public function generate(array $context) {
        $canonical = $this->getCanonicalUrl($context);
        $data = [
            '@type'               => 'SoftwareApplication',
            '@id'                 => $canonical . '#software',
            'name'                => isset($context['title']) ? $context['title'] : '',
            'description'         => isset($context['description']) ? $context['description'] : '',
            'operatingSystem'     => isset($context['operating_system']) ? $context['operating_system'] : 'Windows, macOS, Linux, iOS, Android',
            'applicationCategory' => isset($context['application_category']) ? $context['application_category'] : 'https://schema.org/BusinessApplication',
            'offers'              => [
                '@type'         => 'Offer',
                'price'         => isset($context['price']) ? (string) $context['price'] : '0.00',
                'priceCurrency' => isset($context['currency']) ? $context['currency'] : 'USD',
                'availability'  => 'https://schema.org/InStock',
            ],
        ];

        if (!empty($context['software_version'])) {
            $data['softwareVersion'] = $context['software_version'];
        }

        if (!empty($context['download_url'])) {
            $data['downloadUrl'] = $context['download_url'];
        }

        if (!empty($context['rating_value']) && !empty($context['review_count'])) {
            $data['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (float) $context['rating_value'],
                'reviewCount' => (int) $context['review_count'],
            ];
        }

        if (!empty($context['featured_image'])) {
            $data['image'] = [
                '@type' => 'ImageObject',
                'url'   => $context['featured_image'],
            ];
        }

        return $data;
    }
}
