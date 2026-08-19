<?php
namespace ApexSEO\Core\Environment;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Environment\Server\ServerAdapterInterface;
use ApexSEO\Core\Environment\Server\ApacheAdapter;
use ApexSEO\Core\Environment\Server\NginxAdapter;
use ApexSEO\Core\Environment\Server\LiteSpeedAdapter;
use ApexSEO\Core\Environment\Server\OpenLiteSpeedAdapter;
use ApexSEO\Core\Environment\Server\GenericServerAdapter;

/**
 * Deterministic Environment and Runtime Detector.
 */
class EnvironmentDetector implements ServiceContractInterface {
    const STATUS_AVAILABLE       = 'AVAILABLE';
    const STATUS_UNAVAILABLE     = 'UNAVAILABLE';
    const STATUS_UNKNOWN         = 'UNKNOWN';
    const STATUS_NOT_APPLICABLE  = 'NOT_APPLICABLE';

    /**
     * Cached server adapter instance.
     *
     * @var ServerAdapterInterface|null
     */
    protected $serverAdapter = null;

    /**
     * Cached detection results.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Get PHP runtime version.
     *
     * @return string
     */
    public function getPhpVersion() {
        return PHP_VERSION;
    }

    /**
     * Check if PHP version satisfies a minimum constraint.
     *
     * @param string $minVersion
     * @return bool
     */
    public function isPhpVersionAtLeast($minVersion) {
        return version_compare(PHP_VERSION, $minVersion, '>=');
    }

    /**
     * Check if PHP version meets minimum plugin requirements (7.4.0+).
     *
     * @return bool
     */
    public function isSupportedPhp() {
        return $this->isPhpVersionAtLeast('7.4.0');
    }

    /**
     * Check if WordPress version meets minimum plugin requirements (6.2.0+).
     *
     * @return bool
     */
    public function isSupportedWordPress() {
        return version_compare($this->getWordPressVersion(), '6.2.0', '>=');
    }

    /**
     * Check if an extension is available.
     *
     * @param string $extension
     * @return bool
     */
    public function hasExtension($extension) {
        return $this->getExtensionStatus($extension) === self::STATUS_AVAILABLE;
    }

    /**
     * Get Server API (SAPI) name.
     *
     * @return string
     */
    public function getSapi() {
        return php_sapi_name();
    }

    /**
     * Check if running under PHP-FPM or FastCGI.
     *
     * @return bool
     */
    public function isFpm() {
        $sapi = $this->getSapi();
        return strpos($sapi, 'fpm') !== false || strpos($sapi, 'cgi') !== false;
    }

    /**
     * Check if running in CLI mode.
     *
     * @return bool
     */
    public function isCli() {
        return php_sapi_name() === 'cli' || defined('WP_CLI');
    }

    /**
     * Get memory limit in bytes.
     *
     * @return int
     */
    public function getMemoryLimitBytes() {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return -1;
        }
        return $this->parseSizeToBytes($limit);
    }

    /**
     * Get WordPress core version.
     *
     * @return string
     */
    public function getWordPressVersion() {
        global $wp_version;
        return isset($wp_version) ? $wp_version : '6.2.0';
    }

    /**
     * Check if current site is WordPress Multisite.
     *
     * @return bool
     */
    public function isMultisite() {
        return function_exists('is_multisite') && is_multisite();
    }

    /**
     * Check if WP_DEBUG is active.
     *
     * @return bool
     */
    public function isDebug() {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Check if WP_CACHE constant is active.
     *
     * @return bool
     */
    public function isWpCacheEnabled() {
        return defined('WP_CACHE') && WP_CACHE;
    }

    /**
     * Detect Web Server software string.
     *
     * @return string
     */
    public function getServerSoftware() {
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            return (string) $_SERVER['SERVER_SOFTWARE'];
        }
        return '';
    }

    /**
     * Detect and instantiate the appropriate Web Server Adapter.
     *
     * @return ServerAdapterInterface
     */
    public function getServerAdapter() {
        if ($this->serverAdapter !== null) {
            return $this->serverAdapter;
        }

        $software = strtolower($this->getServerSoftware());

        // 1. LiteSpeed Enterprise Check
        if (strpos($software, 'litespeed') !== false || isset($_SERVER['X-LSCACHE'])) {
            $this->serverAdapter = new LiteSpeedAdapter();
            return $this->serverAdapter;
        }

        // 2. OpenLiteSpeed Check
        if (strpos($software, 'openlitespeed') !== false) {
            $this->serverAdapter = new OpenLiteSpeedAdapter();
            return $this->serverAdapter;
        }

        // 3. Nginx Check
        if (strpos($software, 'nginx') !== false) {
            $this->serverAdapter = new NginxAdapter();
            return $this->serverAdapter;
        }

        // 4. Apache Check
        if (strpos($software, 'apache') !== false) {
            $this->serverAdapter = new ApacheAdapter();
            return $this->serverAdapter;
        }

        // 5. Caddy Check
        if (strpos($software, 'caddy') !== false) {
            $this->serverAdapter = new GenericServerAdapter('caddy', 'Caddy Web Server');
            return $this->serverAdapter;
        }

        // 6. Microsoft IIS Check
        if (strpos($software, 'microsoft-iis') !== false) {
            $this->serverAdapter = new GenericServerAdapter('iis', 'Microsoft IIS');
            return $this->serverAdapter;
        }

        $this->serverAdapter = new GenericServerAdapter('generic', 'Generic Web Server');
        return $this->serverAdapter;
    }

    /**
     * Get detected server type identifier (e.g. 'nginx', 'apache', 'litespeed', 'generic').
     *
     * @return string
     */
    public function getServerType() {
        return $this->getServerAdapter()->getName();
    }

    /**
     * Set explicit server adapter (useful for testing).
     *
     * @param ServerAdapterInterface $adapter
     * @return void
     */
    public function setServerAdapter(ServerAdapterInterface $adapter) {
        $this->serverAdapter = $adapter;
    }

    /**
     * Check PHP extension availability status.
     *
     * @param string $extension Name of extension.
     * @return string Status constant.
     */
    public function getExtensionStatus($extension) {
        $ext = strtolower($extension);

        switch ($ext) {
            case 'imagick':
                return (extension_loaded('imagick') && class_exists('Imagick'))
                    ? self::STATUS_AVAILABLE
                    : self::STATUS_UNAVAILABLE;

            case 'gd':
                return (extension_loaded('gd') && function_exists('gd_info'))
                    ? self::STATUS_AVAILABLE
                    : self::STATUS_UNAVAILABLE;

            case 'redis':
                return (extension_loaded('redis') && class_exists('Redis'))
                    ? self::STATUS_AVAILABLE
                    : self::STATUS_UNAVAILABLE;

            case 'memcached':
                return (extension_loaded('memcached') && class_exists('Memcached'))
                    ? self::STATUS_AVAILABLE
                    : self::STATUS_UNAVAILABLE;

            case 'opcache':
                return (extension_loaded('Zend OPcache') && function_exists('opcache_get_status') && ini_get('opcache.enable'))
                    ? self::STATUS_AVAILABLE
                    : self::STATUS_UNAVAILABLE;

            case 'curl':
                return extension_loaded('curl') ? self::STATUS_AVAILABLE : self::STATUS_UNAVAILABLE;

            case 'mbstring':
                return extension_loaded('mbstring') ? self::STATUS_AVAILABLE : self::STATUS_UNAVAILABLE;

            case 'zlib':
                return extension_loaded('zlib') ? self::STATUS_AVAILABLE : self::STATUS_UNAVAILABLE;

            default:
                return extension_loaded($ext) ? self::STATUS_AVAILABLE : self::STATUS_UNAVAILABLE;
        }
    }

    /**
     * Check binary/CLI executable availability on the server.
     *
     * @param string $binary Name of binary (e.g. 'cwebp', 'avifenc', 'gzip').
     * @return string Status constant.
     */
    public function getBinaryStatus($binary) {
        $cacheKey = 'bin_' . $binary;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if (!$this->canExecuteCommands()) {
            $this->cache[$cacheKey] = self::STATUS_UNAVAILABLE;
            return self::STATUS_UNAVAILABLE;
        }

        $sanitizedBin = preg_replace('/[^a-zA-Z0-9_\-]/', '', $binary);
        if (empty($sanitizedBin)) {
            return self::STATUS_UNAVAILABLE;
        }

        // Test executable using command -v or which
        $output = @shell_exec(sprintf('command -v %s 2>/dev/null || which %s 2>/dev/null', escapeshellarg($sanitizedBin), escapeshellarg($sanitizedBin)));

        $status = (!empty($output) && trim($output) !== '') ? self::STATUS_AVAILABLE : self::STATUS_UNAVAILABLE;
        $this->cache[$cacheKey] = $status;
        return $status;
    }

    /**
     * Check if shell command execution functions are permitted.
     *
     * @return bool
     */
    public function canExecuteCommands() {
        if (!function_exists('shell_exec') || !function_exists('exec')) {
            return false;
        }

        $disabled = ini_get('disable_functions');
        if (!empty($disabled)) {
            $disabledList = array_map('trim', explode(',', strtolower($disabled)));
            if (in_array('shell_exec', $disabledList, true) || in_array('exec', $disabledList, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper to parse human-readable sizes (e.g., '256M', '1G') to bytes.
     *
     * @param string $size
     * @return int
     */
    protected function parseSizeToBytes($size) {
        $size = trim($size);
        $last = strtolower(substr($size, -1));
        $val = (int) substr($size, 0, -1);

        switch ($last) {
            case 'g':
                $val *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $val *= 1024 * 1024;
                break;
            case 'k':
                $val *= 1024;
                break;
            default:
                $val = (int) $size;
                break;
        }

        return $val;
    }
}
