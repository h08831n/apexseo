<?php
namespace ApexSEO\Core\Environment\Server;

/**
 * Apache Web Server Capability Adapter.
 */
class ApacheAdapter implements ServerAdapterInterface {
    /**
     * {@inheritdoc}
     */
    public function getServerType() {
        return 'apache';
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'Apache HTTP Server';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsHtaccess() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNginxDirectives() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsLiteSpeedEngine() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEsi() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDirectGzipServing() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDirectBrotliServing() {
        return function_exists('apache_get_modules') ? in_array('mod_brotli', apache_get_modules(), true) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEarlyHints() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function flushServerCache($tags = null) {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generateDirectGzipRules(array $cachePaths = []) {
        return "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteCond %{HTTP:Accept-Encoding} gzip\nRewriteCond %{REQUEST_FILENAME}.gz -f\nRewriteRule ^(.*)$ $1.gz [L]\n</IfModule>";
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheControlHeaders(array $params = []) {
        $maxAge = isset($params['max_age']) ? (int) $params['max_age'] : 3600;
        return [
            'Cache-Control' => 'public, max-age=' . $maxAge,
        ];
    }
}
