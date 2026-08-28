<?php
namespace ApexSEO\Media\Optimizer;

class LcpOptimizer {
    public function optimizeLcpImages(string $html): string {
        if (empty($html) || strpos($html, '<img') === false) {
            return $html;
        }

        $replaced = false;
        return preg_replace_callback('/<img\s+([^>]+)>/i', function($matches) use (&$replaced) {
            if ($replaced) {
                return $matches[0];
            }
            $attributes = $matches[1];
            if (strpos($attributes, 'fetchpriority=') === false) {
                $attributes .= ' fetchpriority="high"';
            }
            if (strpos($attributes, 'loading=') === false) {
                $attributes .= ' loading="eager"';
            }
            $replaced = true;
            return '<img ' . trim($attributes) . '>';
        }, $html, 1);
    }
}
