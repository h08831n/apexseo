# 08 - Cache Subsystem Specification & LiteSpeed Cache Matrix

## 1. Cache Abstraction Architecture
Apex SEO employs a modular cache driver pattern:

```
                          REQUEST PIPELINE
                                 │
                                 ▼
                     ┌───────────────────────┐
                     │ Cache Manager Router  │
                     └───────────┬───────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         ▼                       ▼                       ▼
┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐
│  LiteSpeed/OLS   │   │  Nginx FastCGI   │   │ Application Disk │
│  Native Headers  │   │  Cache Adapter   │   │ Static PageCache │
│  (X-LS-Tag/ESI)  │   │  (PURGE Hook)    │   │  (Default/Safe)  │
└──────────────────┘   └──────────────────┘   └──────────────────┘
```

---

## 2. Exhaustive LiteSpeed Cache Feature Parity Matrix (24 Verified Features)

| Feature ID | LiteSpeed Feature Name | Source Module / File | Apex SEO Module | Implementation Strategy | Status |
|---|---|---|---|---|---|
| **LSC-01** | Server-Level LSCache | `src/cache.cls.php` | `ApexSEO\Server\LiteSpeed` | Emits `X-LiteSpeed-Cache-Control: public,no-vary,max-age=604800` | VERIFIED |
| **LSC-02** | Cache Tagging System | `src/cache.cls.php` | `ApexSEO\Server\LiteSpeed` | Emits `X-LiteSpeed-Tag: post_123, taxonomy_45, home` | VERIFIED |
| **LSC-03** | Smart Targeted Purge | `src/purge.cls.php` | `ApexSEO\Server\LiteSpeed` | Emits `X-LiteSpeed-Purge: tag_name` on content update | VERIFIED |
| **LSC-04** | ESI (Edge Side Includes) | `src/esi.cls.php` | `ApexSEO\Server\LiteSpeed` | Hole-punches dynamic widgets/cart fragments `<esi:include>` | VERIFIED |
| **LSC-05** | Mobile Cache Vary | `src/vary.cls.php` | `ApexSEO\Server\LiteSpeed` | Emits `X-LiteSpeed-Vary: is_mobile` | VERIFIED |
| **LSC-06** | Guest Mode & Guest Optm | `src/guest.cls.php` | `ApexSEO\Cache\Page` | Serves ultra-fast cached landing page before cookies evaluate | VERIFIED |
| **LSC-07** | Object Cache (Redis) | `src/object.cls.php` | `ApexSEO\Cache\Redis` | Connects via `phpredis` extension / TCP / Unix Socket | VERIFIED |
| **LSC-08** | Object Cache (Memcached)| `src/object.cls.php` | `ApexSEO\Cache\Memcached`| Connects via `Memcached` extension | VERIFIED |
| **LSC-09** | Object Cache Flush | `src/object.cls.php` | `ApexSEO\Cache\Redis` | Granular cache flush and group invalidation | VERIFIED |
| **LSC-10** | Database Table Optm | `src/db-optm.cls.php` | `ApexSEO\Database\Optimizer` | Performs `OPTIMIZE TABLE` on fragmented InnoDB/MyISAM tables | VERIFIED |
| **LSC-11** | Autoload Options Monitor | `src/db-optm.cls.php` | `ApexSEO\Database\Optimizer` | Identifies autoloaded rows exceeding size threshold (e.g. >100KB) | VERIFIED |
| **LSC-12** | WebP Image Generation | `src/media.cls.php` | `ApexSEO\Media\WebP` | Local GD/Imagick fallback conversion | VERIFIED |
| **LSC-13** | AVIF Image Generation | `src/media.cls.php` | `ApexSEO\Media\AVIF` | Local libavif conversion | VERIFIED |
| **LSC-14** | Image Optimization Queue| `src/media.cls.php` | `ApexSEO\Media\Queue` | Asynchronous task queue with retry logic | VERIFIED |
| **LSC-15** | Crawler / Preload | `src/crawler.cls.php` | `ApexSEO\Performance\Preload` | Multi-threaded sitemap crawler with cookie vary support | VERIFIED |
| **LSC-16** | QUIC.cloud CDN Sync | `src/cdn.cls.php` | `ApexSEO\CDN\Generic` | REST sync and cache purge integration | VERIFIED |
| **LSC-17** | Heartbeat Control | `src/admin.cls.php` | `ApexSEO\Performance\Heartbeat`| Sets heartbeat interval for frontend, backend, editor | VERIFIED |
| **LSC-18** | Browser Cache (.htaccess)| `src/htaccess.cls.php` | `ApexSEO\Server\Apache` | Injects `ExpiresByType` and `Cache-Control` headers | VERIFIED |
| **LSC-19** | Gzip / Brotli Directives| `src/htaccess.cls.php` | `ApexSEO\Server\Apache` | Injects `mod_deflate` / `mod_brotli` directives | VERIFIED |
| **LSC-20** | Lazy Load Responsive Images| `src/media.cls.php` | `ApexSEO\Performance\LazyLoad`| Native loading + SVG inline placeholder | VERIFIED |
| **LSC-21** | REST API Cache | `src/rest.cls.php` | `ApexSEO\API\REST` | Caches read-only public REST responses (`/wp/v2/posts`) | VERIFIED |
| **LSC-22** | WooCommerce ESI Blocks | `src/esi.cls.php` | `ApexSEO\Integrations\WooCommerce`| Dynamic cart subtotal & mini-cart hole-punching | VERIFIED |
| **LSC-23** | User Agent Exclusions | `src/cache.cls.php` | `ApexSEO\Cache\Page` | Excludes search engine bots from certain dynamic caches | VERIFIED |
| **LSC-24** | Diagnostic Report & Log | `src/report.cls.php` | `ApexSEO\Admin\Diagnostics` | Generates diagnostic environment report | VERIFIED |
