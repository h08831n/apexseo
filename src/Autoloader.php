<?php
namespace ApexSEO;

class Autoloader {
    private static $registered = false;
    private static $prefix = 'ApexSEO\\';
    private static $baseDir;

    public static function register(?string $baseDir = null): void {
        if (self::$registered) {
            return;
        }
        self::$baseDir = $baseDir ?: dirname(__DIR__) . '/src/';
        spl_autoload_register([__CLASS__, 'loadClass']);
        self::$registered = true;
    }

    public static function loadClass(string $class): bool {
        $prefixLen = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $prefixLen) !== 0) {
            return false;
        }

        $relativeClass = substr($class, $prefixLen);
        $file = self::$baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }

    public static function reset(): void {
        if (self::$registered) {
            spl_autoload_unregister([__CLASS__, 'loadClass']);
            self::$registered = false;
        }
    }
}
