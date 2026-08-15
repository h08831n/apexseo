# Authoritative Compatibility Matrix & Runtime Specifications

**Audit Lock Date**: 2026-08-15  
**Document Purpose**: Comprehensive compatibility matrix defining supported runtimes, language syntax rules, polyfills, database engines, and web servers across PHP 7.4 – 8.4 and WP 6.2 – 6.7.

---

## 1. PHP Runtime Compatibility (PHP 7.4 – 8.4)

| PHP Version | Compatibility Status | Syntax Restrictions & Handling | Required Testing Target |
|---|---|---|---|
| **PHP 7.4** | Supported (Base Minimum) | Strictly adhere to PHP 7.4 syntax: typed properties, `fn() =>`, `??`, spread operator. **No** union types (`int\|string`), constructor promotion, or `match` expressions. | PHPUnit 9.6 / GitHub Actions CI |
| **PHP 8.0** | Supported | Verified zero warnings on named arguments and stricter type coercions. | PHPUnit 9.6 |
| **PHP 8.1** | Supported | Fully compatible; avoids calling `strlen(null)` or passing null to non-nullable internal functions. | PHPUnit 10 |
| **PHP 8.2** | Supported | All dynamic object properties explicitly declared or annotated with `#[AllowDynamicProperties]`. Deprecated `utf8_encode()` replaced with `mb_convert_encoding()`. | PHPUnit 10 |
| **PHP 8.3** | Supported | Validated against typed class constants and stricter `unserialize()` error handling. | PHPUnit 11 |
| **PHP 8.4** | Supported | Verified against updated DOM extension changes and parameter nullability deprecations. | PHPUnit 11 / Nightly |

---

## 2. WordPress Core Compatibility (WP 6.2 – 6.7)

| WordPress Version | Compatibility Status | Core APIs Utilized | Compatibility Fallback Strategy |
|---|---|---|---|
| **WP 6.2** | Supported (Base Minimum) | Classic Editor APIs, Gutenberg Block Hooks, WP_Query, REST API v2, transients. | Baseline standard compatibility. |
| **WP 6.3** | Supported | Core Web Vitals fetchpriority attributes, image lazyload filters. | Conditional check `function_exists('wp_img_tag_add_fetchpriority_attr')`. |
| **WP 6.4** | Supported | Block Hooks API, Attachment page redirection settings. | Detects block hooks and integrates schema block annotations. |
| **WP 6.5** | Supported | Font Library API, Block Bindings API, AVIF image upload support. | Intercepts Font Library for local hosting; natively integrates AVIF generation. |
| **WP 6.6** | Supported | Block theme unified color palette and script module overrides. | Ensures admin UI theme consistency across full-site editing. |
| **WP 6.7** | Supported | Speculative Loading API / Interactivity API enhancements. | Natively integrates with `wp_enqueue_speculation_rules()` for instant navigation. |

---

## 3. Database Engine Compatibility (MySQL & MariaDB)

| Database Engine | Minimum Version | Index Key Rules | Collation & Storage Engine |
|---|---|---|---|
| **MySQL** | `5.7.0+` | Varchar indices capped at `VARCHAR(191)` for compatibility with `utf8mb4` 767-byte prefix limit on older InnoDB engines. | `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci` (gracefully falls back to `utf8mb4_unicode_ci` if unsupported). |
| **MySQL** | `8.0 / 8.4 LTS` | Full native JSON operators and spatial indices supported. | Native `utf8mb4_0900_ai_ci` / `utf8mb4_unicode_520_ci`. |
| **MariaDB** | `10.3+` | Full InnoDB compliance and table optimization support. | Standard `InnoDB` / `utf8mb4`. |
| **MariaDB** | `10.6 / 11.4 LTS` | High-performance atomic indexing and table operations. | Standard `InnoDB` / `utf8mb4`. |

---

## 4. Web Server Environment Compatibility

| Web Server | Supported Features | Generated Directives & Output |
|---|---|---|
| **Apache 2.4+** | Static HTML file serving, Gzip/Brotli delivery, Browser Expiry headers, Redirect execution. | Writes rules to `.htaccess` using `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate`. |
| **Nginx 1.18+** | Microcaching, FastCGI caching, direct static file serving (`try_files $uri $uri/ /wp-content/cache/...`). | Provides production-ready copy-paste Nginx configuration snippet in Admin Tools. |
| **LiteSpeed / OLS** | LSCache response headers, ESI tags, `.htaccess` direct cache reads. | Emits `X-LiteSpeed-Cache-Control` and `.htaccess` rewrite rules. |
| **Caddy 2.x** | Static cache file serving and header injection. | Provides Caddyfile configuration directive documentation. |
| **IIS 10+** | FastCGI static cache serving. | Generates `web.config` URL Rewrite rules for Windows Server environments. |
