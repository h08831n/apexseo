<?php
namespace ApexSEO\Core\Database\Migrations;

use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\MigrationInterface;

class Migration_1_0_0_CreateLockedTables implements MigrationInterface {
    public function getVersion(): string {
        return '1.0.0';
    }

    public function getDescription(): string {
        return 'Create initial locked 8 production database tables for APEX SEO';
    }

    public function up(DatabaseManager $db): bool {
        $prefix = $db->getPrefix();
        $wpdb = $db->getWpdb();
        $charsetCollate = ($wpdb && method_exists($wpdb, 'get_charset_collate')) ? $wpdb->get_charset_collate() : 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        $tables = [
            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_indexables` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `object_id` bigint(20) unsigned NOT NULL,
                `object_type` varchar(32) NOT NULL DEFAULT 'post',
                `object_sub_type` varchar(32) NOT NULL DEFAULT 'post',
                `permalink` text,
                `canonical_url` text,
                `title` text,
                `description` text,
                `robots_index` tinyint(1) NOT NULL DEFAULT 1,
                `robots_follow` tinyint(1) NOT NULL DEFAULT 1,
                `primary_focus_keyword` varchar(191) DEFAULT NULL,
                `keyword_density` decimal(5,2) DEFAULT NULL,
                `readability_score` int(11) DEFAULT NULL,
                `content_analysis` longtext DEFAULT NULL,
                `is_cornerstone` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_object` (`object_id`, `object_type`),
                KEY `idx_permalink_hash` (`object_type`, `object_sub_type`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_schema` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `object_id` bigint(20) unsigned NOT NULL,
                `object_type` varchar(32) NOT NULL DEFAULT 'post',
                `schema_type` varchar(64) NOT NULL,
                `schema_json` longtext NOT NULL,
                `is_custom` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_object` (`object_id`, `object_type`),
                KEY `idx_schema_type` (`schema_type`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_redirects` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `source_path` varchar(191) NOT NULL,
                `target_url` text NOT NULL,
                `status_code` smallint(5) unsigned NOT NULL DEFAULT 301,
                `match_type` varchar(16) NOT NULL DEFAULT 'exact',
                `hits` bigint(20) unsigned NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_source_path` (`source_path`),
                KEY `idx_active` (`is_active`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_404_logs` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `request_uri` varchar(191) NOT NULL,
                `referrer` text,
                `user_agent` text,
                `ip_address` varchar(45) DEFAULT NULL,
                `hits` bigint(20) unsigned NOT NULL DEFAULT 1,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_request_uri` (`request_uri`),
                KEY `idx_hits` (`hits`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_links` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `source_id` bigint(20) unsigned NOT NULL,
                `target_id` bigint(20) unsigned DEFAULT NULL,
                `target_url` text NOT NULL,
                `anchor_text` text,
                `link_type` varchar(16) NOT NULL DEFAULT 'internal',
                `is_nofollow` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_source` (`source_id`),
                KEY `idx_target` (`target_id`),
                KEY `idx_type` (`link_type`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_image_history` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `attachment_id` bigint(20) unsigned NOT NULL,
                `original_size` bigint(20) unsigned NOT NULL,
                `optimized_size` bigint(20) unsigned NOT NULL,
                `saved_bytes` bigint(20) unsigned NOT NULL,
                `mime_type` varchar(64) NOT NULL,
                `optimizer_used` varchar(64) NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_attachment` (`attachment_id`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_analytics` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `metric_type` varchar(64) NOT NULL,
                `metric_key` varchar(191) NOT NULL,
                `metric_value` decimal(12,4) NOT NULL,
                `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_metric_lookup` (`metric_type`, `metric_key`),
                KEY `idx_recorded` (`recorded_at`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_rank_tracking` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `keyword` varchar(191) NOT NULL,
                `url` text NOT NULL,
                `position` int(11) DEFAULT NULL,
                `previous_position` int(11) DEFAULT NULL,
                `device` varchar(16) NOT NULL DEFAULT 'desktop',
                `checked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_keyword` (`keyword`),
                KEY `idx_checked` (`checked_at`)
            ) {$charsetCollate};"
        ];

        foreach ($tables as $sql) {
            $db->query($sql);
        }

        return true;
    }

    public function down(DatabaseManager $db): bool {
        $prefix = $db->getPrefix();
        $tableNames = [
            "{$prefix}apex_indexables",
            "{$prefix}apex_schema",
            "{$prefix}apex_redirects",
            "{$prefix}apex_404_logs",
            "{$prefix}apex_links",
            "{$prefix}apex_image_history",
            "{$prefix}apex_analytics",
            "{$prefix}apex_rank_tracking",
        ];

        foreach ($tableNames as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`;");
        }

        return true;
    }
}
