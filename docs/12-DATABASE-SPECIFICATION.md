# 12 - Database Architecture & Schema Specification

## 1. Design Rationale
To prevent memory bloat and performance degradation associated with storing millions of rows in `wp_options` or `wp_postmeta`, Apex SEO implements 11 dedicated relational tables with InnoDB storage engines, explicit primary keys, foreign object lookups, and compound indexes.

---

## 2. Table Schemas Specification

```sql
-- 1. Indexables Table (Pre-computed SEO metadata cache)
CREATE TABLE `{$wpdb->prefix}apex_indexables` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` BIGINT(20) UNSIGNED NOT NULL,
  `object_type` VARCHAR(32) NOT NULL, -- post, term, user, archive
  `object_sub_type` VARCHAR(32) NOT NULL, -- post, page, category, etc.
  `title` TEXT NULL,
  `description` TEXT NULL,
  `canonical` VARCHAR(2083) NULL,
  `primary_focus_keyword` VARCHAR(191) NULL,
  `seo_score` INT(3) UNSIGNED DEFAULT 0,
  `readability_score` INT(3) UNSIGNED DEFAULT 0,
  `is_robots_noindex` TINYINT(1) DEFAULT 0,
  `is_cornerstone` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `object_lookup` (`object_id`, `object_type`),
  KEY `seo_score_idx` (`seo_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Custom Schema Templates Table
CREATE TABLE `{$wpdb->prefix}apex_schema` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `schema_type` VARCHAR(64) NOT NULL,
  `schema_data` LONGTEXT NOT NULL,
  `conditions` LONGTEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Redirects Table
CREATE TABLE `{$wpdb->prefix}apex_redirects` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url_source` VARCHAR(2083) NOT NULL,
  `url_target` VARCHAR(2083) NOT NULL,
  `type` SMALLINT(3) NOT NULL DEFAULT 301,
  `matching_type` VARCHAR(16) NOT NULL DEFAULT 'exact', -- exact, regex
  `hits` BIGINT(20) UNSIGNED DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `source_idx` (`url_source`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. 404 Access Logs Table
CREATE TABLE `{$wpdb->prefix}apex_404_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` VARCHAR(2083) NOT NULL,
  `user_agent` TEXT NULL,
  `ip_address` VARCHAR(45) NULL, -- Anonymized
  `hit_count` INT(10) UNSIGNED DEFAULT 1,
  `last_accessed` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `url_idx` (`url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Internal & Outbound Link Index Table
CREATE TABLE `{$wpdb->prefix}apex_links` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_post_id` BIGINT(20) UNSIGNED NOT NULL,
  `target_post_id` BIGINT(20) UNSIGNED DEFAULT 0,
  `target_url` VARCHAR(2083) NOT NULL,
  `anchor_text` TEXT NULL,
  `is_internal` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `source_lookup` (`source_post_id`),
  KEY `target_lookup` (`target_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Media Optimization Task Queue Table
CREATE TABLE `{$wpdb->prefix}apex_image_queue` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attachment_id` BIGINT(20) UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, processing, completed, failed
  `attempts` TINYINT(2) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Media Optimization History & Metrics Table
CREATE TABLE `{$wpdb->prefix}apex_image_history` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attachment_id` BIGINT(20) UNSIGNED NOT NULL,
  `original_size` BIGINT(20) UNSIGNED NOT NULL,
  `optimized_size` BIGINT(20) UNSIGNED NOT NULL,
  `webp_size` BIGINT(20) UNSIGNED DEFAULT 0,
  `avif_size` BIGINT(20) UNSIGNED DEFAULT 0,
  `savings_percent` FLOAT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `att_id` (`attachment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Audit Logs Table
CREATE TABLE `{$wpdb->prefix}apex_audit_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(32) NOT NULL,
  `severity` VARCHAR(16) NOT NULL, -- critical, warning, info
  `issue_code` VARCHAR(64) NOT NULL,
  `affected_url` VARCHAR(2083) NULL,
  `details` LONGTEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Analytics Cache Table
CREATE TABLE `{$wpdb->prefix}apex_analytics` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `page_path` VARCHAR(191) NOT NULL,
  `clicks` INT(10) UNSIGNED DEFAULT 0,
  `impressions` INT(10) UNSIGNED DEFAULT 0,
  `ctr` FLOAT DEFAULT 0,
  `position` FLOAT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_page` (`date`, `page_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Keyword Rank Tracking Table
CREATE TABLE `{$wpdb->prefix}apex_rank_tracking` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword` VARCHAR(191) NOT NULL,
  `position` INT(5) UNSIGNED DEFAULT 0,
  `previous_position` INT(5) UNSIGNED DEFAULT 0,
  `checked_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kw_idx` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Page Cache Metadata Table
CREATE TABLE `{$wpdb->prefix}apex_cache_meta` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url_hash` VARCHAR(32) NOT NULL,
  `filepath` TEXT NOT NULL,
  `cache_tags` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash_idx` (`url_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
