# 07 - Performance Subsystem Specification & WP Rocket Audit Matrix

## 1. Engine Overview
The Performance Subsystem replicates the functional capabilities of WP Rocket through a unified pipeline comprising CSS minification/inlining, JavaScript deferral and delayed execution, Google Fonts self-hosting, smart lazy loading, and database optimization.

---

## 2. Exhaustive WP Rocket Feature Parity Matrix (28 Verified Features)

| Feature ID | WP Rocket Feature Name | Source Module / File | Apex SEO Module | Implementation Strategy | Status |
|---|---|---|---|---|---|
| **WPR-01** | Static File Page Cache | `inc/Engine/Cache/FullPage.php` | `ApexSEO\Cache\Page` | Disk write to `/wp-content/cache/apex-page-cache/` | VERIFIED |
| **WPR-02** | Separate Mobile Cache | `inc/Engine/Cache/FullPage.php` | `ApexSEO\Cache\Page` | Separate cache hash per mobile `User-Agent` | VERIFIED |
| **WPR-03** | User Cache (Logged-In) | `inc/Engine/Cache/FullPage.php` | `ApexSEO\Cache\Page` | Per-user cache hash based on auth cookie | VERIFIED |
| **WPR-04** | Cache Lifespan (TTL) | `inc/Engine/Cache/AdminSubscriber.php` | `ApexSEO\Cache\Page` | Automated cron purge after N hours | VERIFIED |
| **WPR-05** | Cache Preload / Warmup | `inc/Engine/Cache/Warmup.php` | `ApexSEO\Performance\Preload` | XML sitemap parser with async `wp_remote_get` | VERIFIED |
| **WPR-06** | CSS Minification | `inc/Engine/Optimization/CSS/Minify.php` | `ApexSEO\Performance\CSS` | Pure PHP CSS tokenizer & whitespace stripper | VERIFIED |
| **WPR-07** | CSS Combination | `inc/Engine/Optimization/CSS/Combine.php` | `ApexSEO\Performance\CSS` | Bundles non-excluded stylesheets into single link | VERIFIED |
| **WPR-08** | Critical CSS Generation | `inc/Engine/Optimization/CSS/CriticalCSS/` | `ApexSEO\Performance\CSS` | Generates critical path CSS and inlines in `<head>` | VERIFIED |
| **WPR-09** | Remove Unused CSS (RUCSS)| `inc/Engine/Optimization/RUCSS/` | `ApexSEO\Performance\CSS` | AST token analyzer extracting used CSS rules | VERIFIED |
| **WPR-10** | JS Minification | `inc/Engine/Optimization/JS/Minify.php` | `ApexSEO\Performance\JavaScript`| Whitespace and comment stripper for inline/external JS | VERIFIED |
| **WPR-11** | JS Combination | `inc/Engine/Optimization/JS/Combine.php` | `ApexSEO\Performance\JavaScript`| Bundles non-excluded scripts into single bundle | VERIFIED |
| **WPR-12** | Load JS Deferred | `inc/Engine/Optimization/JS/Defer.php` | `ApexSEO\Performance\JavaScript`| Injects `defer` attribute into `<script>` tags | VERIFIED |
| **WPR-13** | Delay JS Execution | `inc/Engine/Optimization/JS/Delay.php` | `ApexSEO\Performance\JavaScript`| Delays scripts until `keydown`, `mousemove`, `touchstart`, `scroll` | VERIFIED |
| **WPR-14** | LazyLoad Images | `inc/Engine/Optimization/Lazyload/Images.php` | `ApexSEO\Performance\LazyLoad` | Native `loading="lazy"` + fallback JS with LQIP placeholder | VERIFIED |
| **WPR-15** | LazyLoad Iframes & Video | `inc/Engine/Optimization/Lazyload/Iframes.php`| `ApexSEO\Performance\LazyLoad` | Replaces YouTube/Vimeo embeds with preview thumbnails | VERIFIED |
| **WPR-16** | Exclude LCP Image | `inc/Engine/Optimization/Lazyload/` | `ApexSEO\Performance\LazyLoad` | Detects first image in content / featured image and skips lazy | VERIFIED |
| **WPR-17** | Google Fonts Optimization| `inc/Engine/Optimization/GoogleFonts/` | `ApexSEO\Performance\Fonts` | Combines multiple font families, inlines CSS, adds `font-display: swap` | VERIFIED |
| **WPR-18** | Preload Fonts | `inc/Engine/Optimization/ResourceHints/`| `ApexSEO\Performance\Fonts` | Injects `<link rel="preload" as="font" crossorigin>` | VERIFIED |
| **WPR-19** | DNS Prefetch / Preconnect| `inc/Engine/Optimization/ResourceHints/`| `ApexSEO\Performance\Preload` | Injects `<link rel="dns-prefetch">` and `preconnect` headers | VERIFIED |
| **WPR-20** | CDN URL Rewriting | `inc/Engine/CDN/` | `ApexSEO\CDN\Generic` | Rewrites `wp-content/` and `wp-includes/` URLs to CDN CNAME | VERIFIED |
| **WPR-21** | Cloudflare Integration | `inc/Engine/Addons/Cloudflare/` | `ApexSEO\CDN\Cloudflare` | Automatic Cloudflare API purge on post update | VERIFIED |
| **WPR-22** | Database Revisions Clean | `inc/Engine/Database/Optimization.php` | `ApexSEO\Database\Optimizer` | Deletes old post revisions past configured limit | VERIFIED |
| **WPR-23** | Auto-Drafts & Trash Clean| `inc/Engine/Database/Optimization.php` | `ApexSEO\Database\Optimizer` | Purges auto-drafts and trashed posts/pages | VERIFIED |
| **WPR-24** | Spam Comments Cleanup | `inc/Engine/Database/Optimization.php` | `ApexSEO\Database\Optimizer` | Purges spam and trashed comments | VERIFIED |
| **WPR-25** | Transients Cleanup | `inc/Engine/Database/Optimization.php` | `ApexSEO\Database\Optimizer` | Deletes expired transients from `wp_options` | VERIFIED |
| **WPR-26** | Heartbeat Control | `inc/Engine/Heartbeat/Heartbeat.php` | `ApexSEO\Performance\Heartbeat`| Reduces or disables WP Heartbeat API frequency | VERIFIED |
| **WPR-27** | Safe Mode & Rollback | `inc/Engine/Admin/SafeMode.php` | `ApexSEO\Performance\SafeMode` | One-click bypass of all asset rewriting for debugging | VERIFIED |
| **WPR-28** | Dynamic WooCommerce Excl | `inc/Engine/Cache/WooCommerce.php` | `ApexSEO\Integrations\WooCommerce`| Automatically excludes Cart, Checkout, and `?wc-ajax` from cache | VERIFIED |
