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
}
