<?php
namespace ApexSEO\Core\Environment\Server;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Server Adapter Contract for Web Server specific capabilities.
 */
interface ServerAdapterInterface extends ServiceContractInterface {
    /**
     * Get canonical web server identifier.
     *
     * @return string (e.g., 'apache', 'nginx', 'litespeed', 'openlitespeed', 'caddy', 'iis', 'generic')
     */
    public function getServerType();

    /**
     * Get human-readable server name.
     *
     * @return string
     */
    public function getName();

    /**
     * Check if the server supports .htaccess rewrite and header rules.
     *
     * @return bool
     */
    public function supportsHtaccess();

    /**
     * Check if the server supports Nginx fastcgi/microcaching configuration.
     *
     * @return bool
     */
    public function supportsNginxDirectives();

    /**
     * Check if the server supports LiteSpeed Cache response headers and LSCache engine.
     *
     * @return bool
     */
    public function supportsLiteSpeedEngine();

    /**
     * Check if the server supports Edge Side Includes (ESI).
     *
     * @return bool
     */
    public function supportsEsi();

    /**
     * Check if the server supports direct serving of pre-compressed Gzip static assets.
     *
     * @return bool
     */
    public function supportsDirectGzipServing();

    /**
     * Check if the server supports direct serving of pre-compressed Brotli static assets.
     *
     * @return bool
     */
    public function supportsDirectBrotliServing();

    /**
     * Check if the server supports HTTP/2 Server Push or Early Hints (103).
     *
     * @return bool
     */
    public function supportsEarlyHints();

    /**
     * Flush native web server cache if supported (e.g. LSWS tagged purge header).
     *
     * @param string|array|null $tags Optional cache tags.
     * @return bool
     */
    public function flushServerCache($tags = null);

    /**
     * Generate direct static Gzip rewrite / config rules.
     *
     * @param array $cachePaths Paths to include.
     * @return string
     */
    public function generateDirectGzipRules(array $cachePaths = []);

    /**
     * Generate HTTP Cache-Control and server-specific headers.
     *
     * @param array $params Parameter dictionary (max_age, tag, etc).
     * @return array
     */
    public function getCacheControlHeaders(array $params = []);
}
