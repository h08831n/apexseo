<?php
namespace ApexSEO\SEO\Integrations;

class WooCommerceIntegration {
    public function isWooCommerceActive(): bool {
        return class_exists('WooCommerce');
    }

    public function enhanceProductSchema(array $schema, int $productId): array {
        if (!$this->isWooCommerceActive()) {
            return $schema;
        }
        $product = function_exists('wc_get_product') ? wc_get_product($productId) : null;
        if ($product) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => method_exists($product, 'get_price') ? $product->get_price() : '0.00',
                'priceCurrency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD',
            ];
        }
        return $schema;
    }
}
