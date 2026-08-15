<?php
namespace ApexSEO\Schema\Types;

/**
 * Schema.org Product & WooCommerce Entity Generator.
 */
class ProductSchema extends AbstractSchemaType {
    /**
     * {@inheritdoc}
     */
    public function getType() {
        return 'Product';
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $context = []) {
        return !empty($context['is_product']) || (isset($context['page_type']) && $context['page_type'] === 'product');
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $context = []) {
        $name = !empty($context['title']) ? $context['title'] : 'Product';
        $canonical = !empty($context['canonical_url']) ? $context['canonical_url'] : '';

        $schema = [
            '@type'       => 'Product',
            'name'        => $name,
            'description' => !empty($context['description']) ? $context['description'] : null,
            'sku'         => !empty($context['sku']) ? $context['sku'] : null,
            'brand'       => !empty($context['brand']) ? [
                '@type' => 'Brand',
                'name'  => $context['brand'],
            ] : null,
        ];

        if (!empty($context['featured_image'])) {
            $schema['image'] = $context['featured_image'];
        }

        // Offers
        if (isset($context['price'])) {
            $currency = !empty($context['currency']) ? $context['currency'] : 'USD';
            $availability = !empty($context['in_stock']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

            $schema['offers'] = [
                '@type'         => 'Offer',
                'url'           => $canonical,
                'priceCurrency' => $currency,
                'price'         => (string) $context['price'],
                'availability'  => $availability,
            ];
        }

        // Aggregate Rating
        if (!empty($context['rating_value']) && !empty($context['review_count'])) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (float) $context['rating_value'],
                'reviewCount' => (int) $context['review_count'],
            ];
        }

        return $this->cleanData($schema);
    }
}
