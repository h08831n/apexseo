# Phase 3D — Migration Subsystem Runtime Evidence

## 1. Overview
The Apex SEO Migration Subsystem provides non-destructive, zero-downtime data import and schema transformation from legacy WordPress SEO plugins into the unified Apex SEO high-performance indexable database tables (`wp_apex_indexables` and `wp_apex_redirects`).

## 2. Tested Migration Adapters & Runtime Results
Environment: WordPress 6.7.2 | PHP 8.2.33 | MariaDB 10.11

| Legacy Source Plugin | Core Meta Imported | Social (OG/Twitter) Meta | Redirects Imported | Schema Settings | Dry-Run Status | Runtime Status |
|---|---|---|---|---|---|---|
| **Yoast SEO** (`yoast`) | `_yoast_wpseo_title`, `_yoast_wpseo_metadesc`, `_yoast_wpseo_focuskw` | `_yoast_wpseo_opengraph-title`, `_yoast_wpseo_twitter-image` | Migrated via indexables | Primary Category & Breadcrumbs | VERIFIED | **RUNTIME_PASS** |
| **Rank Math** (`rankmath`) | `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword` | `rank_math_facebook_title`, `rank_math_twitter_image` | Redirection DB integration | Schema templates & Rich Snippets | VERIFIED | **RUNTIME_PASS** |
| **All in One SEO** (`aioseo`) | `_aioseo_title`, `_aioseo_description`, `_aioseo_keywords` | `_aioseo_og_title`, `_aioseo_twitter_title` | Standalone table mappings | Article / WebPage schemas | VERIFIED | **RUNTIME_PASS** |
| **SEOPress** (`seopress`) | `_seopress_titles_title`, `_seopress_titles_desc` | `_seopress_social_fb_title`, `_seopress_social_twitter_img` | Redirections CPT & 301 mappings | LocalBusiness & Organization | VERIFIED | **RUNTIME_PASS** |
| **The SEO Framework** (`the_seo_framework`) | `_genesis_title`, `_genesis_description`, canonical redirect | Social image IDs & metadata | Canonical redirects | Site schema definitions | VERIFIED | **RUNTIME_PASS** |
| **Redirection Plugin** (`redirection`) | N/A (Redirect Specialist) | N/A | `wp_redirection_items` regex & 301/302 rules to `wp_apex_redirects` | N/A | VERIFIED | **RUNTIME_PASS** |

## 3. Data Transformation & Canonicalization Logic
- **Hashing**: All source URLs are indexed with MD5 hash (`source_url_hash`) for $O(1)$ constant-time lookup.
- **Normalization**: URLs are trimmed of duplicate slashes, leading/trailing whitespace, and resolved against site root.
- **Idempotency**: Importers execute with `INSERT IGNORE` or unique constraints on `(object_id, object_type)` and `permalink_hash`, preventing duplicate entries on subsequent runs.
- **Dry-Run Safety**: The CLI command `wp apexseo migrate --source=<name> --dry-run` executes full transformation without committing SQL transactions.