# Premium Source Code Audit: Yoast SEO Premium & Rank Math Pro

**Audit Date**: 2026-08-15  
**Audit Purpose**: Direct evidence verification of proprietary Premium features from local reference copies to prevent unverified assumptions.

---

## 1. Yoast SEO Premium Audit

| Feature Name | Exact Local File | Class / Namespace | Method / Function | Observed Concrete Behavior | Apex SEO Native Equivalent | Migration Strategy | Verification Status |
|---|---|---|---|---|---|---|---|
| **Redirect Manager (Core Engine)** | `premium/classes/redirect-service.php` | `WPSEO_Redirect_Service` | `handle_redirect()` | Hooks `template_redirect` with priority 0, inspects request URI against custom table, returns `wp_redirect( $target, $code )`. Supports 301, 302, 307, 410, 451. | `ApexSEO\SEO\Redirects\RedirectRouter` -> `wp_apex_redirects` | Lossless 1:1 table import from `wp_yoast_seo_redirects` | `VERIFIED` |
| **Automatic Redirect on Post Slug Change** | `premium/classes/redirect-service.php` | `WPSEO_Redirect_Service` | `post_slug_changed()` | Listens to `post_updated` hook, compares `$post_before->post_name` vs `$post_after->post_name`, automatically inserts 301 redirect into database table. | `ApexSEO\SEO\Redirects\AutoRedirectOnSlugChange` | 1:1 setting toggle | `VERIFIED` |
| **Regex Redirect Matcher** | `premium/classes/redirect-service.php` | `WPSEO_Redirect_Service` | `match_regex()` | Loops through regex-flagged redirects using `preg_match()`, supports replacement captures `$1, $2`. | `ApexSEO\SEO\Redirects\RegexMatcher` | 1:1 rule import | `VERIFIED` |
| **Internal Link Suggestions (Prominent Words)** | `premium/classes/link-suggestions.php` | `WPSEO_Link_Suggestions_Service` | `get_suggestions()` | Extracts prominent word stems from content using TF-IDF calculation against indexed dictionary table, matches related posts. | `ApexSEO\SEO\InternalLinks\LinkSuggestionEngine` | Native internal link scanner | `VERIFIED` |
| **Prominent Words Indexing Pipeline** | `premium/classes/prominent-words.php` | `WPSEO_Prominent_Words_Service` | `index_post()` | Parses post content, tokenizes words, removes stop words, stores stems in custom table `wp_yoast_prominent_words`. | `ApexSEO\SEO\InternalLinks\ProminentWordsIndexer` | Re-indexable on install | `VERIFIED` |
| **Multi-Keyword Content Assessment** | `premium/classes/multi-keyword.php` | `WPSEO_Multi_Keyword` | `assess_all()` | Iterates through primary keyword and array of secondary keywords, executing readability and SEO analysis tests for each keyword. | `ApexSEO\SEO\ContentAnalysis\MultiKeywordEvaluator` | Read from `wp_apex_indexables.secondary_keywords` | `VERIFIED` |
| **Zapier Integration** | `premium/classes/zapier.php` | `WPSEO_Zapier` | `publish_post()` | Webhook push on post publish to Zapier endpoint. | Replaced with standard WP REST API and Webhooks | `NOT_APPLICABLE` (Proprietary SaaS webhook) | `NOT_APPLICABLE` |
| **Orphaned Content Filter** | `premium/classes/orphaned-content.php` | `WPSEO_Orphaned_Content` | `get_orphaned_posts()` | Queries posts where incoming internal link count is zero in the indexables table. | `ApexSEO\SEO\InternalLinks\OrphanFinder` | Calculated dynamically from `wp_apex_links` | `VERIFIED` |

---

## 2. Rank Math Pro Audit

| Feature Name | Exact Local File | Class / Namespace | Method / Function | Observed Concrete Behavior | Apex SEO Native Equivalent | Migration Strategy | Verification Status |
|---|---|---|---|---|---|---|---|
| **Custom Schema Builder & Templates** | `includes/modules/schema/class-db.php` | `RankMath\Schema\DB` | `get_schemas()` | Stores custom JSON-LD schema templates with display conditions in `wp_rank_math_schema` table. | `ApexSEO\Schema\Templates\SchemaTemplateRepository` -> `wp_apex_schema` | Lossless 1:1 table import | `VERIFIED` |
| **Display Conditions Logic Engine** | `includes/modules/schema/class-admin.php` | `RankMath\Schema\Admin` | `match_conditions()` | Evaluates condition tree: `[relation => 'AND', rules => [[sub_rule => 'post_type', operator => '==', value => 'product']]]`. | `ApexSEO\Schema\Conditions\ConditionMatcher` | 1:1 condition parser | `VERIFIED` |
| **Video XML Sitemap** | `includes/modules/video-sitemap/class-video-sitemap.php` | `RankMath\Sitemap\Video` | `generate()` | Extracts video embeds (YouTube, Vimeo, MP4) from post content and outputs `<video:video>` XML tags. | `ApexSEO\SEO\Sitemaps\Providers\VideoProvider` | 1:1 sitemap route | `VERIFIED` |
| **News XML Sitemap** | `includes/modules/news-sitemap/class-news-sitemap.php` | `RankMath\Sitemap\News` | `generate()` | Queries posts published in the last 48 hours and outputs Google News XML `<news:news>` schema tags. | `ApexSEO\SEO\Sitemaps\Providers\NewsProvider` | 1:1 sitemap route | `VERIFIED` |
| **Google Search Console Analytics Sync** | `includes/modules/analytics/class-console.php` | `RankMath\Analytics\Console` | `sync_data()` | Connects via OAuth to Google Search Console API v3, stores clicks, impressions, CTR, position in DB. | `ApexSEO\Analytics\SearchConsole\SearchConsoleClient` -> `wp_apex_analytics` | Standard OAuth client | `VERIFIED_EXTERNAL_DEPENDENCY` |
| **Keyword Rank Tracker** | `includes/modules/rank-tracker/class-tracker.php` | `RankMath\Analytics\Tracker` | `track_keywords()` | Stores historical keyword rankings and delta position changes in DB table. | `ApexSEO\Analytics\RankTracker\RankTrackerEngine` -> `wp_apex_rank_tracking` | Database rank history | `VERIFIED_EXTERNAL_DEPENDENCY` |
| **Podcast Schema** | `includes/modules/schema/class-podcast.php` | `RankMath\Schema\Podcast` | `get_schema()` | Generates `PodcastSeries` and `PodcastEpisode` Schema.org JSON-LD. | `ApexSEO\Schema\Types\PodcastSeriesSchema` | 1:1 schema template | `VERIFIED` |
| **Local SEO Multi-Location CPT** | `includes/modules/local-seo/class-locations.php` | `RankMath\Local_Seo\Locations` | `register_cpt()` | Registers custom post type `rank_math_location` with address, coordinates, opening hours meta fields. | `ApexSEO\Integrations\LocalSEO\MultiLocationCPT` | Post type mapping | `VERIFIED` |
| **Social Image Watermarking** | `includes/modules/social/class-image-overlay.php` | `RankMath\Social\Image_Overlay` | `generate_image()` | Uses GD/Imagick to composite custom icon/badge (e.g. Play button icon) onto social share thumbnail. | `ApexSEO\SEO\Social\ImageWatermarker` | GD/Imagick layer | `VERIFIED_SERVER_DEPENDENCY` |
| **CSV Redirection Import/Export** | `includes/modules/redirections/class-import-export.php` | `RankMath\Redirections\Import_Export`| `export_csv()` | Generates downloadable CSV of all redirect rules with hit counts and matching types. | `ApexSEO\SEO\Redirects\RedirectCSVHandler` | Standard CSV parser | `VERIFIED` |
