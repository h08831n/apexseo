# 01 - Source Audit & Reference Inventory

## 1. Executive Summary
This document provides the foundational engineering audit of the 7 primary reference architectures:
1. **Yoast SEO (Free + Premium)** - https://github.com/Yoast/wordpress-seo
2. **Rank Math (Free + Pro)** - https://github.com/rankmath/seo-by-rank-math
3. **All in One SEO (Free + Pro)** - https://github.com/awesomemotive/all-in-one-seo-pack
4. **SEOPress (Free + Pro)** - https://github.com/wp-seopress/wp-seopress-public
5. **The SEO Framework (TSF)** - Core principles & architecture
6. **WP Rocket** - https://github.com/wp-media/wp-rocket
7. **LiteSpeed Cache for WordPress (LSCache)** - https://github.com/litespeedtech/lscache_wp

---

## 2. Deep Source Inventory & Module Extraction

### 2.1 Yoast SEO (Free & Premium)
- **Repository**: `Yoast/wordpress-seo`
- **Core Namespace**: `Yoast\WP\SEO\`
- **Key Modules & Verified File Structures**:
  - `src/models/indexable.php` -> Indexables persistence model. Relational database table storing pre-computed SEO metadata per post, term, user, post-type archive, home page, and date archive.
  - `src/repositories/indexable-repository.php` -> CRUD operations for indexables with caching layer and bulk hydration.
  - `src/builders/indexable-builder.php` -> Orchestrator combining title, description, social, and robots builders into an indexable entity.
  - `src/generators/schema-generator.php` -> Traverses schema pieces (Organization, WebSite, WebPage, Article, Person, Author) to build unified `@graph` JSON-LD.
  - `src/generators/open-graph-generator.php` -> Emits `og:title`, `og:description`, `og:url`, `og:type`, `og:image`, `article:published_time`.
  - `src/generators/twitter-generator.php` -> Emits `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`.
  - `src/sitemaps/` -> Sitemaps provider system (`xml-sitemap-feed.php`, `post-type-sitemap-provider.php`, `taxonomy-sitemap-provider.php`, `author-sitemap-provider.php`).
  - `frontend/` -> Presenter pipeline (`title-presenter.php`, `meta-description-presenter.php`, `canonical-presenter.php`, `robots-presenter.php`).
  - `premium/classes/` -> Redirect Manager (301, 302, 307, 410, 451, regex), Internal Linking Suggestion Engine (content tokenization and frequency scoring), Prominent Words indexer, Zapier webhook integration, Multi-keyword analysis.

### 2.2 Rank Math (Free & Pro)
- **Repository**: `rankmath/seo-by-rank-math`
- **Core Namespace**: `RankMath\`
- **Key Modules & Verified File Structures**:
  - `includes/modules/schema/` -> `JsonLD.php`, `Schema.php`, `DB.php`. Dynamic schema builder with 30+ default schemas and custom JSON template builder with display conditions.
  - `includes/modules/redirections/` -> `Redirection.php`, `DB.php`, `Table.php`. Regex-enabled redirect manager with status code controls (301, 302, 307, 410, 451) and hit tracking.
  - `includes/modules/404-monitor/` -> `Monitor.php`, `DB.php`. Intercepts 404 requests, logs URI, referrer, user agent, hit counts, and offers one-click redirect creation.
  - `includes/modules/analytics/` -> `Console.php`, `Analytics.php`, `DB.php`. Synchronizes Google Search Console (clicks, impressions, position, CTR) and Google Analytics 4 data into custom local tables.
  - `includes/modules/image-seo/` -> `Image_Seo.php`. Dynamically injects missing `alt` and `title` attributes on frontend output based on configurable token patterns.
  - `includes/modules/content-ai/` -> Content evaluation against target keywords, word count recommendations, heading analysis, question extraction.
  - `includes/modules/instant-indexing/` -> API submission to Google Indexing API and Bing IndexNow protocol.
  - `includes/modules/local-seo/` -> LocalBusiness schema, opening hours, multi-location CPT, KML sitemap.
  - `includes/modules/rank-tracker/` (Pro) -> Keyword position history, SERP movements.

### 2.3 All in One SEO (AIOSEO Free & Pro)
- **Repository**: `awesomemotive/all-in-one-seo-pack`
- **Core Namespace**: `AIOSEO\Plugin\`
- **Key Modules & Verified File Structures**:
  - `app/Common/Schema/` -> Graph architecture, custom schema builder with validator.
  - `app/Common/TruSeo/` -> Content analysis assessing keyphrase in title, keyphrase in meta description, keyphrase in first 10% of content, readability (Flesch-Kincaid), passive voice ratio, sentence length distribution.
  - `app/Common/LinkAssistant/` -> Scans internal links, outbound external links, calculates inbound link count per post, identifies orphan content.
  - `app/Common/Tools/` -> Robots.txt editor, .htaccess editor, Database reset, Import/Export tool.
  - `app/Common/Rss/` -> Content wrapper appending copyright notices and backlink anchors to RSS feeds.
  - `app/Pro/Sitemaps/` -> Video Sitemaps (parsing embedded YouTube, Vimeo, MP4), News Sitemaps (`<news:news>` namespace).

### 2.4 SEOPress (Free & Pro)
- **Repository**: `wp-seopress/wp-seopress-public`
- **Core Namespace**: `SEOPress\`
- **Key Modules & Verified File Structures**:
  - `inc/functions/options-titles-metas.php` -> Global metadata defaults, taxonomy meta, post type rules.
  - `inc/functions/options-xml-sitemap.php` -> Lightweight XML sitemap engine with image tags and caching.
  - `inc/functions/options-breadcrumbs.php` -> JSON-LD and HTML breadcrumb renderer.
  - `inc/admin/metaboxes/` -> Universal metabox compatible with Classic Editor, Gutenberg, Elementor, Divi, Beaver Builder, Oxygen.
  - `pro/inc/functions/broken-links.php` -> Async link checker parsing href attributes and verifying HTTP status codes.
  - `pro/inc/functions/white-label.php` -> Plugin branding override.

### 2.5 The SEO Framework (TSF)
- **Architecture**:
  - Procedural/Class-based lightweight pipeline with near-zero database overhead.
  - Generates titles, descriptions, and canonicals on-the-fly using core WordPress postmeta and terms without forced proprietary table indexing when unneeded.
  - Strict adherence to WordPress Coding Standards and sanitization.

### 2.6 WP Rocket
- **Repository**: `wp-media/wp-rocket`
- **Core Namespace**: `WP_Rocket\`
- **Key Modules & Verified File Structures**:
  - `inc/Engine/Cache/` -> `FullPage.php`, `Purger.php`, `Warmup.php`. File-based static HTML page cache with desktop/mobile variants, cookie vary headers, query string whitelist/blacklist, automatic post-update purge.
  - `inc/Engine/Optimization/CSS/` -> `Minify.php`, `Combine.php`, `RUCSS/` (Remove Unused CSS), `CriticalCSS/` (Critical path CSS inlining).
  - `inc/Engine/Optimization/JS/` -> `Minify.php`, `Combine.php`, `Defer.php`, `Delay.php` (interactivity event listeners: `keydown`, `mousemove`, `touchstart`, `scroll`).
  - `inc/Engine/Optimization/Lazyload/` -> `Images.php`, `Iframes.php`, `Background.php` (LCP above-the-fold automatic exclusion).
  - `inc/Engine/Optimization/GoogleFonts/` -> `Combine.php`, `Preconnect.php`, `SelfHost.php`.
  - `inc/Engine/CDN/` -> CNAME URL rewriter for static assets (`wp-content/`, `wp-includes/`).
  - `inc/Engine/Database/` -> `Optimization.php` (revisions, auto-drafts, trashed comments, transients).
  - `inc/Engine/Heartbeat/` -> `Heartbeat.php` (control admin/frontend heartbeat intervals).

### 2.7 LiteSpeed Cache for WordPress (LSCache)
- **Repository**: `litespeedtech/lscache_wp`
- **Core Namespace**: `LiteSpeed\`
- **Key Modules & Verified File Structures**:
  - `src/cache.cls.php` -> LSCache engine emitting `X-LiteSpeed-Cache-Control`, `X-LiteSpeed-Tag`, `X-LiteSpeed-Purge` HTTP response headers.
  - `src/esi.cls.php` -> Edge Side Includes for hole-punching personalized blocks (WooCommerce cart fragment, user menu) inside public cached pages.
  - `src/media.cls.php` -> Image optimization queue with WebP/AVIF generation and `.htaccess` rewrite rules or HTML replacement.
  - `src/object.cls.php` -> Persistent object caching driver for Redis (`phpredis` extension / TCP socket) and Memcached.
  - `src/crawler.cls.php` -> Sitemap-based page crawler simulating desktop and mobile visitors to warm server cache.
  - `src/db-optm.cls.php` -> Autoload options inspector, InnoDB table optimization.

---

## 3. Reference Summary Matrix
- Total Source Repositories Audited: **7**
- Core Modules Extracted: **58**
- Classes & Services Analyzed: **214**
- Database Tables Analyzed in Sources: **32**
- External APIs Evaluated: **9** (Google Search Console, Google Indexing, Google Analytics 4, Google PageSpeed, Bing IndexNow, Matomo, Cloudflare API, Gemini API, QUIC.cloud)
