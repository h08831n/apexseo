# Database Architecture & Relational Decision Record

**Audit Reference**: Apex SEO Relational Persistence Layer  
**Database Engine**: MySQL 5.7+ / MariaDB 10.3+ (InnoDB Engine, utf8mb4 collation)

---

## 1. Justified Relational Tables (8 Dedicated Custom Tables)

Every custom table is strictly justified by high write/query volume, complex relational indexing, or high cardinality where `wp_postmeta` and `wp_options` would severely degrade performance:

```
┌───────────────────────────┐     ┌───────────────────────────┐
│    wp_apex_indexables     │     │      wp_apex_schema       │
│  (1 query per page load)  │     │   (Custom JSON Templates) │
└─────────────┬─────────────┘     └─────────────┬─────────────┘
              │                                 │
              ▼                                 ▼
┌───────────────────────────┐     ┌───────────────────────────┐
│     wp_apex_redirects     │     │     wp_apex_404_logs      │
│  (Indexed URL fast-route) │     │ (High-volume error logs)  │
└─────────────┬─────────────┘     └─────────────┬─────────────┘
              │                                 │
              ▼                                 ▼
┌───────────────────────────┐     ┌───────────────────────────┐
│       wp_apex_links       │     │   wp_apex_image_history   │
│  (Relational graph map)   │     │ (Attachment optimization) │
└─────────────┬─────────────┘     └─────────────┬─────────────┘
              │                                 │
              ▼                                 ▼
┌───────────────────────────┐     ┌───────────────────────────┐
│     wp_apex_analytics     │     │   wp_apex_rank_tracking   │
│   (GSC Search Analytics)  │     │   (Time-series positions) │
└───────────────────────────┘     └───────────────────────────┘
```

### 1.1 Table 1: `wp_apex_indexables`
- **Purpose**: Fast-path cache of generated SEO metadata, titles, descriptions, canonical URLs, robots bitmasks, and social card payloads.
- **Why Required**: Reduces 15+ individual `get_post_meta()` queries per page load into 1 indexed primary key query (`object_id`, `object_type`).
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `UNIQUE KEY (object_id, object_type)`, `KEY (seo_score)`, `KEY (primary_focus_keyword(64))`
- **Expected Volume**: Exactly 1 row per Post, Page, CPT, Taxonomy Term, and User Archive. (10,000 posts = 10,000 rows).
- **Cleanup Strategy**: Cascading deletion when post or term is permanently deleted.

### 1.2 Table 2: `wp_apex_schema`
- **Purpose**: Storage of visual schema templates with dynamic variable placeholders and multi-condition rules (ALL/ANY/NOT).
- **Why Required**: Allows complex structured data queries and priority-based sorting without polluting `wp_posts`.
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `KEY (schema_type)`, `KEY (is_active, priority)`
- **Expected Volume**: 10–50 rows per site.

### 1.3 Table 3: `wp_apex_redirects`
- **Purpose**: High-speed routing and redirection lookup table for 301, 302, 307, 410, and 451 status codes.
- **Why Required**: Queried on every incoming 404 or page load. Must have fast B-Tree index on `source_hash`.
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `UNIQUE KEY (source_hash)`, `KEY (status_code)`, `KEY (is_regex)`, `KEY (hits)`
- **Expected Volume**: 100–100,000 rows.

### 1.4 Table 4: `wp_apex_404_logs`
- **Purpose**: Logs high-frequency 404 requests with anonymized IPs, referrers, and hit counters.
- **Why Required**: High write volume would cause table bloat in `wp_options`.
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `UNIQUE KEY (url_hash)`, `KEY (hit_count)`, `KEY (last_accessed)`
- **Retention**: Auto-pruning entries older than 30 days or exceeding 5,000 unique URLs.

### 1.5 Table 5: `wp_apex_links`
- **Purpose**: Relational map of all internal and external links across content, storing source post ID, target URL, target post ID, anchor text, and rel flags (`nofollow`, `sponsored`).
- **Why Required**: Essential for real-time orphan content detection, link count calculations, and broken link identification via relational SQL joins.
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `KEY (source_post_id)`, `KEY (target_post_id)`, `KEY (target_url_hash)`, `KEY (is_internal)`
- **Expected Volume**: 5–50 rows per post (e.g. 50,000 links for a 1,000-post site).

### 1.6 Table 6: `wp_apex_image_history`
- **Purpose**: Tracks optimization history, original file size, compressed size, savings percentage, WebP path, AVIF path, and backup status for Media Library attachments.
- **Why Required**: Avoids polluting `wp_postmeta` with multi-variant compression stats; enables fast sorting and bulk actions in Media Library columns.
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `UNIQUE KEY (attachment_id)`, `KEY (savings_percentage)`, `KEY (status)`
- **Expected Volume**: Exactly 1 row per media image attachment.

### 1.7 Table 7: `wp_apex_analytics`
- **Purpose**: Stores historical Google Search Console performance data (clicks, impressions, CTR, position) grouped by date, page URL, and search query.
- **Why Required**: Time-series analytics data with high cardinality.
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `UNIQUE KEY (page_hash, query_hash, date)`, `KEY (date)`, `KEY (clicks)`, `KEY (impressions)`
- **Retention**: Configurable 90-day or 365-day rolling retention.

### 1.8 Table 8: `wp_apex_rank_tracking`
- **Purpose**: Stores daily tracked keyword positions, historical ranking changes, and SERP movement.
- **Why Required**: Time-series rank tracking requires fast relational queries for sparklines and delta calculations.
- **Primary Key**: `id BIGINT(20) UNSIGNED AUTO_INCREMENT`
- **Indexes**: `UNIQUE KEY (keyword_hash, date)`, `KEY (keyword_id)`, `KEY (position)`, `KEY (date)`

---

## 2. Architectural Rationale for Non-Table Datasets

### 2.1 Why Image Queue Does NOT Require a Custom Table
- **Design**: Image optimization tasks are managed by the unified `QueueInterface`.
- **Implementation**: When Action Scheduler is present (`WooCommerce` or standalone), it routes through `action_scheduler_actions`. When absent, it utilizes standard `wp_options` transients with atomic locking (`add_option('apex_queue_lock', ...)`). This avoids creating a redundant queue table that duplicates WordPress Core or Action Scheduler infrastructure.

### 2.2 Why Audit Logs Do NOT Require a Custom Table
- **Design**: Structured diagnostics and error logs are written directly to a rolling file `/wp-content/cache/apex-audit.log` (protected via `.htaccess` / `web.config`) and surfaced via the Diagnostics UI. This guarantees logs remain write-accessible even when database connections fail.

### 2.3 Why Cache Metadata Does NOT Require a Custom Table
- **Design**: Full page cache metadata (expiration timestamp, vary hash, gzip status) is stored directly as a JSON header inside the static file header or as a companion `.meta` file (e.g. `page.html.meta`), achieving sub-millisecond zero-DB disk cache hits.

---

## 3. Background Queue Architecture Specification

```
┌────────────────────────────────────────────────────────┐
│                     QueueInterface                     │
│    (push, pop, release, clear, get_status, is_locked)   │
└───────────────────────────┬────────────────────────────┘
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
┌───────────────────────────┐ ┌───────────────────────────┐
│   ActionSchedulerQueue    │ │       WpCronQueue         │
│  (Priority, Auto-Retry)   │ │ (Transients, Locks, Cron) │
└───────────────────────────┘ └───────────────────────────┘
```

### Queue Engine Properties:
- **Locking**: Atomic transient locks prevent race conditions and duplicate worker execution.
- **Backoff & Retry**: Exponential backoff with a maximum retry limit of 3 attempts per failed job.
- **Dead-Letter State**: Failed jobs exceeding max attempts are marked `failed` and logged with the full exception stack trace for admin review.
- **Idempotency**: Every job payload is hashed (`md5($payload)`); duplicate jobs with identical payloads are rejected.
