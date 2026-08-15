<?php
namespace ApexSEO\Core\Database\Migrations;

use ApexSEO\Core\Database\MigrationInterface;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Authoritative Initial Migration: Creates the 8 Locked Custom Relational Tables.
 */
class Migration_1_0_0_CreateLockedTables implements MigrationInterface {
    /**
     * {@inheritdoc}
     */
    public function getVersion() {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
        return 'Create 8 core custom relational tables: indexables, schema, redirects, 404_logs, links, image_history, analytics, rank_tracking.';
    }

    /**
     * {@inheritdoc}
     */
    public function up(DatabaseManager $db) {
        $prefix = $db->getPrefix();
        $charsetCollate = $db->getCharsetCollate();

        $tables = [];

        // Table 1: apex_indexables
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_indexables` (
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
) ENGINE=InnoDB {$charsetCollate};";

        // Table 2: apex_schema
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_schema` (
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
) ENGINE=InnoDB {$charsetCollate};";

        // Table 3: apex_redirects
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_redirects` (
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
) ENGINE=InnoDB {$charsetCollate};";

        // Table 4: apex_404_logs
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_404_logs` (
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
) ENGINE=InnoDB {$charsetCollate};";

        // Table 5: apex_links
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_links` (
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
) ENGINE=InnoDB {$charsetCollate};";

        // Table 6: apex_image_history
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_image_history` (
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
) ENGINE=InnoDB {$charsetCollate};";

        // Table 7: apex_analytics
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_analytics` (
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
) ENGINE=InnoDB {$charsetCollate};";

        // Table 8: apex_rank_tracking
        $tables[] = "CREATE TABLE IF NOT EXISTS `{$prefix}apex_rank_tracking` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword` VARCHAR(191) NOT NULL,
  `target_url` VARCHAR(2048) NOT NULL,
  `current_position` DECIMAL(5,2) NULL,
  `previous_position` DECIMAL(5,2) NULL,
  `best_position` DECIMAL(5,2) NULL,
  `search_volume` INT UNSIGNED NULL,
  `last_checked_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_keyword_url` (`keyword`, `target_url`(191)),
  KEY `idx_current_position` (`current_position`),
  KEY `idx_last_checked` (`last_checked_at`)
) ENGINE=InnoDB {$charsetCollate};";

        foreach ($tables as $ddl) {
            $db->delta($ddl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function down(DatabaseManager $db) {
        $prefix = $db->getPrefix();
        $tables = [
            "{$prefix}apex_indexables",
            "{$prefix}apex_schema",
            "{$prefix}apex_redirects",
            "{$prefix}apex_404_logs",
            "{$prefix}apex_links",
            "{$prefix}apex_image_history",
            "{$prefix}apex_analytics",
            "{$prefix}apex_rank_tracking",
        ];

        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }

        return true;
    }
}
