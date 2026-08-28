<?php
namespace ApexSEO\Core\Security;

class SecurityUtils {
    public static function sanitizeInput(string $str): string {
        return sanitize_text_field($str);
    }

    public static function sanitizePath(string $path): string {
        $path = str_replace(['../', '..\\', chr(0)], '', $path);
        return $path;
    }

    public static function validateSafeUrl(string $url): bool {
        $parsed = wp_parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return false;
        }
        $host = strtolower($parsed['host']);
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || strpos($host, '169.254.') === 0) {
            return false;
        }
        return in_array($parsed['scheme'] ?? '', ['http', 'https'], true);
    }
}
