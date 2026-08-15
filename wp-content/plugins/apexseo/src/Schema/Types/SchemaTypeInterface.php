<?php
namespace ApexSEO\Schema\Types;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Schema Type Generator Contract.
 */
interface SchemaTypeInterface extends ServiceContractInterface {
    /**
     * Get Schema.org type name (e.g. 'Article', 'Product', 'FAQPage').
     *
     * @return string
     */
    public function getType();

    /**
     * Determine if this schema type is applicable to the current context.
     *
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = []);

    /**
     * Generate Schema.org structured data array.
     *
     * @param array $context
     * @return array
     */
    public function generate(array $context = []);
}
