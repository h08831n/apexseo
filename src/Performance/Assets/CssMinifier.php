<?php
namespace ApexSEO\Performance\Assets;

class CssMinifier {
    public function minify(string $css): string {
        // Remove comments
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        // Remove excess whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
        $css = str_replace(';}', '}', $css);
        return trim($css);
    }
}
