<?php
namespace ApexSEO\Performance\Assets;

class JsMinifier {
    public function minify(string $js): string {
        // Remove multi-line comments
        $js = preg_replace('!/\*.*?\*/!s', '', $js);
        // Remove single line comments
        $js = preg_replace('!^\s*//.*$!m', '', $js);
        // Remove extra spaces
        $js = preg_replace('/[ \t]+/', ' ', $js);
        return trim($js);
    }
}
