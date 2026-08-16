<?php
namespace ApexSEO\SEO\Integrations;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;

/**
 * WooCommerce SEO Extension for Product Metadata, Breadcrumbs, and Availability.
 */
class WooCommerceIntegration {
    /**
     * Determine if WooCommerce plugin is active.
     *
     * @return bool
     */
    public function isWooCommerceActive() {
        return class_exists('WooCommerce') || function_exists('WC');
    }

    /**
     * Enhance SeoContext with WooCommerce product attributes.
     *
     * @param SeoContext $context
     * @return SeoContext
     */
    public function enrichContext(SeoContext $context) {
        if (!$this->isWooCommerceActive()) {
            return $context;
        }

        if (function_exists('is_shop') && is_shop()) {
            $shopPageId = (int) get_option('woocommerce_shop_page_id');
            if ($shopPageId > 0) {
                $context->page_type = 'shop';
                $context->object_id = $shopPageId;
                $context->object_type = 'post';
                $context->object_sub_type = 'page';
            }
            return $context;
        }

        if (function_exists('is_product') && is_product() && $context->object_id) {
            $context->page_type = 'product';
            $context->og_type = 'product';

            if (function_exists('wc_get_product')) {
                $product = wc_get_product($context->object_id);
                if ($product) {
                    $context->extra['product_price'] = $product->get_price();
                    $context->extra['product_regular_price'] = $product->get_regular_price();
                    $context->extra['product_sale_price'] = $product->get_sale_price();
                    $context->extra['product_currency'] = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD';
                    $context->extra['product_in_stock'] = $product->is_in_stock() ? 'instock' : 'outofstock';
                    $context->extra['product_sku'] = $product->get_sku();
                }
            }
        }

        return $context;
    }
}
