<?php
namespace ApexSEO\Performance\Assets;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * High-Speed CSS Minifier & Optimizer.
 */
class CssMinifier implements ServiceContractInterface {
    /**
     * Minify CSS string.
     *
     * @param string $css Raw CSS.
     * @return string Minified CSS.
     */
    public function minify($css) {
        if (empty($css)) {
            return '';
        }

        // 1. Strip comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // 2. Strip excess whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $css);
        $css = preg_replace('/\s+/', ' ', $css);

        // 3. Remove space around delimiters
        $css = preg_replace('/\s*([\{\};:,>~+])\s*/', '$1', $css);

        // 4. Remove trailing semicolons in declarations
        $css = str_replace(';}', '}', $css);

        // 5. Remove empty rulesets
        $css = preg_replace('/[^\};\{\/]+\{\}/', '', $css);

        return trim($css);
    }
}
