<?php
namespace ApexSEO\SEO\Robots;

class RobotsHeaderManager {
    public function sendHeader(string $directive = 'noindex, nofollow'): void {
        if (!headers_sent()) {
            header("X-Robots-Tag: {$directive}", true);
        }
    }
}
