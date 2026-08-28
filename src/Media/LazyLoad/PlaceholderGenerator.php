<?php
namespace ApexSEO\Media\LazyLoad;

class PlaceholderGenerator {
    public function generateSvgPlaceholder(int $width, int $height, string $color = '#e2e8f0'): string {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d"><rect width="100%%" height="100%%" fill="%s"/></svg>',
            $width, $height, $width, $height, htmlspecialchars($color, ENT_QUOTES, 'UTF-8')
        );
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
