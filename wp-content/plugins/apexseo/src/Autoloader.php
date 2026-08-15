<?php
namespace ApexSEO;

/**
 * Deterministic PSR-4 Autoloader for Apex SEO Platform.
 *
 * Implements strict, high-performance prefix-to-directory mapping without runtime filesystem scanning.
 */
class Autoloader {
    /**
     * Namespace prefix.
     *
     * @var string
     */
    private static $prefix = 'ApexSEO\\';

    /**
     * Base directory for the namespace prefix.
     *
     * @var string
     */
    private static $baseDir = '';

    /**
     * Test namespace prefix.
     *
     * @var string
     */
    private static $testPrefix = 'ApexSEO\\Tests\\';

    /**
     * Test directory.
     *
     * @var string
     */
    private static $testDir = '';

    /**
     * Indicates whether the autoloader has been registered.
     *
     * @var bool
     */
    private static $registered = false;

    /**
     * Register the autoloader with SPL autoloader stack.
     *
     * @param string|null $baseDir Optional base directory override.
     * @return bool True on success.
     */
    public static function register($baseDir = null) {
        if (self::$registered) {
            return true;
        }

        self::$baseDir = $baseDir !== null ? rtrim($baseDir, '/\\') . '/' : dirname(__DIR__) . '/src/';
        self::$testDir = dirname(__DIR__) . '/tests/';

        self::$registered = spl_autoload_register([__CLASS__, 'loadClass']);
        return self::$registered;
    }

    /**
     * Unregister the autoloader.
     *
     * @return bool True on success.
     */
    public static function unregister() {
        if (!self::$registered) {
            return false;
        }

        $unregistered = spl_autoload_unregister([__CLASS__, 'loadClass']);
        if ($unregistered) {
            self::$registered = false;
        }
        return $unregistered;
    }

    /**
     * Load the requested class file based on PSR-4 rules.
     *
     * @param string $class Fully-qualified class name.
     * @return bool True if loaded, false otherwise.
     */
    public static function loadClass($class) {
        // Check test namespace first if applicable
        $testLen = strlen(self::$testPrefix);
        if (strncmp(self::$testPrefix, $class, $testLen) === 0) {
            $relativeClass = substr($class, $testLen);
            $file = self::$testDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            return false;
        }

        // Check main plugin namespace
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return false;
        }

        $relativeClass = substr($class, $len);
        $file = self::$baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }
}
