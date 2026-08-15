<?php
namespace ApexSEO\Core\Environment;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Authoritative Capability Registry for Apex SEO Platform.
 */
class CapabilityRegistry implements ServiceContractInterface {
    /**
     * Environment detector instance.
     *
     * @var EnvironmentDetector
     */
    protected $detector;

    /**
     * Registered capability definitions.
     *
     * @var array
     */
    protected $capabilities = [];

    /**
     * Constructor.
     *
     * @param EnvironmentDetector $detector
     */
    public function __construct(EnvironmentDetector $detector) {
        $this->detector = $detector;
        $this->registerCoreCapabilities();
    }

    /**
     * Register a capability.
     *
     * @param string $id Unique capability key (e.g. 'server.litespeed_cache', 'media.webp_imagick').
     * @param string $status Status constant from EnvironmentDetector.
     * @param string $provider Provider class or subsystem.
     * @param array $details Additional metadata.
     * @return self
     */
    public function register($id, $status, $provider, array $details = []) {
        $this->capabilities[$id] = [
            'id'       => $id,
            'status'   => $status,
            'provider' => $provider,
            'details'  => $details,
        ];
        return $this;
    }

    /**
     * Check if a capability is available.
     *
     * @param string $id Capability ID.
     * @return bool True if status is AVAILABLE.
     */
    public function isAvailable($id) {
        return isset($this->capabilities[$id])
            && $this->capabilities[$id]['status'] === EnvironmentDetector::STATUS_AVAILABLE;
    }

    /**
     * Get capability definition array.
     *
     * @param string $id Capability ID.
     * @return array|null
     */
    public function get($id) {
        return isset($this->capabilities[$id]) ? $this->capabilities[$id] : null;
    }

    /**
     * Get all registered capabilities.
     *
     * @return array
     */
    public function all() {
        return $this->capabilities;
    }

    /**
     * Pre-populate core capabilities based on detected environment.
     *
     * @return void
     */
    protected function registerCoreCapabilities() {
        $server = $this->detector->getServerAdapter();

        // 1. Web Server Capabilities
        $this->register(
            'server.htaccess',
            $server->supportsHtaccess() ? EnvironmentDetector::STATUS_AVAILABLE : EnvironmentDetector::STATUS_UNAVAILABLE,
            get_class($server)
        );

        $this->register(
            'server.nginx_directives',
            $server->supportsNginxDirectives() ? EnvironmentDetector::STATUS_AVAILABLE : EnvironmentDetector::STATUS_UNAVAILABLE,
            get_class($server)
        );

        $this->register(
            'server.litespeed_cache',
            $server->supportsLiteSpeedEngine() ? EnvironmentDetector::STATUS_AVAILABLE : EnvironmentDetector::STATUS_UNAVAILABLE,
            get_class($server)
        );

        $this->register(
            'server.esi',
            $server->supportsEsi() ? EnvironmentDetector::STATUS_AVAILABLE : EnvironmentDetector::STATUS_UNAVAILABLE,
            get_class($server)
        );

        $this->register(
            'server.direct_gzip',
            $server->supportsDirectGzipServing() ? EnvironmentDetector::STATUS_AVAILABLE : EnvironmentDetector::STATUS_UNAVAILABLE,
            get_class($server)
        );

        $this->register(
            'server.direct_brotli',
            $server->supportsDirectBrotliServing() ? EnvironmentDetector::STATUS_AVAILABLE : EnvironmentDetector::STATUS_UNAVAILABLE,
            get_class($server)
        );

        // 2. Media / Image Optimization Capabilities
        $imagickStatus = $this->detector->getExtensionStatus('imagick');
        $gdStatus = $this->detector->getExtensionStatus('gd');

        $this->register('media.imagick', $imagickStatus, 'ImagickExtension');
        $this->register('media.gd', $gdStatus, 'GdExtension');

        $cwebpStatus = $this->detector->getBinaryStatus('cwebp');
        $this->register('media.cwebp_binary', $cwebpStatus, 'BinaryCwebp');

        $avifencStatus = $this->detector->getBinaryStatus('avifenc');
        $this->register('media.avifenc_binary', $avifencStatus, 'BinaryAvifenc');

        // WebP Support Determination
        $hasWebp = ($imagickStatus === EnvironmentDetector::STATUS_AVAILABLE && method_exists('Imagick', 'queryFormats') && in_array('WEBP', \Imagick::queryFormats(), true))
            || ($gdStatus === EnvironmentDetector::STATUS_AVAILABLE && function_exists('imagewebp'))
            || ($cwebpStatus === EnvironmentDetector::STATUS_AVAILABLE);

        $this->register(
            'media.webp_generation',
            $hasWebp ? EnvironmentDetector::STATUS_AVAILABLE : EnvironmentDetector::STATUS_UNAVAILABLE,
            $hasWebp ? 'LocalImageEngine' : 'None'
        );

        // 3. Object Cache Capabilities
        $redisStatus = $this->detector->getExtensionStatus('redis');
        $memcachedStatus = $this->detector->getExtensionStatus('memcached');
        $opcacheStatus = $this->detector->getExtensionStatus('opcache');

        $this->register('cache.redis', $redisStatus, 'RedisExtension');
        $this->register('cache.memcached', $memcachedStatus, 'MemcachedExtension');
        $this->register('cache.opcache', $opcacheStatus, 'OpcacheExtension');

        // 4. AST / Local Parsing Capabilities (Always available pure PHP)
        $this->register('asset.local_critical_css_ast', EnvironmentDetector::STATUS_AVAILABLE, 'LocalAstParser');
        $this->register('asset.local_rucss_ast', EnvironmentDetector::STATUS_AVAILABLE, 'LocalAstParser');
        $this->register('schema.jsonld_builder', EnvironmentDetector::STATUS_AVAILABLE, 'SchemaEngine');
        $this->register('link.graph_analyzer', EnvironmentDetector::STATUS_AVAILABLE, 'LinkGraphEngine');
    }
}
