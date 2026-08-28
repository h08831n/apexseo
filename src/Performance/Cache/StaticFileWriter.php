<?php
namespace ApexSEO\Performance\Cache;

class StaticFileWriter {
    private $cacheDir;

    public function __construct(?string $cacheDir = null) {
        $this->cacheDir = $cacheDir ?: (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/cache/apexseo' : sys_get_temp_dir() . '/apex_cache');
        if (!file_exists($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public function getCacheDir(): string {
        return $this->cacheDir;
    }

    public function getCachedFilesCount(): int {
        if (!file_exists($this->cacheDir)) {
            return 0;
        }
        $files = glob($this->cacheDir . '/*.html');
        return is_array($files) ? count($files) : 0;
    }

    public function writeCache(string $url, string $content): bool {
        $key = md5($url);
        $file = $this->cacheDir . '/' . $key . '.html';
        return (bool) file_put_contents($file, $content);
    }

    public function readCache(string $url): ?string {
        $key = md5($url);
        $file = $this->cacheDir . '/' . $key . '.html';
        return file_exists($file) ? file_get_contents($file) : null;
    }

    public function deleteCache(string $url): bool {
        $key = md5($url);
        $file = $this->cacheDir . '/' . $key . '.html';
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    public function flushAll(): bool {
        $files = glob($this->cacheDir . '/*');
        if (is_array($files)) {
            foreach ($files as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
        return true;
    }
}
