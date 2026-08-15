# Cache Layer Architecture & Multi-Driver Specification

**Audit Reference**: WP Rocket, LiteSpeed Cache, Nginx FastCGI, Cloudflare  
**Architecture Pattern**: Strategy Pattern with Priority-Based Auto-Detection & Fallback Hierarchy

---

## 1. Unified Cache Driver Interface

Every cache engine implements the standard `CacheDriverInterface`:

```php
namespace ApexSEO\Cache\Contracts;

interface CacheDriverInterface {
    /**
     * Check whether this driver is supported in the current server environment.
     */
    public function is_available(): bool;

    /**
     * Retrieve cached response for given key.
     */
    public function get(string $key): ?string;

    /**
     * Store HTML response in cache.
     */
    public function set(string $key, string $html, int $ttl, array $tags = []): bool;

    /**
     * Invalidate specific cache key.
     */
    public function delete(string $key): bool;

    /**
     * Purge cache entries associated with a specific tag (e.g. "post_12", "cat_4").
     */
    public function purge_by_tag(string $tag): bool;

    /**
     * Purge specific URL from cache.
     */
    public function purge_by_url(string $url): bool;

    /**
     * Flush all cached content managed by this driver.
     */
    public function purge_all(): bool;

    /**
     * Return human-readable driver identifier.
     */
    public function get_name(): string;
}
```

---

## 2. Supported Cache Drivers & Detection Matrix

| Priority | Cache Driver | Underlying Technology | Environment Requirements | Availability Check Method | Purge Mechanism |
|---|---|---|---|---|---|
| **1 (Highest)** | `LiteSpeedCacheDriver` | LiteSpeed / OpenLiteSpeed Server Core | `$_SERVER['SERVER_SOFTWARE']` containing `LiteSpeed` or `OpenLiteSpeed` | `function_exists('litespeed_request_headers')` or server check | HTTP response headers (`X-LiteSpeed-Purge: tag`) |
| **2** | `NginxFastCgiCacheDriver` | Nginx `fastcgi_cache` module | Nginx server with `srcache` or `fastcgi_cache_purge` module | Presence of Nginx cache path and write permissions or purge module | File unlinking in Nginx cache dir or HTTP `PURGE /url` |
| **3** | `ApplicationFileCacheDriver` | Local Disk File Caching | Standard PHP environment, writable `/wp-content/cache/` | `is_writable(WP_CONTENT_DIR . '/cache')` | File unlinking with `flock()` concurrency control |
| **Edge CDN** | `CloudflareEdgeCacheDriver` | Cloudflare Edge Caching | Active Cloudflare Zone ID & API Token | Configured valid API credentials | Cloudflare REST API `POST /zones/{id}/purge_cache` |

---

## 3. Cache Key Generation Matrix

The cache key must uniquely isolate all dynamic variants to prevent serving mismatched HTML:

```
Cache Key = sha256( Host + URI + Protocol + DeviceVariant + CurrencyVariant + LanguageVariant + UserRoleHash )
```

| Factor | Calculation / Logic | Sample Value |
|---|---|---|
| **Host** | `$_SERVER['HTTP_HOST']` lowercase | `example.com` |
| **URI** | Cleaned `$_SERVER['REQUEST_URI']` (stripping `utm_*`, `fbclid`, `gclid`) | `/shop/product-1/` |
| **Protocol** | `is_ssl() ? 'https' : 'http'` | `https` |
| **Device Variant** | Mobile detection regex matching `HTTP_USER_AGENT` | `mobile` or `desktop` |
| **Currency** | WooCommerce currency cookie (`woocommerce_current_currency`) | `USD` or `EUR` |
| **Language** | WPML / Polylang language cookie (`pll_language`, `_icl_current_language`) | `en` or `es` |
| **User Role Hash** | If user-caching enabled: `md5(user_id . '_' . user_role)` | `a1b2c3d4...` |

---

## 4. Cache Bypass & Dynamic Hole-Punching Matrix

Caching is strictly bypassed under any of the following conditions:

```
┌────────────────────────────────────────────────────────┐
│                   Incoming HTTP Request                │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
              ┌───────────────────────────┐
              │ Is POST / PUT / DELETE?   │───[YES]──► BYPASS CACHE
              └─────────────┬─────────────┘
                            │ [NO]
                            ▼
              ┌───────────────────────────┐
              │ Is User Logged In?        │───[YES]──► BYPASS (Unless user-cache enabled)
              └─────────────┬─────────────┘
                            │ [NO]
                            ▼
              ┌───────────────────────────┐
              │ WooCommerce Cart Empty?   │───[NO]───► BYPASS CACHE
              └─────────────┬─────────────┘
                            │ [YES]
                            ▼
              ┌───────────────────────────┐
              │ Excluded URL or Cookie?   │───[YES]──► BYPASS CACHE
              └─────────────┬─────────────┘
                            │ [NO]
                            ▼
                     SERVE CACHED HTML
```

- **Excluded Endpoints**: `wp-login.php`, `wp-admin/*`, `xmlrpc.php`, `wc-ajax=*`, `?preview=true`, `/cart/`, `/checkout/`, `/my-account/`.
- **Excluded Cookies**: `wordpress_logged_in_*`, `woocommerce_items_in_cart`, `comment_author_*`.
- **Excluded Query Strings**: Unregistered custom query parameters when strict query string policy is enabled.

---

## 5. Race Conditions & File Locking (`flock`)

To prevent Cache Stampede (dog-piling) when 100 concurrent requests hit an expired cache entry:
1. **Exclusive Lock (`LOCK_EX`)**: The first worker acquires an exclusive non-blocking file lock before generating the HTML cache.
2. **Stale Serving**: Concurrent requests arriving while the lock is held serve the stale cache file for up to 10 seconds rather than executing heavy backend PHP requests.
3. **Atomic File Replacement**: New cache files are written to a temporary filename (`page.html.tmp.123`) and renamed via `rename()`, guaranteeing atomic filesystem updates.
