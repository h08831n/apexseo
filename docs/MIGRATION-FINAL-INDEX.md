# Authoritative Migration Source Matrix & Data Transformation Specification

**Audit Lock Date**: 2026-08-15  
**Document Purpose**: Full evidentiary catalog and mapping specification for all 8 supported migration source plugins, resolving the 7 vs 8 migration discrepancy by including the standalone Redirection plugin.

---

## 1. Migration Source Overview (8 Supported Plugins)

| # | Source Product | Plugin Slug | Versions Tested | Detected Data Locations | Target Apex Storage | Migration Handler Class | Migration Status |
|---|---|---|---|---|---|---|---|
| 1 | **Yoast SEO** (Free & Premium) | `wordpress-seo`, `wordpress-seo-premium` | 20.0 – 22.8 | `wp_postmeta`, `wp_options`, `wp_yoast_indexable`, `wp_yoast_seo_links`, `wp_yoast_prominent_words` | `wp_apex_indexables`, `wp_apex_redirects`, `wp_options` | `src/Migration/Importers/YoastImporter.php` | `VERIFIED` |
| 2 | **Rank Math** (Free & Pro) | `seo-by-rank-math`, `seo-by-rank-math-pro` | 1.0.100 – 3.0.64 | `wp_postmeta`, `wp_options`, `wp_rank_math_schema`, `wp_rank_math_redirections`, `wp_rank_math_404_logs` | `wp_apex_indexables`, `wp_apex_schema`, `wp_apex_redirects`, `wp_options` | `src/Migration/Importers/RankMathImporter.php` | `VERIFIED` |
| 3 | **All in One SEO** (Free & Pro) | `all-in-one-seo-pack`, `all-in-one-seo-pack-pro` | 4.2.0 – 4.6.4 | `wp_postmeta`, `wp_options`, `wp_aioseo_posts`, `wp_aioseo_redirects`, `wp_aioseo_links` | `wp_apex_indexables`, `wp_apex_redirects`, `wp_options` | `src/Migration/Importers/AioseoImporter.php` | `VERIFIED` |
| 4 | **SEOPress** (Free & Pro) | `wp-seopress`, `wp-seopress-pro` | 6.0 – 7.8.1 | `wp_postmeta`, `wp_options`, `wp_seopress_404`, `wp_seopress_significant_keywords` | `wp_apex_indexables`, `wp_apex_redirects`, `wp_options` | `src/Migration/Importers/SeoPressImporter.php` | `VERIFIED` |
| 5 | **The SEO Framework** | `autodescription` | 4.2.0 – 5.0.5 | `wp_postmeta` (`_genesis_*`, `_tsf_*`), `wp_options` (`autodescription-site-settings`) | `wp_apex_indexables`, `wp_options` | `src/Migration/Importers/TsfImporter.php` | `VERIFIED` |
| 6 | **WP Rocket** | `wp-rocket` | 3.12.0 – 3.16.1 | `wp_options` (`wp_rocket_settings`) | `wp_options` (`apex_performance_settings`) | `src/Migration/Importers/WpRocketImporter.php` | `VERIFIED` |
| 7 | **LiteSpeed Cache** | `litespeed-cache` | 5.0 – 6.2.0.1 | `wp_options` (`litespeed.conf.*`) | `wp_options` (`apex_performance_settings`) | `src/Migration/Importers/LiteSpeedImporter.php`| `VERIFIED` |
| 8 | **Redirection** (John Godley) | `redirection` | 5.0 – 5.4.2 | `wp_redirection_items`, `wp_redirection_groups`, `wp_redirection_404` | `wp_apex_redirects`, `wp_apex_404_logs` | `src/Migration/Importers/RedirectionImporter.php` | `VERIFIED` |

---

## 2. Granular Field Mapping & Transformation Rules

### A. Yoast SEO -> Apex SEO

| Source Field (Yoast) | Target Field (Apex) | Transformation Logic |
|---|---|---|
| `_yoast_wpseo_title` | `wp_apex_indexables.title` | Direct string copy; converts `%%title%%` -> `{{title}}`, `%%sitename%%` -> `{{site_title}}`, `%%sep%%` -> `{{separator}}`. |
| `_yoast_wpseo_metadesc` | `wp_apex_indexables.description` | Direct string copy; converts Yoast variable syntax to Apex `{{var}}` format. |
| `_yoast_wpseo_meta-robots-noindex` | `wp_apex_indexables.is_robots_noindex` | `1` -> `1`, `2` (index) -> `0`, `0` (default) -> `NULL` (inherits global setting). |
| `_yoast_wpseo_meta-robots-nofollow` | `wp_apex_indexables.is_robots_nofollow` | `1` -> `1`, `0` -> `0`. |
| `_yoast_wpseo_canonical` | `wp_apex_indexables.canonical_url` | Direct URL string validation via `esc_url_raw()`. |
| `_yoast_wpseo_opengraph-title` | `wp_apex_indexables.og_title` | Direct string copy. |
| `_yoast_wpseo_opengraph-description` | `wp_apex_indexables.og_description` | Direct string copy. |
| `_yoast_wpseo_opengraph-image` | `wp_apex_indexables.og_image` | Image URL string. |
| `_yoast_wpseo_twitter-title` | `wp_apex_indexables.twitter_title` | Direct string copy. |
| `_yoast_wpseo_twitter-description` | `wp_apex_indexables.twitter_description` | Direct string copy. |
| `_yoast_wpseo_twitter-image` | `wp_apex_indexables.twitter_image` | Image URL string. |
| `_yoast_wpseo_focuskw` | `wp_apex_indexables.primary_focus_keyword` | Trimmed lowercase keyword string. |
| `_yoast_wpseo_focuskeywords` (JSON) | `wp_apex_indexables.secondary_keywords` | Decodes JSON array and stores as JSON text array. |
| `wp_yoast_indexable` (redirect) | `wp_apex_redirects` | Migrates source URL, target URL, HTTP code (`301`, `302`, `307`, `410`), and created date. |
| `wp_yoast_seo_links` | `wp_apex_links` | Copies `url`, `post_id`, `target_post_id`, and `type` (`internal`/`external`). |

---

### B. Rank Math -> Apex SEO

| Source Field (Rank Math) | Target Field (Apex) | Transformation Logic |
|---|---|---|
| `rank_math_title` | `wp_apex_indexables.title` | Direct copy; converts `%title%` -> `{{title}}`, `%sitename%` -> `{{site_title}}`, `%sep%` -> `{{separator}}`. |
| `rank_math_description` | `wp_apex_indexables.description` | Direct copy; converts Rank Math variable tokens. |
| `rank_math_robots` (serialized) | `wp_apex_indexables.is_robots_*` | Unserializes array: checks for `noindex`, `nofollow`, `noarchive`, `nosnippet`, `noimageindex`. |
| `rank_math_canonical_url` | `wp_apex_indexables.canonical_url` | Direct URL copy. |
| `rank_math_facebook_title` | `wp_apex_indexables.og_title` | Direct copy. |
| `rank_math_facebook_description` | `wp_apex_indexables.og_description` | Direct copy. |
| `rank_math_facebook_image` | `wp_apex_indexables.og_image` | Image URL. |
| `rank_math_focus_keyword` | `wp_apex_indexables.primary_focus_keyword` | Extracts first comma-separated keyword as primary; remaining as secondary. |
| `wp_rank_math_schema` / `rank_math_schema_*` | `wp_apex_schema` | Parses JSON schema template, converts conditions, and inserts into `wp_apex_schema`. |
| `wp_rank_math_redirections` | `wp_apex_redirects` | Maps `sources`, `url_to`, `header_code` (`301`, `302`, `307`, `410`, `451`), and `status`. |
| `wp_rank_math_404_logs` | `wp_apex_404_logs` | Migrates `uri`, `accessed`, `times_visited`, and `referer`. |

---

### C. All in One SEO (AIOSEO) -> Apex SEO

| Source Field (AIOSEO) | Target Field (Apex) | Transformation Logic |
|---|---|---|
| `_aioseo_title` / `wp_aioseo_posts.title` | `wp_apex_indexables.title` | Direct copy; converts `tag_title` -> `{{title}}`, `tag_site_title` -> `{{site_title}}`. |
| `_aioseo_description` | `wp_apex_indexables.description` | Direct copy with token conversion. |
| `_aioseo_og_title` | `wp_apex_indexables.og_title` | Direct copy. |
| `_aioseo_canonical_url` | `wp_apex_indexables.canonical_url` | Direct URL copy. |
| `wp_aioseo_redirects` | `wp_apex_redirects` | Maps source URL, target URL, type (`301`, `302`, `307`, `410`), and hits. |

---

### D. SEOPress -> Apex SEO

| Source Field (SEOPress) | Target Field (Apex) | Transformation Logic |
|---|---|---|
| `_seopress_titles_title` | `wp_apex_indexables.title` | Direct string copy; converts `%%post_title%%` -> `{{title}}`. |
| `_seopress_titles_desc` | `wp_apex_indexables.description` | Direct string copy with token conversion. |
| `_seopress_robots_index` | `wp_apex_indexables.is_robots_noindex` | `'yes'` -> `1`, `''` or `'no'` -> `0`. |
| `_seopress_robots_follow` | `wp_apex_indexables.is_robots_nofollow` | `'yes'` -> `1`, `''` or `'no'` -> `0`. |
| `_seopress_robots_canonical` | `wp_apex_indexables.canonical_url` | Direct URL string. |
| `_seopress_social_fb_title` | `wp_apex_indexables.og_title` | Direct copy. |
| `_seopress_redirections_value` | `wp_apex_redirects` | Migrates custom redirection post type entries into `wp_apex_redirects`. |

---

### E. The SEO Framework (TSF) -> Apex SEO

| Source Field (TSF) | Target Field (Apex) | Transformation Logic |
|---|---|---|
| `_genesis_title` / `_tsf_title` | `wp_apex_indexables.title` | Direct string copy. |
| `_genesis_description` / `_tsf_description` | `wp_apex_indexables.description` | Direct string copy. |
| `_genesis_noindex` | `wp_apex_indexables.is_robots_noindex` | `1` -> `1`, `0` -> `0`. |
| `_genesis_nofollow` | `wp_apex_indexables.is_robots_nofollow` | `1` -> `1`, `0` -> `0`. |
| `_genesis_canonical_uri` | `wp_apex_indexables.canonical_url` | Direct URL string. |
| `_tsf_social_image_url` | `wp_apex_indexables.og_image` | Direct image URL string. |

---

### F. WP Rocket -> Apex Performance Settings

| Source Setting (WP Rocket) | Target Setting (Apex) | Transformation Logic |
|---|---|---|
| `cache_mobile` (`1`/`0`) | `apex_performance_settings['cache_mobile']` | Direct boolean mapping. |
| `cache_logged_user` (`1`/`0`) | `apex_performance_settings['cache_logged_in']` | Direct boolean mapping. |
| `purge_cron_interval` | `apex_performance_settings['cache_ttl']` | Maps hours to seconds (e.g. 10h -> 36000s). |
| `minify_css` (`1`/`0`) | `apex_performance_settings['minify_css']` | Direct boolean mapping. |
| `minify_js` (`1`/`0`) | `apex_performance_settings['minify_js']` | Direct boolean mapping. |
| `defer_all_js` (`1`/`0`) | `apex_performance_settings['defer_js']` | Direct boolean mapping. |
| `delay_js` (`1`/`0`) | `apex_performance_settings['delay_js']` | Direct boolean mapping. |
| `lazyload` (`1`/`0`) | `apex_performance_settings['lazyload_images']`| Direct boolean mapping. |
| `lazyload_iframes` (`1`/`0`) | `apex_performance_settings['lazyload_iframes']`| Direct boolean mapping. |
| `cdn` (`1`/`0`) & `cdn_cnames` | `apex_performance_settings['cdn_cnames']` | Copies CDN hostnames array. |

---

### G. LiteSpeed Cache -> Apex Performance Settings

| Source Setting (LiteSpeed) | Target Setting (Apex) | Transformation Logic |
|---|---|---|
| `cache-priv` (`1`/`0`) | `apex_performance_settings['cache_logged_in']` | Direct boolean mapping. |
| `cache-mobile` (`1`/`0`) | `apex_performance_settings['cache_mobile']` | Direct boolean mapping. |
| `cache-ttl_pub` | `apex_performance_settings['cache_ttl']` | Direct integer seconds copy. |
| `media-lazy` (`1`/`0`) | `apex_performance_settings['lazyload_images']`| Direct boolean mapping. |
| `media-webp_replace` (`1`/`0`) | `apex_performance_settings['webp_replace']` | Direct boolean mapping. |
| `optm-css_min` (`1`/`0`) | `apex_performance_settings['minify_css']` | Direct boolean mapping. |
| `optm-js_min` (`1`/`0`) | `apex_performance_settings['minify_js']` | Direct boolean mapping. |
| `optm-js_defer` (`1`/`0`) | `apex_performance_settings['defer_js']` | Direct boolean mapping. |
| `cdn` (`1`/`0`) & `cdn-url` | `apex_performance_settings['cdn_cnames']` | Converts CDN domain list to Apex array. |

---

### H. Redirection (John Godley) -> Apex SEO

| Source Table (`wp_redirection_items`) | Target Table (`wp_apex_redirects`) | Transformation Logic |
|---|---|---|
| `url` | `source_url` | Direct string copy; handles regex flags if `match_type = 'url'`. |
| `action_data` | `target_url` | Direct string copy. |
| `action_code` | `status_code` | Directly maps HTTP codes (`301`, `302`, `307`, `410`, `451`). |
| `match_type` | `match_type` | `'url'` -> `'exact'`, `'regex'` -> `'regex'`. |
| `last_access` | `last_accessed_at` | Converts UNIX timestamp to MySQL `DATETIME`. |
| `match_count` | `hits_count` | Integer counter copy. |
| `status` | `status` | `'enabled'` -> `'active'`, `'disabled'` -> `'disabled'`. |

---

## 3. Unsupported Data & Edge Cases (Explicitly Non-Migrated)

| Source Product | Non-Migrated Elements | Reason & Resolution |
|---|---|---|
| **Yoast SEO** | Proprietary Semrush OAuth tokens, Zapier webhook API keys. | Security risk: API tokens must be re-authenticated by user. |
| **Rank Math** | Google Search Console offline OAuth refresh tokens. | Security risk: User must authenticate via Apex OAuth flow. |
| **LiteSpeed Cache** | QUIC.cloud remote credit balances and server node IPs. | Proprietary cloud infrastructure; replaced by local GD/AST engines. |
| **WP Rocket** | RocketCDN subscription keys and license tokens. | Commercial SaaS billing credentials not applicable to self-hosted plugin. |

---

## 4. Rollback & Migration Safety Architecture

1. **Transactional Execution**: Migrations run inside MySQL transactions (`START TRANSACTION` / `COMMIT`) per batch. If a batch fails, it automatically issues `ROLLBACK`.
2. **Snapshot Creation**: Before initiating any migration, a snapshot JSON dump of existing `wp_options` and target table row counts is written to `/wp-content/cache/apex-migration-backup-{timestamp}.json`.
3. **Non-Destructive Import**: Migrations **never delete** source plugin data (`wp_postmeta` or source tables remain 100% untouched).
4. **One-Click Rollback**: Admins can click "Revert Migration" to truncate Apex custom tables and restore previous settings from the JSON snapshot.
5. **Batch Processing**: Background processing uses chunks of **500 records per batch** via WP-Cron / Action Scheduler to prevent memory exhaustion and timeout errors on large sites (100,000+ posts).
