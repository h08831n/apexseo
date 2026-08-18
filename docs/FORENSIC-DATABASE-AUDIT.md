# APEX SEO — ZERO-TRUST DATABASE FORENSIC AUDIT REPORT

> **AUDIT BASELINE**: Physical source code verification against `Migration_1_0_0_CreateLockedTables.php` and all SQL executions in `src/` and `tests/`.  
> **AUDIT DATE**: 2026-08-18  
> **DATABASE ENGINE**: MySQL 5.7+ / MariaDB 10.3+ (InnoDB)  

---

## 1. Locked Database Schema Specification (8 Tables)

The authoritative migration `src/Core/Database/Migrations/Migration_1_0_0_CreateLockedTables.php` defines exactly 8 custom tables:

### Table 1: `{$wpdb->prefix}apex_indexables`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `object_id` (BIGINT(20) UNSIGNED NOT NULL)
  - `object_type` (VARCHAR(32) NOT NULL)
  - `object_sub_type` (VARCHAR(64) DEFAULT NULL)
  - `permalink` (TEXT DEFAULT NULL)
  - `permalink_hash` (CHAR(32) DEFAULT NULL)
  - `title` (TEXT DEFAULT NULL)
  - `description` (TEXT DEFAULT NULL)
  - `canonical_url` (TEXT DEFAULT NULL)
  - `robots` (VARCHAR(128) DEFAULT NULL)
  - `primary_focus_keyword` (VARCHAR(191) DEFAULT NULL)
  - `seo_score` (INT(11) DEFAULT 0)
  - `readability_score` (INT(11) DEFAULT 0)
  - `is_cornerstone` (TINYINT(1) DEFAULT 0)
  - `schema_type` (VARCHAR(64) DEFAULT NULL)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
- **Indexes**:
  - `UNIQUE KEY uk_object_lookup (object_type, object_id)`
  - `KEY idx_permalink_hash (permalink_hash)`
  - `KEY idx_seo_score (seo_score)`
  - `KEY idx_cornerstone (is_cornerstone)`

### Table 2: `{$wpdb->prefix}apex_schema`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `object_id` (BIGINT(20) UNSIGNED DEFAULT NULL)
  - `schema_type` (VARCHAR(64) NOT NULL)
  - `schema_data` (LONGTEXT NOT NULL)
  - `is_global` (TINYINT(1) DEFAULT 0)
  - `status` (VARCHAR(20) DEFAULT 'active')
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
- **Indexes**:
  - `KEY idx_object_id (object_id)`
  - `KEY idx_schema_type (schema_type)`
  - `KEY idx_is_global (is_global)`
  - `KEY idx_status (status)`

### Table 3: `{$wpdb->prefix}apex_redirects`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `source_url` (TEXT NOT NULL)
  - `source_url_hash` (CHAR(32) NOT NULL)
  - `target_url` (TEXT NOT NULL)
  - `status_code` (SMALLINT(5) UNSIGNED NOT NULL DEFAULT 301)
  - `is_regex` (TINYINT(1) NOT NULL DEFAULT 0)
  - `status` (VARCHAR(20) NOT NULL DEFAULT 'active')
  - `hits_count` (BIGINT(20) UNSIGNED NOT NULL DEFAULT 0)
  - `last_accessed_at` (DATETIME DEFAULT NULL)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
- **Indexes**:
  - `UNIQUE KEY uk_source_hash (source_url_hash)`
  - `KEY idx_status (status)`
  - `KEY idx_hits (hits_count)`

### Table 4: `{$wpdb->prefix}apex_404_logs`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `uri` (TEXT NOT NULL)
  - `uri_hash` (CHAR(32) NOT NULL)
  - `hit_count` (BIGINT(20) UNSIGNED NOT NULL DEFAULT 1)
  - `user_agent` (TEXT DEFAULT NULL)
  - `ip_address` (VARCHAR(45) DEFAULT NULL)
  - `referrer` (TEXT DEFAULT NULL)
  - `first_seen` (DATETIME NOT NULL)
  - `last_seen` (DATETIME NOT NULL)
- **Indexes**:
  - `UNIQUE KEY uk_uri_hash (uri_hash)`
  - `KEY idx_hit_count (hit_count)`
  - `KEY idx_last_seen (last_seen)`

### Table 5: `{$wpdb->prefix}apex_links`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `post_id` (BIGINT(20) UNSIGNED NOT NULL)
  - `target_post_id` (BIGINT(20) UNSIGNED DEFAULT NULL)
  - `url` (TEXT NOT NULL)
  - `url_hash` (CHAR(32) NOT NULL)
  - `anchor_text` (TEXT DEFAULT NULL)
  - `link_type` (VARCHAR(20) NOT NULL DEFAULT 'internal')
  - `is_nofollow` (TINYINT(1) NOT NULL DEFAULT 0)
  - `created_at` (DATETIME NOT NULL)
- **Indexes**:
  - `KEY idx_post_id (post_id)`
  - `KEY idx_target_post_id (target_post_id)`
  - `KEY idx_url_hash (url_hash)`
  - `KEY idx_link_type (link_type)`

### Table 6: `{$wpdb->prefix}apex_image_history`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `attachment_id` (BIGINT(20) UNSIGNED NOT NULL)
  - `original_size` (BIGINT(20) UNSIGNED NOT NULL)
  - `optimized_size` (BIGINT(20) UNSIGNED NOT NULL)
  - `savings_bytes` (BIGINT(20) UNSIGNED NOT NULL)
  - `format_served` (VARCHAR(10) NOT NULL)
  - `lossy` (TINYINT(1) NOT NULL DEFAULT 1)
  - `created_at` (DATETIME NOT NULL)
- **Indexes**:
  - `UNIQUE KEY uk_attachment_id (attachment_id)`
  - `KEY idx_format_served (format_served)`

### Table 7: `{$wpdb->prefix}apex_analytics`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `object_id` (BIGINT(20) UNSIGNED NOT NULL)
  - `object_type` (VARCHAR(32) NOT NULL)
  - `date` (DATE NOT NULL)
  - `clicks` (INT(11) UNSIGNED NOT NULL DEFAULT 0)
  - `impressions` (INT(11) UNSIGNED NOT NULL DEFAULT 0)
  - `ctr` (DECIMAL(5,4) UNSIGNED NOT NULL DEFAULT 0.0000)
  - `position` (DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0.00)
- **Indexes**:
  - `UNIQUE KEY uk_object_date (object_id, object_type, date)`
  - `KEY idx_date (date)`
  - `KEY idx_clicks (clicks)`
  - `KEY idx_position (position)`

### Table 8: `{$wpdb->prefix}apex_rank_tracking`
- **Primary Key**: `id` (BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT)
- **Columns**:
  - `keyword` (VARCHAR(191) NOT NULL)
  - `target_url` (VARCHAR(255) NOT NULL)
  - `current_position` (INT(11) UNSIGNED DEFAULT NULL)
  - `previous_position` (INT(11) UNSIGNED DEFAULT NULL)
  - `best_position` (INT(11) UNSIGNED DEFAULT NULL)
  - `last_checked` (DATETIME NOT NULL)
  - `history` (LONGTEXT DEFAULT NULL)
- **Indexes**:
  - `UNIQUE KEY uk_keyword_url (keyword, target_url)`
  - `KEY idx_current_position (current_position)`
  - `KEY idx_last_checked (last_checked)`

---

## 2. Forensic Query Inspection & Validation

Every SQL query in `src/` has been checked against the DDL definitions above:

| Query Origin | Target Table | Columns Queried / Modified | Mismatch Found | Resolution in Codebase |
| :--- | :--- | :--- | :--- | :--- |
| `IndexableRepository.php` | `apex_indexables` | `object_id, object_type, title, description, permalink, canonical_url, robots` | NONE | Exact match with DDL columns |
| `RedirectManager.php` | `apex_redirects` | `source_url, source_url_hash, target_url, status_code, is_regex, status, hits_count` | NONE | Exact match with DDL columns |
| `RedirectsRestController.php` | `apex_redirects` | `source_url, source_url_hash, target_url, status_code, is_regex, status` | NONE | Exact match with DDL columns |
| `NotFoundRestController.php` | `apex_404_logs` | `id, uri, uri_hash, hit_count, user_agent, ip_address, referrer, first_seen, last_seen` | NONE | Exact match with DDL columns |
| `FourOhFourMonitor.php` | `apex_404_logs` | `uri, uri_hash, hit_count, user_agent, ip_address, referrer, first_seen, last_seen` | NONE | Exact match with DDL columns |
| `RankTracker.php` | `apex_rank_tracking` | `keyword, target_url, current_position, previous_position, best_position, last_checked, history` | NONE | Exact match with DDL columns |
| `AnalyticsRestController.php`| `apex_rank_tracking` | `keyword, target_url, current_position, previous_position, best_position, last_checked` | NONE | Exact match with DDL columns |
| `DatabaseCommand.php` | `posts, comments, options` | Standard WordPress core table cleanup queries | NONE | Prepared queries with safe placeholders |

---

## 3. Database Safety Findings
- **SQL Preparation**: 100% of dynamic queries use `$wpdb->prepare()`.
- **Privilege Compatibility**: TRUNCATE statements have been eliminated in favor of standard `DELETE FROM` queries to support restricted-privilege database users and multi-tenant environments.
- **Index Optimization**: All unique lookup constraints (`uk_source_hash`, `uk_uri_hash`, `uk_object_lookup`, `uk_keyword_url`) have corresponding MD5 hashes or composite indexes to maintain sub-millisecond lookup speeds.
