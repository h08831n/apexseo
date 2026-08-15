# Authoritative Performance Budget & Latency Specification

**Audit Lock Date**: 2026-08-15  
**Document Purpose**: Mathematically defensible, component-isolated performance budgets separating PHP runtime overhead from hosting infrastructure and network latency.

---

## 1. Component-Isolated Performance Architecture

A WordPress plugin cannot guarantee global network TTFB because DNS resolution, TLS handshakes, physical edge distances, and hosting hardware CPU speeds are outside software control. 

Apex SEO defines strict budgets strictly within its execution boundaries:

```
Total Request Latency = Network Latency + Web Server Latency + Core WP Latency + [Apex Plugin Overhead]
                                                                                ▲
                                                                                └── Strictly Budgeted Here
```

---

## 2. Exhaustive Runtime Performance Budgets

| Metric | Target Budget | Hard Enforcement Ceiling | Measurement Condition | Architectural Strategy |
|---|---|---|---|---|
| **Static Cache Hit Serving Overhead** | **< 0.5 ms** | **1.0 ms** | Cached GET request served via `advanced-cache.php` | Direct `readfile()` stream from `/wp-content/cache/`; zero database connections, zero WordPress Core bootstrap. |
| **Direct Web Server Static Serving** | **0.0 ms** | **0.0 ms** | Nginx / Apache rewrite rules | Web server serves `.html` / `.html.gz` directly from disk; PHP process is never spawned. |
| **Dynamic Frontend PHP Overhead** | **< 6.0 ms** | **12.0 ms** | Uncached dynamic frontend page render | Single indexed query against `wp_apex_indexables`; pre-compiled template tags; zero runtime reflections. |
| **Frontend Memory Footprint** | **< 2.0 MB** | **3.5 MB** | Dynamic frontend page render | Strict lazy-loading: admin controllers, schema builder, migration engines, and sitemaps are not loaded on frontend. |
| **Database Queries on Cached Request** | **0 Queries** | **0 Queries** | Cache hit | No DB bootstrap. |
| **Database Queries on Dynamic Request** | **Exactly 1 Query** | **1 Query** | Uncached dynamic request | `SELECT * FROM wp_apex_indexables WHERE object_type = %s AND object_id = %d LIMIT 1` (Covered by primary unique key index). |
| **Admin Post Editor Metabox Overhead** | **< 20.0 ms** | **35.0 ms** | Gutenberg / Classic Editor load | Asynchronous REST data fetching for analysis; editor UI rendered via React JS components. |
| **Sitemap Generation Time (1,000 URLs)** | **< 45.0 ms** | **80.0 ms** | XML Sitemap request | Direct streaming cursor SQL query with XML writer buffer; avoids loading full WP_Post objects into memory. |
| **Background Batch Processing** | **< 25.0 s** | **30.0 s** | Per 500-item migration/optimization chunk | Action Scheduler / WP-Cron batching with memory self-monitoring (`memory_get_usage() < 64MB`). |

---

## 3. Database Query Budget & Verification

```
Standard WordPress Request (Without Apex Indexables):
├── Query 1: wp_posts lookup
├── Query 2: wp_postmeta (_yoast_wpseo_title)
├── Query 3: wp_postmeta (_yoast_wpseo_metadesc)
├── Query 4: wp_postmeta (_yoast_wpseo_canonical)
├── Query 5: wp_postmeta (_yoast_wpseo_opengraph-title)
├── Query 6: wp_postmeta (_yoast_wpseo_opengraph-image)
├── Query 7: wp_postmeta (_yoast_wpseo_twitter-title)
├── Query 8: wp_postmeta (schema settings)
└── Total: 8-15 separate postmeta queries per request

Apex SEO Unified Request (With Indexables):
└── Query 1: SELECT * FROM wp_apex_indexables WHERE object_type = 'post' AND object_id = 142 LIMIT 1;
    ├── Returns: title, description, canonical, robots, og, twitter, schema_type, scores in 1 row.
    └── Execution Time: ~0.15ms (Indexed B-Tree lookup).
```

---

## 4. Asset Delivery & Payload Budgets

| Asset Type | Target Payload (Gzip) | Target Payload (Brotli) | Execution Timing |
|---|---|---|---|
| **Frontend LazyLoad Micro-Script** | `< 1.2 KB` | `< 0.9 KB` | Inlined in `<head>` or deferred; zero external HTTP request. |
| **Frontend Instant Click / Prefetch** | `< 0.8 KB` | `< 0.6 KB` | Inlined script; triggers on hover/touchstart with 65ms intent delay. |
| **Admin React Application Bundle** | `< 145 KB` | `< 115 KB` | Loaded only in `admin.php?page=apex-*` and Gutenberg editor. |
