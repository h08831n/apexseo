# Authoritative Database Architecture & Relational Table Specification

**Audit Lock Date**: 2026-08-15  
**Document Purpose**: Full technical validation, DDL schemas, index optimization, query patterns, and architectural justifications for the 8 dedicated custom tables in Apex SEO.

---

## 1. Relational Table Summary Matrix (8 Custom Tables)

| # | Table Name | Primary Key | Key Relationships | Est. Row Size | Est. Rows (10k Posts) | Read / Write Pattern | Justification vs `wp_postmeta` / `wp_options` |
|---|---|---|---|---|---|---|---|
| 1 | `wp_apex_indexables` | `id` (BIGINT UNSIGNED) | `object_id` -> `wp_posts.ID` / `wp_terms.term_id` | ~680 bytes | ~11,500 rows | Reads: High (Every dynamic page request, 1 query). Writes: Low (Only on post/term save). | Eliminates 15-20 individual `wp_postmeta` queries per page load; allows single indexed primary lookup on `(object_type, object_id)`. |
| 2 | `wp_apex_schema` | `id` (BIGINT UNSIGNED) | `object_id` (optional), `template_id` | ~1,200 bytes | ~500 rows | Reads: High (Cached in memory). Writes: Very Low (Admin config). | Supports multi-rule conditional JSON evaluation (`post_type`, `taxonomy`, `user_role`) without bloating `wp_options`. |
| 3 | `wp_apex_redirects` | `id` (BIGINT UNSIGNED) | None | ~320 bytes | ~2,500 rows | Reads: Extremely High (Every 404/incoming request). Writes: Low (Admin/URL changes). | Fast b-tree index lookup on `source_url_hash` (MD5) in <0.2ms; avoids full-table scans on `wp_postmeta`. |
| 4 | `wp_apex_404_logs` | `id` (BIGINT UNSIGNED) | None | ~240 bytes | ~10,000 rows (Pruned) | Reads: Low (Admin UI). Writes: High (Every unhandled 404 request, buffered). | High write frequency would cause table locks and severe autoload bloat in `wp_options`. |
| 5 | `wp_apex_links` | `id` (BIGINT UNSIGNED) | `post_id` -> `wp_posts.ID`, `target_post_id` | ~180 bytes | ~45,000 rows | Reads: Medium (Editor suggestions). Writes: Batch on post save. | Relational graph indexing for internal link counts, orphaned content queries, and anchor analysis. |
| 6 | `wp_apex_image_history` | `id` (BIGINT UNSIGNED) | `attachment_id` -> `wp_posts.ID` | ~210 bytes | ~15,000 rows | Reads: Low (Media library UI). Writes: Once per image optimization. | Prevents repetitive re-optimization loops; tracks original file sizes, WebP/AVIF paths, and compression ratios. |
| 7 | `wp_apex_analytics` | `id` (BIGINT UNSIGNED) | `object_id` -> `wp_posts.ID` | ~160 bytes | ~30,000 rows | Reads: Medium (Dashboard charts). Writes: Scheduled background sync (GSC). | Time-series query support (`date`, `impressions`, `clicks`, `position`) with composite time-range indexing. |
| 8 | `wp_apex_rank_tracking` | `id` (BIGINT UNSIGNED) | None | ~190 bytes | ~5,000 rows | Reads: Low (Admin UI). Writes: Daily Cron sync. | Multi-keyword position delta tracking with historical trend graphing. |

---

## 2. Exhaustive DDL Specifications (MySQL 5.7+ / MariaDB 10.3+ Compliant)

```sql
-- =============================================================================
-- Table 1: wp_apex_indexables (Core SEO Meta & Scoring Engine)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_indexables` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` BIGINT UNSIGNED NOT NULL,
  `object_type` VARCHAR(32) NOT NULL DEFAULT 'post',
  `object_sub_type` VARCHAR(64) NOT NULL DEFAULT 'post',
  `permalink` VARCHAR(2048) NOT NULL,
  `permalink_hash` CHAR(32) NOT NULL,
  `title` TEXT NULL,
  `description` TEXT NULL,
  `canonical_url` VARCHAR(2048) NULL,
  `is_robots_noindex` TINYINT(1) NULL DEFAULT 0,
  `is_robots_nofollow` TINYINT(1) NULL DEFAULT 0,
  `is_robots_noarchive` TINYINT(1) NULL DEFAULT 0,
  `is_robots_nosnippet` TINYINT(1) NULL DEFAULT 0,
  `is_robots_noimageindex` TINYINT(1) NULL DEFAULT 0,
  `og_title` TEXT NULL,
  `og_description` TEXT NULL,
  `og_image` VARCHAR(2048) NULL,
  `og_image_id` BIGINT UNSIGNED NULL,
  `twitter_title` TEXT NULL,
  `twitter_description` TEXT NULL,
  `twitter_image` VARCHAR(2048) NULL,
  `primary_focus_keyword` VARCHAR(191) NULL,
  `secondary_keywords` LONGTEXT NULL,
  `seo_score` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `readability_score` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `link_count_internal` INT UNSIGNED NOT NULL DEFAULT 0,
  `link_count_inbound` INT UNSIGNED NOT NULL DEFAULT 0,
  `link_count_external` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_cornerstone` TINYINT(1) NOT NULL DEFAULT 0,
  `schema_type` VARCHAR(64) NULL DEFAULT 'Article',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_object_lookup` (`object_type`, `object_id`),
  KEY `idx_permalink_hash` (`permalink_hash`),
  KEY `idx_sub_type` (`object_sub_type`),
  KEY `idx_seo_score` (`seo_score`),
  KEY `idx_inbound_links` (`link_count_inbound`),
  KEY `idx_cornerstone` (`is_cornerstone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- =============================================================================
-- Table 2: wp_apex_schema (Dynamic Schema.org Builder & Rules)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_schema` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `schema_type` VARCHAR(64) NOT NULL,
  `schema_data` LONGTEXT NOT NULL,
  `conditions` LONGTEXT NOT NULL,
  `is_global` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schema_type` (`schema_type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_global` (`is_global`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- =============================================================================
-- Table 3: wp_apex_redirects (High-Speed URL Router & Redirection Engine)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_redirects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_url` VARCHAR(2048) NOT NULL,
  `source_url_hash` CHAR(32) NOT NULL,
  `target_url` VARCHAR(2048) NOT NULL,
  `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  `match_type` ENUM('exact', 'regex', 'prefix') NOT NULL DEFAULT 'exact',
  `is_regex` TINYINT(1) NOT NULL DEFAULT 0,
  `hits_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  `last_accessed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_source_url_hash` (`source_url_hash`),
  KEY `idx_status_code` (`status_code`),
  KEY `idx_is_regex` (`is_regex`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- =============================================================================
-- Table 4: wp_apex_404_logs (High-Speed Buffered 404 Logging & Error Monitor)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_404_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uri` VARCHAR(2048) NOT NULL,
  `uri_hash` CHAR(32) NOT NULL,
  `referer` VARCHAR(2048) NULL,
  `user_agent` VARCHAR(512) NULL,
  `ip_address` VARCHAR(45) NULL,
  `hit_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `is_redirected` TINYINT(1) NOT NULL DEFAULT 0,
  `last_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uri_hash` (`uri_hash`),
  KEY `idx_hit_count` (`hit_count`),
  KEY `idx_last_seen` (`last_seen`),
  KEY `idx_is_redirected` (`is_redirected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- =============================================================================
-- Table 5: wp_apex_links (Internal & External Link Graph Assistant)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `target_post_id` BIGINT UNSIGNED NULL,
  `url` VARCHAR(2048) NOT NULL,
  `url_hash` CHAR(32) NOT NULL,
  `anchor_text` TEXT NULL,
  `link_type` ENUM('internal', 'external') NOT NULL DEFAULT 'internal',
  `is_nofollow` TINYINT(1) NOT NULL DEFAULT 0,
  `is_ugc` TINYINT(1) NOT NULL DEFAULT 0,
  `is_sponsored` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_target_post_id` (`target_post_id`),
  KEY `idx_url_hash` (`url_hash`),
  KEY `idx_link_type` (`link_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- =============================================================================
-- Table 6: wp_apex_image_history (Media Compression & WebP/AVIF Tracking)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_image_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attachment_id` BIGINT UNSIGNED NOT NULL,
  `original_size` INT UNSIGNED NOT NULL,
  `optimized_size` INT UNSIGNED NOT NULL,
  `webp_size` INT UNSIGNED NULL,
  `avif_size` INT UNSIGNED NULL,
  `savings_bytes` INT UNSIGNED NOT NULL DEFAULT 0,
  `savings_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `format_served` VARCHAR(16) NOT NULL DEFAULT 'original',
  `optimized_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attachment_id` (`attachment_id`),
  KEY `idx_format_served` (`format_served`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- =============================================================================
-- Table 7: wp_apex_analytics (GSC & Performance Time-Series Cache)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_analytics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` BIGINT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `impressions` INT UNSIGNED NOT NULL DEFAULT 0,
  `ctr` DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
  `position` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_object_date` (`object_id`, `date`),
  KEY `idx_date` (`date`),
  KEY `idx_clicks` (`clicks`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- =============================================================================
-- Table 8: wp_apex_rank_tracking (Keyword Position Monitoring)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `wp_apex_rank_tracking` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword` VARCHAR(191) NOT NULL,
  `target_url` VARCHAR(2048) NOT NULL,
  `current_position` DECIMAL(5,2) NULL,
  `previous_position` DECIMAL(5,2) NULL,
  `best_position` DECIMAL(5,2) NULL,
  `search_volume` INT UNSIGNED NULL,
  `last_checked_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_keyword_url` (`keyword`, `target_url`(191)),
  KEY `idx_current_position` (`current_position`),
  KEY `idx_last_checked` (`last_checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

---

## 3. Data Retention & Pruning Policies

1. **404 Logs (`wp_apex_404_logs`)**:
   - Automated pruning via daily WP-Cron: deletes log entries not seen in the last **30 days** or caps table at **10,000 most frequent entries**.
2. **Analytics Time-Series (`wp_apex_analytics`)**:
   - Retains **90 days** of granular daily Search Console metrics by default (configurable up to 365 days); older records are summarized into monthly aggregates.
3. **Redirect Hits (`wp_apex_redirects`)**:
   - Inactive redirects (`status = 'disabled'`) with 0 hits over 180 days are flagged in the admin for review.

---

## 4. Datasets Stored Without Custom Tables (Architecture Justification)

| Dataset | Storage Medium | Justification |
|---|---|---|
| **Plugin Global Settings** | `wp_options` (`apex_seo_settings`, `apex_perf_settings`) | Read-only per request; cached in memory by WordPress core `alloptions` autoloading; zero extra database queries. |
| **Transient Image Processing Queue** | Action Scheduler / WP-Cron atomic transients | Transient in-flight queue items have short lifecycles (<2 minutes); using a dedicated table would cause high InnoDB deadlocks and fragmented auto-increment gaps. |
| **System Diagnostic & Audit Logs** | `/wp-content/cache/apex-audit.log` | Rolling filesystem logs provide zero-DB-load auditing even when database connections fail. |
| **Static Cache Metadata** | Companion `.meta` files on disk | Reading cache metadata alongside `.html` files in filesystem takes <0.1ms without touching MySQL. |
