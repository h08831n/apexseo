<?php
namespace ApexSEO\Media\LazyLoad;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Aspect-Ratio Preserving SVG LQIP Placeholder Generator.
 */
class PlaceholderGenerator implements ServiceContractInterface {
    /**
     * Generate inline lightweight SVG data URI placeholder with aspect ratio.
     *
     * @param int $width
     * @param int $height
     * @param string $bgColor
     * @return string Data URI
     */
    public function generateSvgPlaceholder($width = 100, $height = 100, $bgColor = '#f3f4f6') {
        $w = (int) $width;
        $h = (int) $height;
        if ($w <= 0) $w = 100;
        if ($h <= 0) $h = 100;

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d"><rect width="100%%" height="100%%" fill="%s"/></svg>',
            $w,
            $h,
            $w,
            $h,
            esc_attr($bgColor)
        );

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
