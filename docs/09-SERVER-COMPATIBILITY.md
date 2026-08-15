# 09 - Server & Environment Compatibility Specification

## 1. Compatibility Overview & Targets
Apex SEO Platform is engineered for broad compatibility across web servers and PHP runtimes:

- **Minimum PHP Version**: `7.4.0`
- **Recommended PHP Version**: `8.2.x` / `8.3.x`
- **Maximum Tested PHP Version**: `8.4.x`
- **Minimum WordPress Version**: `5.8.0`
- **Recommended WordPress Version**: `6.5+`

---

## 2. Server Capability Detection Engine
At runtime, `ApexSEO\Core\Environment` inspects server environment variables without fatal errors:

```php
namespace ApexSEO\Core;

class Environment {
    public static function getServerSoftware(): string {
        $software = $_SERVER['SERVER_SOFTWARE'] ?? '';
        if (stripos($software, 'litespeed') !== false) return 'litespeed';
        if (stripos($software, 'openlitespeed') !== false) return 'openlitespeed';
        if (stripos($software, 'nginx') !== false) return 'nginx';
        if (stripos($software, 'apache') !== false) return 'apache';
        return 'generic';
    }

    public static function hasImagick(): bool {
        return extension_loaded('imagick') && class_exists('\Imagick');
    }

    public static function hasGD(): bool {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    public static function hasRedis(): bool {
        return extension_loaded('redis') && class_exists('\Redis');
    }

    public static function hasMemcached(): bool {
        return extension_loaded('memcached') && class_exists('\Memcached');
    }

    public static function hasOpcache(): bool {
        return extension_loaded('Zend OPcache') && function_exists('opcache_get_status');
    }
}
```

---

## 3. Server Feature Matrix

| Server Environment | Page Cache Strategy | WebP/AVIF Serving | Cache Purge Method | ESI Support |
|---|---|---|---|---|
| **LiteSpeed / OpenLiteSpeed** | Server Header (`X-LiteSpeed-Cache-Control`) | Direct Server Rewrite | Header Purge (`X-LiteSpeed-Purge`) | Supported natively |
| **Nginx (with FastCGI Cache)** | PHP Disk Cache or FastCGI Cache Helper | Nginx Config Rules | FastCGI Cache Key Purge / File Purge| Via AJAX fallback |
| **Apache (with mod_rewrite)** | PHP Disk Cache + `.htaccess` browser headers | `.htaccess` Direct Rewrite | File Invalidation | Via AJAX fallback |
| **Generic PHP / Docker / Cloud Run** | Application File Cache (`/wp-content/cache/`) | HTML Picture Tag Rewrite | File Unlink / Directory Purge | Via AJAX fallback |
