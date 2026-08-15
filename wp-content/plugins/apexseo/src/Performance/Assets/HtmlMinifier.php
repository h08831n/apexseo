<?php
namespace ApexSEO\Performance\Assets;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Streaming / Output-Buffer HTML Minifier.
 */
class HtmlMinifier implements ServiceContractInterface {
    /**
     * Minify HTML buffer.
     *
     * @param string $html
     * @return string
     */
    public function minify($html) {
        if (empty($html)) {
            return '';
        }

        // Protect <pre>, <textarea>, <script>, and <style> blocks from destructive compression
        $protected = [];
        $html = preg_replace_callback('/<(pre|textarea|script|style)\b[^>]*>.*?<\/\1>/is', function($matches) use (&$protected) {
            $key = '<!--###APEX_PROTECTED_' . count($protected) . '###-->';
            $protected[$key] = $matches[0];
            return $key;
        }, $html);

        // Strip HTML comments (preserving IE conditionals)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);

        // Collapse whitespace between tags
        $html = preg_replace('/>\s+</', '> <', $html);
        $html = preg_replace('/\s+/', ' ', $html);

        // Restore protected blocks
        if (!empty($protected)) {
            $html = str_replace(array_keys($protected), array_values($protected), $html);
        }

        return trim($html);
    }
}
