# LiteSpeed Cache Reference Audit

## 1. Source Inventory
- Repository: https://github.com/litespeedtech/lscache_wp
- Version Scope: LiteSpeed Cache for WordPress Analysis

## 2. Architecture & Class Mapping
- **LSCache Core**: `Litespeed\Cache`, `Litespeed\Purge`, `Litespeed\Vary`
- **ESI (Edge Side Includes)**: Dynamic caching fragments for logged-in users / WooCommerce
- **Image Optimization Engine**: WebP/AVIF compression, QUIC.cloud API sync, local GD/Imagick fallback
- **Crawler**: Multi-threaded page cache crawler with cookie vary support
- **Object Cache**: Redis / Memcached object cache wrapper
- **Database Cleaner**: Autoload options monitor, table optimization

## 3. Key Feature Scope
1. Server-level LSCache integration & Cache Tagging
2. ESI (Edge Side Includes) support for localized dynamic blocks
3. WebP / AVIF Next-Gen Image Conversion
4. Object Cache (Redis & Memcached) integration
5. QUIC.cloud CDN integration
6. Automatic Image Optimization Queue
7. Database Table Optimization & Autoload Analysis
