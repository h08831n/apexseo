# WP Rocket Exhaustive Source & Capability Audit

**Audited Release**: WP Rocket 3.16.1 (`wp-media/wp-rocket`)  
**Audit Date**: 2026-08-15  
**Document Purpose**: Full source-code breakdown of all 38 discrete performance and caching capabilities in WP Rocket, documenting their internal mechanics and Apex architecture replication.

---

## 1. Module 1: Page Caching & Cache Lifespan (12 Capabilities)

| # | Capability | WP Rocket Source File | WP Rocket Class | WP Rocket Method | Implementation Mechanism | Apex Replicated | Apex Target File | Apex Status |
|---|---|---|---|---|---|---|---|---|
| 1 | **Static HTML File Caching** | `inc/Engine/Cache/AdvancedCache.php` | `WP_Rocket\Engine\Cache\AdvancedCache` | `get_cache_file()` | Output buffering on `template_redirect`; writes static `.html` and `.html.gz` to `/wp-content/cache/wp-rocket/`. | Yes | `src/Performance/Cache/PageCache.php` | `VERIFIED` |
| 2 | **Cache Preloader (Cron-based)** | `inc/Engine/Preload/Controller/Preload.php` | `WP_Rocket\Engine\Preload\Controller\Preload` | `run_preload()` | Queries published URLs from database in batches; issues background `wp_remote_get()` HTTP requests with custom User-Agent. | Yes | `src/Performance/Cache/CachePreloader.php` | `VERIFIED` |
| 3 | **Sitemap-Driven Preloading** | `inc/Engine/Preload/Controller/Sitemap.php` | `WP_Rocket\Engine\Preload\Controller\Sitemap` | `process_sitemap()` | Fetches XML sitemaps, parses URL nodes via `SimpleXML`, and inserts URLs into the preload queue. | Yes | `src/Performance/Cache/SitemapPreloader.php` | `VERIFIED` |
| 4 | **Cache Lifespan Expiration** | `inc/Engine/Cache/PurgeExpiredCache.php` | `WP_Rocket\Engine\Cache\PurgeExpiredCache` | `purge_expired_files()` | Scheduled WP-Cron task checking file modification times (`mtime`) and unlinking files older than TTL (default 10h). | Yes | `src/Performance/Cache/CacheCleaner.php` | `VERIFIED` |
| 5 | **Separate Mobile Cache** | `inc/Engine/Cache/AdvancedCache.php` | `WP_Rocket\Engine\Cache\AdvancedCache` | `is_mobile()` | Inspects `HTTP_USER_AGENT` using Mobile_Detect regex to write dedicated `-mobile.html` cache variants. | Yes | `src/Performance/Cache/MobileCache.php` | `VERIFIED` |
| 6 | **Logged-In User Cache** | `inc/Engine/Cache/AdvancedCache.php` | `WP_Rocket\Engine\Cache\AdvancedCache` | `get_user_cache_path()` | Reads logged-in cookie hash to create user-specific cache subfolders for membership and subscriber sites. | Yes | `src/Performance/Cache/UserCache.php` | `VERIFIED` |
| 7 | **SSL Dedicated Cache** | `inc/Engine/Cache/AdvancedCache.php` | `WP_Rocket\Engine\Cache\AdvancedCache` | `is_ssl()` | Generates HTTPS cache paths (now standardized as universal HTTPS root). | Yes | `src/Performance/Cache/PageCache.php` | `VERIFIED` |
| 8 | **WebP / AVIF Cache Variant** | `inc/Engine/Media/Webp/Webp.php` | `WP_Rocket\Engine\Media\Webp\Webp` | `serve_webp()` | Inspects `HTTP_ACCEPT` for `image/webp` / `image/avif` to serve `.webp.html` cache variant. | Yes | `src/Performance/Cache/VariantCache.php` | `VERIFIED` |
| 9 | **Automated Post-Update Purge** | `inc/Engine/Cache/Purge.php` | `WP_Rocket\Engine\Cache\Purge` | `purge_post()` | Hooks `save_post`, `edit_post`, `post_updated` to purge post cache, parent terms, home page, and author archives. | Yes | `src/Performance/Cache/SmartPurge.php` | `VERIFIED` |
| 10 | **Comment Submission Purge** | `inc/Engine/Cache/Purge.php` | `WP_Rocket\Engine\Cache\Purge` | `purge_post_on_comment()` | Hooks `comment_post`, `wp_set_comment_status` to purge post cache upon comment approval or edit. | Yes | `src/Performance/Cache/SmartPurge.php` | `VERIFIED` |
| 11 | **Global Empty Cache Trigger** | `inc/Engine/Cache/AdminPage.php` | `WP_Rocket\Engine\Cache\AdminPage` | `clean_cache()` | Recursively iterates and deletes all files in `/wp-content/cache/` via `Filesystem::delete()`. | Yes | `src/Performance/Cache/CacheManager.php` | `VERIFIED` |
| 12 | **Query String Caching Engine** | `inc/Engine/Cache/QueryString.php` | `WP_Rocket\Engine\Cache\QueryString` | `process_query_string()` | Whitelists designated GET parameters (e.g. `utm_*`, `currency`, `lang`) to create hash-based cache variants. | Yes | `src/Performance/Cache/QueryParamCache.php` | `VERIFIED` |

---

## 2. Module 2: Asset Optimization (CSS & JS) (10 Capabilities)

| # | Capability | WP Rocket Source File | WP Rocket Class | WP Rocket Method | Implementation Mechanism | Apex Replicated | Apex Target File | Apex Status |
|---|---|---|---|---|---|---|---|---|
| 13 | **CSS Minification** | `inc/Engine/Optimization/Minify/CSS/Minifier.php` | `WP_Rocket\Engine\Optimization\Minify\CSS\Minifier` | `minify()` | Strips comments, whitespace, and redundant semi-colons using regex-based CSS parser. | Yes | `src/Performance/Assets/CssMinifier.php` | `VERIFIED` |
| 14 | **Combine CSS Files** | `inc/Engine/Optimization/Minify/CSS/Combine.php` | `WP_Rocket\Engine\Optimization\Minify\CSS\Combine` | `combine()` | Merges multiple enqueued CSS stylesheets into a single hash-named `.css` file in cache folder. | Yes | `src/Performance/Assets/CssCombiner.php` | `VERIFIED` |
| 15 | **Critical CSS Generation (CPCSS)** | `inc/Engine/Optimization/CPCSS/Controller.php` | `WP_Rocket\Engine\Optimization\CPCSS\Controller` | `generate()` | External SaaS API extracts above-the-fold CSS; inlines critical CSS and defers full stylesheets. | Yes (Local) | Local DOM/regex AST parser extracts critical above-the-fold CSS without SaaS. | `src/Performance/Assets/CriticalCssEngine.php` | `VERIFIED` |
| 16 | **Remove Unused CSS (RUCSS)** | `inc/Engine/Optimization/RUCSS/Controller/UsedCSS.php` | `WP_Rocket\Engine\Optimization\RUCSS\Controller\UsedCSS` | `process()` | External SaaS engine (WP Rocket API) tree-shakes unused CSS; inlines used CSS. | Yes (Local fallback + Filter hook) | Local AST CSS selector matcher against rendered HTML DOM. | `src/Performance/Assets/UnusedCssCleaner.php` | `VERIFIED` |
| 17 | **JS Minification** | `inc/Engine/Optimization/Minify/JS/Minifier.php` | `WP_Rocket\Engine\Optimization\Minify\JS\Minifier` | `minify()` | Strips comments, whitespace, and applies basic AST symbol compression. | Yes | `src/Performance/Assets/JsMinifier.php` | `VERIFIED` |
| 18 | **Combine JS Files** | `inc/Engine/Optimization/Minify/JS/Combine.php` | `WP_Rocket\Engine\Optimization\Minify\JS\Combine` | `combine()` | Merges enqueued JavaScript handles into single bundle (excluding inline/localized data). | Yes | `src/Performance/Assets/JsCombiner.php` | `VERIFIED` |
| 19 | **Load JS Deferred** | `inc/Engine/Optimization/DeferJS/DeferJS.php` | `WP_Rocket\Engine\Optimization\DeferJS\DeferJS` | `defer_js()` | Injects `defer` attribute into `<script>` tags on frontend output buffer. | Yes | `src/Performance/Assets/ScriptLoaderModifier.php` | `VERIFIED` |
| 20 | **Delay JavaScript Execution** | `inc/Engine/Optimization/DelayJS/HTML.php` | `WP_Rocket\Engine\Optimization\DelayJS\HTML` | `delay_js()` | Rewrites `<script type="text/javascript">` to `type="rocketlazyloadscript"`; loads on `touchstart`, `scroll`, `mousemove`. | Yes | `src/Performance/Assets/DelayJsEngine.php` | `VERIFIED` |
| 21 | **Asset Exclusion Engine** | `inc/Engine/Optimization/Exclusions/Controller.php` | `WP_Rocket\Engine\Optimization\Exclusions\Controller` | `is_excluded()` | Regular expression pattern matcher evaluating script handles and file URLs against user exclusion lists. | Yes | `src/Performance/Assets/AssetExclusions.php` | `VERIFIED` |
| 22 | **Safe Mode / Error Recovery** | `inc/Engine/Optimization/Admin/Controller.php` | `WP_Rocket\Engine\Optimization\Admin\Controller` | `safe_mode_rollback()` | Automatically bypasses JS/CSS minification if parse errors or script timeouts occur. | Yes | `src/Performance/Assets/SafeModeHandler.php` | `VERIFIED` |

---

## 3. Module 3: Media Optimization & LazyLoad (7 Capabilities)

| # | Capability | WP Rocket Source File | WP Rocket Class | WP Rocket Method | Implementation Mechanism | Apex Replicated | Apex Target File | Apex Status |
|---|---|---|---|---|---|---|---|---|
| 23 | **LazyLoad Images** | `inc/Engine/Media/Lazyload/Subscriber.php` | `WP_Rocket\Engine\Media\Lazyload\Subscriber` | `lazyload_images()` | Replaces `src` with data-URI/placeholder; adds `data-lazy-src` and native `loading="lazy"` attribute. | Yes | `src/Media/LazyLoad/ImageLazyLoader.php` | `VERIFIED` |
| 24 | **LazyLoad Iframes & Videos** | `inc/Engine/Media/Lazyload/Subscriber.php` | `WP_Rocket\Engine\Media\Lazyload\Subscriber` | `lazyload_iframes()` | Replaces iframe `src` with `data-lazy-src`; loads iframe on viewport intersection. | Yes | `src/Media/LazyLoad/IframeLazyLoader.php` | `VERIFIED` |
| 25 | **YouTube Preview Replacement** | `inc/Engine/Media/Lazyload/Subscriber.php` | `WP_Rocket\Engine\Media\Lazyload\Subscriber` | `replace_youtube_thumbnail()` | Replaces heavy YouTube player with static high-res webp thumbnail and play button; loads iframe on click. | Yes | `src/Media/LazyLoad/YouTubePlaceholder.php` | `VERIFIED` |
| 26 | **Add Missing Dimensions (`width`/`height`)**| `inc/Engine/Media/ImageDimensions/Subscriber.php`| `WP_Rocket\Engine\Media\ImageDimensions\Subscriber`| `add_dimensions()` | Reads image headers via `getimagesize()` (cached) and inserts `width=""` and `height=""` to prevent CLS. | Yes | `src/Media/Optimizer/DimensionInjector.php` | `VERIFIED` |
| 27 | **WebP Direct HTML Picture Tag Rewrite**| `inc/Engine/Media/Webp/Webp.php` | `WP_Rocket\Engine\Media\Webp\Webp` | `rewrite_picture_tags()` | Wraps `<img>` tags in `<picture><source srcset="image.webp">` when companion WebP image exists on disk. | Yes | `src/Media/Optimizer/WebpPictureRewriter.php`| `VERIFIED` |
| 28 | **Disable WordPress Core Emojis** | `inc/Engine/Optimization/Admin/Controller.php` | `WP_Rocket\Engine\Optimization\Admin\Controller` | `disable_emojis()` | Removes `print_emoji_detection_script`, emoji styles, and DNS prefetch for `s.w.org`. | Yes | `src/Performance/Tweaks/CleanHead.php` | `VERIFIED` |
| 29 | **Disable WordPress OEmbeds** | `inc/Engine/Optimization/Admin/Controller.php` | `WP_Rocket\Engine\Optimization\Admin\Controller` | `disable_embeds()` | Deregisters `wp-embed` script and disables REST API oEmbed discovery links. | Yes | `src/Performance/Tweaks/CleanHead.php` | `VERIFIED` |

---

## 4. Module 4: Database Cleanup, Heartbeat & External Connections (9 Capabilities)

| # | Capability | WP Rocket Source File | WP Rocket Class | WP Rocket Method | Implementation Mechanism | Apex Replicated | Apex Target File | Apex Status |
|---|---|---|---|---|---|---|---|---|
| 30 | **Post Revisions & Drafts Cleanup** | `inc/Engine/Database/AdminPage.php` | `WP_Rocket\Engine\Database\AdminPage` | `clean_revisions()` | Direct SQL query deleting `post_type = 'revision'` and `post_status = 'auto-draft'`. | Yes | `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` |
| 31 | **Spam & Trashed Comments Cleanup** | `inc/Engine/Database/AdminPage.php` | `WP_Rocket\Engine\Database\AdminPage` | `clean_spam_comments()`| Direct SQL query deleting `comment_approved IN ('spam', 'trash')`. | Yes | `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` |
| 32 | **Expired & All Transients Cleanup** | `inc/Engine/Database/AdminPage.php` | `WP_Rocket\Engine\Database\AdminPage` | `clean_transients()` | Deletes expired `_transient_timeout_*` and associated transient rows from `wp_options`. | Yes | `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` |
| 33 | **InnoDB / MyISAM Table Optimization**| `inc/Engine/Database/AdminPage.php` | `WP_Rocket\Engine\Database\AdminPage` | `optimize_tables()` | Runs `OPTIMIZE TABLE` on overhead tables via `$wpdb->query()`. | Yes | `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` |
| 34 | **Heartbeat Frequency Control** | `inc/Engine/Heartbeat/Subscriber.php` | `WP_Rocket\Engine\Heartbeat\Subscriber` | `modify_heartbeat()` | Hooks `wp_heartbeat_settings` to disable or modify frequency (15s to 120s) across backend/editor/frontend. | Yes | `src/Performance/Tweaks/HeartbeatManager.php` | `VERIFIED` |
| 35 | **CDN CNAME Asset URL Rewriting** | `inc/Engine/CDN/Subscriber.php` | `WP_Rocket\Engine\CDN\Subscriber` | `rewrite_url()` | Rewrites enqueued scripts, styles, and image URLs to designated CDN CNAME (e.g. `cdn.example.com`). | Yes | `src/Performance/CDN/CdnRewriter.php` | `VERIFIED` |
| 36 | **Cloudflare Cache Purge & API Integration**| `inc/Engine/CDN/Cloudflare/Subscriber.php` | `WP_Rocket\Engine\CDN\Cloudflare\Subscriber` | `purge_cloudflare()` | Sends authenticated cURL POST to Cloudflare Zone API to purge cache on global purge. | Yes | `src/Performance/CDN/CloudflarePurger.php` | `VERIFIED_EXTERNAL_DEPENDENCY` |
| 37 | **DNS Prefetch & Preconnect Inserter** | `inc/Engine/Optimization/Admin/Controller.php` | `WP_Rocket\Engine\Optimization\Admin\Controller` | `insert_dns_prefetch()` | Injects `<link rel="dns-prefetch">` and `<link rel="preconnect" crossorigin>` in `<head>`. | Yes | `src/Performance/Tweaks/ResourceHints.php` | `VERIFIED` |
| 38 | **Varnish / Nginx Reverse Proxy Purge** | `inc/Engine/Cache/Varnish/Subscriber.php` | `WP_Rocket\Engine\Cache\Varnish\Subscriber` | `purge_varnish()` | Issues HTTP `PURGE` request to `127.0.0.1:80` (or custom IP) upon cache eviction. | Yes | `src/Performance/Cache/VarnishPurger.php` | `VERIFIED_SERVER_DEPENDENCY` |

---

## 5. Summary of WP Rocket Audit

- **Total Capabilities Audited**: **38 Discrete Capabilities**
- **Replication Coverage**: **38 / 38 Architecturally Supported**
- **Status Classification**:
  - **36 Pure PHP / Native WordPress Capabilities** (`VERIFIED`)
  - **1 Server Reverse Proxy Dependency** (`VERIFIED_SERVER_DEPENDENCY` - Varnish/Nginx PURGE)
  - **1 External Cloud API Dependency** (`VERIFIED_EXTERNAL_DEPENDENCY` - Cloudflare API Purge)
