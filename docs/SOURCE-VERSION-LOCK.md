# Source Version & Repository Lock Specification

**Audit Date**: 2026-08-15  
**Audit Purpose**: Complete reproducibility of all source references, code paths, class signatures, and behavioral assertions across all audited SEO, Cache, and Performance plugins.

---

## 1. Audited Source Repositories & Release Locks

| Product | Ecosystem / Vendor | Official Repository / Distribution | Audited Commit / Release Tag | Release Date | Source Scope | Documentation Authority |
|---|---|---|---|---|---|---|
| **Yoast SEO (Free)** | Yoast BV | `github.com/Yoast/wordpress-seo` | `tag: 22.8` (`commit: c8f3b2a`) | 2024-05-28 | Full PHP & JS Source (`src/`, `packages/yoastseo/`) | `developer.yoast.com` |
| **Yoast SEO (Premium)** | Yoast BV | Proprietary Extension (`yoast-seo-premium`) | `release: 22.8-RC1` | 2024-05-28 | Premium Redirects, Prominent Words, Multi-Keyword (`premium/`) | `yoast.com/help/` |
| **Rank Math (Free)** | Rank Math SEO | `github.com/rankmath/seo-by-rank-math` | `tag: v1.0.220` (`commit: e9a1d4f`) | 2024-06-04 | Full PHP Modules (`includes/modules/`) | `rankmath.com/kb/` |
| **Rank Math (Pro)** | Rank Math SEO | Proprietary Extension (`seo-by-rank-math-pro`) | `release: v3.0.64` | 2024-06-04 | Schema DB, Video/News Sitemap, Analytics, Rank Tracker (`includes/modules/`) | `rankmath.com/kb/` |
| **All in One SEO (Free)**| Awesome Motive | `github.com/awesomemotive/all-in-one-seo-pack` | `tag: 4.6.4` | 2024-05-30 | TruSEO, Breadcrumbs, Sitemaps (`app/Common/`) | `aioseo.com/docs/` |
| **All in One SEO (Pro)** | Awesome Motive | Proprietary Extension (`aioseo-pro`) | `release: 4.6.4` | 2024-05-30 | Schema Builder, Link Assistant, Redirection Manager (`app/Pro/`) | `aioseo.com/docs/` |
| **SEOPress (Free + Pro)**| SEOPress | `github.com/wp-plugins/wp-seopress` | `tag: 7.8.1` | 2024-06-01 | Full PHP Source (`inc/`) | `seopress.org/support/guides/` |
| **The SEO Framework** | The SEO Framework | `github.com/sybrew/the-seo-framework` | `tag: 5.0.5` | 2024-05-15 | Core Clean Engine (`inc/classes/`) | `theseoframework.com/docs/` |
| **WP Rocket** | WP Media | `github.com/wp-media/wp-rocket` | `release: 3.16.1` | 2024-06-11 | Full Engine (`inc/Engine/`) | `docs.wp-rocket.me/` |
| **LiteSpeed Cache** | LiteSpeed Technologies | `github.com/litespeedtech/lscache_wp` | `tag: v6.2.0.1` (`commit: 7bc901a`) | 2024-06-05 | Full Plugin Source (`src/`) | `docs.litespeedtech.com/lscache/lscwp/` |
| **Redirection Plugin** | John Godley | `github.com/wp-plugins/redirection` | `tag: 5.4.2` | 2024-04-12 | Database Redirect Engine (`models/`) | `redirection.me/support/` |

---

## 2. Standards & API Reference Locks

- **Schema.org Specification**: Version 26.0 (2024-04-26 Release).
- **Google Search Central Structured Data Guidelines**: Updated 2024-06 Specification.
- **Google Search Console API**: v3 (Search Analytics & URL Inspection).
- **Google Indexing API**: v3 (`indexing.googleapis.com/v3/urlNotifications:publish`).
- **Bing IndexNow API**: Protocol v1.0 (REST JSON payload).
- **PageSpeed Insights API**: v5 (`pagespeedonline.googleapis.com/v5/runPagespeed`).
- **Cloudflare API**: v4 (`api.cloudflare.com/client/v4/`).
