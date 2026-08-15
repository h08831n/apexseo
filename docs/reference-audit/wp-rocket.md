# WP Rocket Reference Audit

## 1. Source Inventory
- Repository: https://github.com/wp-media/wp-rocket
- Version Scope: WP Rocket Performance & Caching Engine Analysis

## 2. Architecture & Class Mapping
- **Page Cache**: Static HTML generation, file cache adapter, cache purging
- **Cache Preload**: Sitemap-based crawler, automatic cache warm-up
- **Minification & Asset Optimization**: CSS minification, JS minification, unused CSS removal (RUCSS), Critical CSS generation
- **JavaScript Execution Engine**: Defer JS, Delay JS until user interaction
- **Lazy Load**: Images, iframes, YouTube preview thumbnails, native loading attribute fallback
- **Database Optimization**: Revisions, auto-drafts, trashed posts, comments, transients, scheduled cleanup
- **CDN Integration**: CNAME rewriting, Cloudflare API sync

## 3. Key Feature Scope
1. Full Page Cache & Cache Warmup (Preload)
2. Remove Unused CSS (RUCSS) & Critical CSS
3. Delay JS Execution until touch/mousemove/scroll
4. Lazy Loading for Images, Video, Frames
5. Gzip / Brotli Compression & Browser Cache configuration (.htaccess/Nginx)
6. Google Fonts Optimization (Combine & Inline CSS)
7. Heartbeat Control API
