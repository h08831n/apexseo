<?php
namespace ApexSEO\Schema\Types;

/**
 * Base Abstract Schema Type.
 */
abstract class AbstractSchemaType implements SchemaTypeInterface {
    /**
     * Filter empty or null values recursively.
     *
     * @param array $data
     * @return array
     */
    protected function cleanData(array $data) {
        $clean = [];
        foreach ($data as $k => $v) {
            if ($v === null || $v === '' || (is_array($v) && empty($v))) {
                continue;
            }
            if (is_array($v)) {
                $cleanedChild = $this->cleanData($v);
                if (!empty($cleanedChild) || isset($v['@type'])) {
                    $clean[$k] = $cleanedChild;
                }
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }

    /**
     * Resolve canonical URL safely from context or home URL.
     *
     * @param array $context
     * @return string
     */
    protected function getCanonicalUrl(array $context = []) {
        if (!empty($context['canonical_url'])) {
            return $context['canonical_url'];
        }
        if (!empty($context['url'])) {
            return $context['url'];
        }
        return function_exists('home_url') ? home_url('/') : 'https://example.com/';
    }
}
