<?php
namespace ApexSEO\Schema\Types;

/**
 * Schema.org LocalBusiness & Physical Location Generator.
 */
class LocalBusinessSchema extends AbstractSchemaType {
    /**
     * @var string
     */
    protected $businessType;

    /**
     * Constructor.
     *
     * @param string $businessType ('LocalBusiness', 'Restaurant', 'Store', etc.)
     */
    public function __construct($businessType = 'LocalBusiness') {
        $this->businessType = $businessType;
    }

    /**
     * {@inheritdoc}
     */
    public function getType() {
        return $this->businessType;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $context = []) {
        return !empty($context['is_local_business']) || !empty($context['business_address']);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $context = []) {
        $name = !empty($context['business_name']) ? $context['business_name'] : (function_exists('get_bloginfo') ? get_bloginfo('name') : 'Local Business');
        $siteUrl = function_exists('home_url') ? home_url('/') : 'https://example.com/';

        $schema = [
            '@type'       => $this->businessType,
            'name'        => $name,
            'url'         => $siteUrl,
            'telephone'   => !empty($context['business_phone']) ? $context['business_phone'] : null,
            'priceRange'  => !empty($context['price_range']) ? $context['price_range'] : '$$',
            'image'       => !empty($context['business_image']) ? $context['business_image'] : null,
        ];

        // Postal Address
        if (!empty($context['business_address']) && is_array($context['business_address'])) {
            $addr = $context['business_address'];
            $schema['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => isset($addr['street']) ? $addr['street'] : '',
                'addressLocality' => isset($addr['city']) ? $addr['city'] : '',
                'addressRegion'   => isset($addr['state']) ? $addr['state'] : '',
                'postalCode'      => isset($addr['postal_code']) ? $addr['postal_code'] : '',
                'addressCountry'  => isset($addr['country']) ? $addr['country'] : 'US',
            ];
        }

        // Geo Coordinates
        if (isset($context['latitude']) && isset($context['longitude'])) {
            $schema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float) $context['latitude'],
                'longitude' => (float) $context['longitude'],
            ];
        }

        return $this->cleanData($schema);
    }
}
