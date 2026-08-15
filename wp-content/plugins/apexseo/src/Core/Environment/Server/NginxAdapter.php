<?php
namespace ApexSEO\Core\Environment\Server;

/**
 * Nginx Web Server Capability Adapter.
 */
class NginxAdapter implements ServerAdapterInterface {
    /**
     * {@inheritdoc}
     */
    public function getServerType() {
        return 'nginx';
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'Nginx HTTP Server';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsHtaccess() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNginxDirectives() {
        return true;
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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generateDirectGzipRules(array $cachePaths = []) {
        return "# Nginx static gzip serving\ngzip_static on;\ngzip_vary on;\n";
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
