<?php
namespace ApexSEO\Core\Environment\Server;

/**
 * LiteSpeed Enterprise Web Server Capability Adapter.
 */
class LiteSpeedAdapter implements ServerAdapterInterface {
    /**
     * {@inheritdoc}
     */
    public function getServerType() {
        return 'litespeed';
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'LiteSpeed Web Server Enterprise';
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEsi() {
        return true;
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEarlyHints() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flushServerCache($tags = null) {
        if (headers_sent()) {
            return false;
        }

        if ($tags === null || $tags === '*' || $tags === 'all') {
            header('X-LiteSpeed-Purge: *');
            return true;
        }

        $tagList = is_array($tags) ? implode(',', array_map('trim', $tags)) : (string) $tags;
        header('X-LiteSpeed-Purge: ' . $tagList);
        return true;
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
        $maxAge = isset($params['max_age']) ? (int) $params['max_age'] : 86400;
        $headers = [
            'X-LiteSpeed-Cache-Control' => 'public, max-age=' . $maxAge,
        ];
        if (!empty($params['tag'])) {
            $headers['X-LiteSpeed-Tag'] = (string) $params['tag'];
        }
        return $headers;
    }
}
