<?php
namespace ApexSEO\Performance\Assets;

class HtmlMinifier {
    public function minify(string $html): string {
        // Remove HTML comments except IE conditional comments
        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
        // Collapse whitespace outside <pre> or <code>
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        return trim($html);
    }
}
