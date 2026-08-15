<?php
namespace ApexSEO\Core\Security;

/**
 * Reusable static security utilities for Apex SEO Platform.
 */
class SecurityUtils {
    /**
     * Validate that a regular expression pattern is syntactically valid and safe against catastrophic backtracking.
     *
     * @param string $pattern
     * @return bool
     */
    public static function isValidRegex($pattern) {
        if (empty($pattern) || !is_string($pattern)) {
            return false;
        }

        // Must have valid delimiters
        $delimiter = substr($pattern, 0, 1);
        if (!in_array($delimiter, ['/', '#', '~', '@'], true)) {
            return false;
        }

        // Suppress warning during test match
        $test = @preg_match($pattern, '');
        return $test !== false;
    }

    /**
     * Safely match a regex pattern against a subject with backtracking limits.
     *
     * @param string $pattern
     * @param string $subject
     * @param array $matches
     * @param int $flags
     * @param int $offset
     * @return int|false
     */
    public static function safePregMatch($pattern, $subject, &$matches = null, $flags = 0, $offset = 0) {
        if (!self::isValidRegex($pattern)) {
            return false;
        }

        // Cap subject length to prevent ReDoS on massive inputs
        if (strlen($subject) > 65536) {
            $subject = substr($subject, 0, 65536);
        }

        return @preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Sanitize and validate file path to prevent directory traversal and null byte injections.
     *
     * @param string $path
     * @param string|null $baseDirectory
     * @return string|false Normalized absolute path or false if invalid/traversal detected.
     */
    public static function sanitizePath($path, $baseDirectory = null) {
        if (!is_string($path) || empty($path)) {
            return false;
        }

        // Disallow null bytes
        if (strpos($path, "\0") !== false) {
            return false;
        }

        // Disallow directory traversal segments
        if (strpos($path, '..') !== false) {
            return false;
        }

        $clean = wp_normalize_path($path);

        if ($baseDirectory !== null) {
            $cleanBase = wp_normalize_path(rtrim($baseDirectory, '/\\')) . '/';
            if (strpos($clean, $cleanBase) !== 0) {
                return false;
            }
        }

        return $clean;
    }

    /**
     * Validate and sanitize redirect target URLs to prevent open redirect vulnerabilities.
     *
     * @param string $url Target redirect URL.
     * @param string|null $fallback Safe fallback URL (default home_url()).
     * @return string Validated safe URL.
     */
    public static function validateRedirectUrl($url, $fallback = null) {
        $defaultFallback = $fallback !== null ? $fallback : (function_exists('home_url') ? home_url('/') : '/');

        if (empty($url) || !is_string($url)) {
            return $defaultFallback;
        }

        // Disallow javascript: or data: URIs
        $trimmed = strtolower(trim($url));
        if (strpos($trimmed, 'javascript:') === 0 || strpos($trimmed, 'data:') === 0 || strpos($trimmed, 'vbscript:') === 0) {
            return $defaultFallback;
        }

        // If WordPress validate_redirect exists, use it
        if (function_exists('wp_validate_redirect')) {
            $validated = wp_validate_redirect($url, false);
            if ($validated !== false) {
                return $validated;
            }
        }

        // Allow relative URLs starting with / (excluding // which represents protocol-relative external domain)
        if (substr($url, 0, 1) === '/' && substr($url, 0, 2) !== '//') {
            return $url;
        }

        return $defaultFallback;
    }

    /**
     * Recursively sanitize nested array values.
     *
     * @param array $array
     * @return array
     */
    public static function sanitizeArray(array $array) {
        $clean = [];
        foreach ($array as $key => $value) {
            $cleanKey = is_string($key) ? sanitize_key($key) : $key;
            if (is_array($value)) {
                $clean[$cleanKey] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $clean[$cleanKey] = sanitize_text_field($value);
            } else {
                $clean[$cleanKey] = $value;
            }
        }
        return $clean;
    }
}
