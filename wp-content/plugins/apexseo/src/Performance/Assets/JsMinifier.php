<?php
namespace ApexSEO\Performance\Assets;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Safe JS Minifier & Optimizer.
 */
class JsMinifier implements ServiceContractInterface {
    /**
     * Minify JavaScript code safely.
     *
     * @param string $js Raw JS code.
     * @return string Minified JS.
     */
    public function minify($js) {
        if (empty($js)) {
            return '';
        }

        // 1. Remove multi-line comments (ignoring inline string literals where possible)
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);

        // 2. Remove single-line comments safely
        $lines = explode("\n", $js);
        $cleanLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                continue;
            }
            // Skip pure comment lines
            if (strpos($trimmed, '//') === 0) {
                continue;
            }
            $cleanLines[] = $trimmed;
        }

        return implode("\n", $cleanLines);
    }
}
