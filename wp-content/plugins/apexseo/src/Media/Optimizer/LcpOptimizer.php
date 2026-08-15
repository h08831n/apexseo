<?php
namespace ApexSEO\Media\Optimizer;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Largest Contentful Paint (LCP) Critical Image Optimizer.
 */
class LcpOptimizer implements ServiceContractInterface {
    /**
     * Inspect HTML content and automatically tag featured image / top images with fetchpriority="high".
     *
     * @param string $html
     * @return string
     */
    public function optimizeLcpImages($html) {
        if (empty($html) || strpos($html, '<img') === false) {
            return $html;
        }

        $count = 0;
        return preg_replace_callback('/<img\b([^>]*)>/i', function($matches) use (&$count) {
            $count++;
            $attrs = $matches[1];

            // Target the very first content image if not already prioritized
            if ($count === 1 && strpos($attrs, 'fetchpriority=') === false) {
                $attrs .= ' fetchpriority="high"';
            }

            return '<img' . $attrs . '>';
        }, $html);
    }
}
