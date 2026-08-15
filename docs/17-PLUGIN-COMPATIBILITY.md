# 17 - Plugin Conflict Detection & Interoperability

## 1. Conflicting Plugin Detection
When activating Apex SEO, `ApexSEO\Admin\Conflict` checks active plugins to prevent duplicate tags, double-caching, and script conflicts:

| Detected Plugin | Conflicting Modules | Recommended Action |
|---|---|---|
| **Yoast SEO** | Meta Titles, Schema, Sitemaps, OpenGraph | Run Apex Migration, then Deactivate Yoast |
| **Rank Math** | Meta Titles, Schema, Redirections, 404, Analytics | Run Apex Migration, then Deactivate Rank Math |
| **All in One SEO** | Meta Titles, TruSEO, Sitemaps, Schema | Run Apex Migration, then Deactivate AIOSEO |
| **SEOPress** | Meta Titles, Breadcrumbs, Sitemaps | Run Apex Migration, then Deactivate SEOPress |
| **WP Rocket** | Page Cache, Delay JS, CSS Minify, LazyLoad | Disable WP Rocket Cache or Deactivate WP Rocket |
| **LiteSpeed Cache** | Page Cache, Image Optm, Object Cache | Switch to Apex LiteSpeed Adapter mode |
| **Autoptimize** | CSS/JS Minification, LazyLoad | Deactivate Autoptimize to avoid double minification |
| **Redirection** | 301 Redirects, 404 Logging | Run Apex Redirect Importer, then Deactivate |

---

## 2. Safe Coexistence Protocol
If an administrator intentionally keeps a specialized plugin active, Apex SEO provides module-level toggle switches allowing individual engines (e.g. Page Cache, Schema, Image Optimization) to be disabled independently without affecting other features.
