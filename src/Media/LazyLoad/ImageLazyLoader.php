<?php
namespace ApexSEO\Media\LazyLoad;

class ImageLazyLoader {
    private $placeholderGen;

    public function __construct(PlaceholderGenerator $placeholderGen) {
        $this->placeholderGen = $placeholderGen;
    }

    public function processHtml(string $html, int $skipFirstCount = 1): string {
        if (empty($html) || strpos($html, '<img') === false) {
            return $html;
        }

        $imgCount = 0;
        return preg_replace_callback('/<img\s+([^>]+)>/i', function($matches) use (&$imgCount, $skipFirstCount) {
            $imgCount++;
            $attributes = $matches[1];

            // If within skip count (LCP hero image)
            if ($imgCount <= $skipFirstCount) {
                if (strpos($attributes, 'loading=') === false) {
                    $attributes .= ' loading="eager"';
                }
                if (strpos($attributes, 'fetchpriority=') === false) {
                    $attributes .= ' fetchpriority="high"';
                }
                return '<img ' . trim($attributes) . '>';
            }

            // Below fold image: lazy load
            if (strpos($attributes, 'loading=') === false) {
                $attributes .= ' loading="lazy"';
            }
            if (strpos($attributes, 'decoding=') === false) {
                $attributes .= ' decoding="async"';
            }
            return '<img ' . trim($attributes) . '>';
        }, $html);
    }
}
