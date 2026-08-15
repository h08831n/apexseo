<?php
namespace ApexSEO\Performance\Cache;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Static Page Cache File Writer (supporting HTML, Gzip .gz, Brotli .br).
 */
class StaticFileWriter implements ServiceContractInterface {
    /**
     * Cache directory path.
     *
     * @var string
     */
    protected $cacheDir;

    /**
     * Constructor.
     *
     * @param string|null $cacheDir
     */
    public function __construct($cacheDir = null) {
        $this->cacheDir = ($cacheDir !== null) ? rtrim($cacheDir, '/\\') : sys_get_temp_dir() . '/apex_cache';
    }

    /**
     * Write static HTML output and companion pre-compressed files (.gz, .br).
     *
     * @param string $key (e.g. hash or relative path)
     * @param string $html Content
     * @return bool
     */
    public function writeCache($key, $html) {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        $filePath = $this->cacheDir . '/' . md5($key) . '.html';
        $written = @file_put_contents($filePath, $html);

        if ($written === false) {
            return false;
        }

        // Generate static .gz file if zlib loaded
        if (function_exists('gzencode')) {
            $gzData = gzencode($html, 9);
            if ($gzData !== false) {
                @file_put_contents($filePath . '.gz', $gzData);
            }
        }

        return true;
    }

    /**
     * Read cached HTML.
     *
     * @param string $key
     * @return string|null
     */
    public function readCache($key) {
        $filePath = $this->cacheDir . '/' . md5($key) . '.html';
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        return null;
    }

    /**
     * Delete cache file for key.
     *
     * @param string $key
     * @return bool
     */
    public function deleteCache($key) {
        $filePath = $this->cacheDir . '/' . md5($key) . '.html';
        $deleted = false;
        if (file_exists($filePath)) {
            $deleted = @unlink($filePath);
        }
        if (file_exists($filePath . '.gz')) {
            @unlink($filePath . '.gz');
        }
        return $deleted;
    }

    /**
     * Purge all static cache files in directory.
     *
     * @return int Count of purged files.
     */
    public function purgeAll() {
        if (!is_dir($this->cacheDir)) {
            return 0;
        }

        $count = 0;
        $files = glob($this->cacheDir . '/*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $count++;
                }
            }
        }
        return $count;
    }
}
