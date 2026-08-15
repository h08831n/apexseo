<?php
namespace ApexSEO\Media\LazyLoad;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Modern HTML Native & Polyfilled Image LazyLoader.
 */
class ImageLazyLoader implements ServiceContractInterface {
    /**
     * @var PlaceholderGenerator
     */
    protected $placeholderGenerator;

    /**
     * Constructor.
     *
     * @param PlaceholderGenerator $placeholderGenerator
     */
    public function __construct(PlaceholderGenerator $placeholderGenerator) {
        $this->placeholderGenerator = $placeholderGenerator;
    }

    /**
     * Process HTML to add loading="lazy", decoding="async", and prevent CLS.
     *
     * @param string $html
     * @param int $skipCount Number of leading above-the-fold images to exclude (for LCP).
     * @return string
     */
    public function processHtml($html, $skipCount = 1) {
        if (empty($html) || strpos($html, '<img') === false) {
            return $html;
        }

        $imageIndex = 0;

        return preg_replace_callback('/<img\b([^>]*)>/i', function($matches) use (&$imageIndex, $skipCount) {
            $imageIndex++;
            $attrs = $matches[1];

            // If image is marked as high priority / skip count, mark with fetchpriority="high" and loading="eager"
            if ($imageIndex <= $skipCount || strpos($attrs, 'fetchpriority="high"') !== false || strpos($attrs, 'data-no-lazy') !== false) {
                if (strpos($attrs, 'loading=') === false) {
                    $attrs .= ' loading="eager"';
                }
                if (strpos($attrs, 'fetchpriority=') === false) {
                    $attrs .= ' fetchpriority="high"';
                }
                if (strpos($attrs, 'decoding=') === false) {
                    $attrs .= ' decoding="async"';
                }
                return '<img' . $attrs . '>';
            }

            // Otherwise add loading="lazy" and decoding="async"
            if (strpos($attrs, 'loading=') === false) {
                $attrs .= ' loading="lazy"';
            }
            if (strpos($attrs, 'decoding=') === false) {
                $attrs .= ' decoding="async"';
            }

            return '<img' . $attrs . '>';
        }, $html);
    }
}
