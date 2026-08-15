# Authoritative REST API Endpoint Registry & Specification

**Audit Lock Date**: 2026-08-15  
**API Namespace**: `apexseo/v1`  
**Document Purpose**: Definitive registry of all 22 REST API routes, schemas, authorization controls, and rate limits.

---

## 1. REST API Endpoint Summary Matrix (22 Endpoints)

| Endpoint ID | HTTP Method | Route Path | Controller Class | Action Method | Required Capability | Nonce Required | Cacheable |
|---|---|---|---|---|---|---|---|
| **API-01** | `GET` | `/apexseo/v1/settings` | `SettingsRestController` | `get_settings` | `manage_options` | Yes (`wp_rest`) | No |
| **API-02** | `POST` | `/apexseo/v1/settings` | `SettingsRestController` | `update_settings` | `manage_options` | Yes (`wp_rest`) | No |
| **API-03** | `GET` | `/apexseo/v1/meta/{object_type}/{object_id}` | `MetaRestController` | `get_meta` | `edit_posts` / Public | No | Yes (Transients) |
| **API-04** | `POST` | `/apexseo/v1/meta/{object_type}/{object_id}` | `MetaRestController` | `save_meta` | `edit_post` | Yes (`wp_rest`) | No |
| **API-05** | `GET` | `/apexseo/v1/schema` | `SchemaRestController` | `get_schemas` | `edit_posts` | Yes (`wp_rest`) | No |
| **API-06** | `POST` | `/apexseo/v1/schema` | `SchemaRestController` | `create_schema` | `manage_options` | Yes (`wp_rest`) | No |
| **API-07** | `PUT` | `/apexseo/v1/schema/{id}` | `SchemaRestController` | `update_schema` | `manage_options` | Yes (`wp_rest`) | No |
| **API-08** | `DELETE` | `/apexseo/v1/schema/{id}` | `SchemaRestController` | `delete_schema` | `manage_options` | Yes (`wp_rest`) | No |
| **API-09** | `GET` | `/apexseo/v1/redirects` | `RedirectsRestController` | `get_redirects` | `manage_options` | Yes (`wp_rest`) | No |
| **API-10** | `POST` | `/apexseo/v1/redirects` | `RedirectsRestController` | `create_redirect` | `manage_options` | Yes (`wp_rest`) | No |
| **API-11** | `PUT` | `/apexseo/v1/redirects/{id}` | `RedirectsRestController` | `update_redirect` | `manage_options` | Yes (`wp_rest`) | No |
| **API-12** | `DELETE` | `/apexseo/v1/redirects/{id}` | `RedirectsRestController` | `delete_redirect` | `manage_options` | Yes (`wp_rest`) | No |
| **API-13** | `GET` | `/apexseo/v1/monitor/404` | `NotFoundRestController` | `get_404_logs` | `manage_options` | Yes (`wp_rest`) | No |
| **API-14** | `DELETE` | `/apexseo/v1/monitor/404` | `NotFoundRestController` | `clear_404_logs` | `manage_options` | Yes (`wp_rest`) | No |
| **API-15** | `GET` | `/apexseo/v1/links/suggestions` | `LinksRestController` | `get_suggestions` | `edit_posts` | Yes (`wp_rest`) | Yes (Memory) |
| **API-16** | `GET` | `/apexseo/v1/analytics/overview` | `AnalyticsRestController` | `get_overview` | `manage_options` | Yes (`wp_rest`) | Yes (Transients) |
| **API-17** | `GET` | `/apexseo/v1/analytics/rank-tracker` | `AnalyticsRestController` | `get_rank_tracker` | `manage_options` | Yes (`wp_rest`) | Yes (Transients) |
| **API-18** | `POST` | `/apexseo/v1/cache/purge` | `CacheRestController` | `purge_cache` | `manage_options` | Yes (`wp_rest`) | No |
| **API-19** | `POST` | `/apexseo/v1/cache/preload` | `CacheRestController` | `trigger_preload` | `manage_options` | Yes (`wp_rest`) | No |
| **API-20** | `POST` | `/apexseo/v1/media/optimize` | `MediaRestController` | `optimize_single` | `upload_files` | Yes (`wp_rest`) | No |
| **API-21** | `POST` | `/apexseo/v1/media/bulk-optimize`| `MediaRestController` | `bulk_optimize` | `manage_options` | Yes (`wp_rest`) | No |
| **API-22** | `POST` | `/apexseo/v1/migration/run` | `MigrationRestController` | `execute_migration`| `manage_options` | Yes (`wp_rest`) | No |

---

## 2. Granular Endpoint Specifications

### `GET /apexseo/v1/settings` & `POST /apexseo/v1/settings`
- **Controller**: `src/API/SettingsRestController.php`
- **Request Body (POST)**: JSON containing `general`, `titles_meta`, `sitemaps`, `performance`, `media`, `analytics` config trees.
- **Validation**: Schema-driven validation using `rest_validate_value_from_schema()`.
- **Response**: `{ "success": true, "settings": { ... }, "updated_at": "2026-08-15T12:00:00Z" }`.

### `GET /apexseo/v1/meta/{object_type}/{object_id}`
- **Controller**: `src/API/MetaRestController.php`
- **Parameters**: `object_type` (`post` | `term` | `user`), `object_id` (integer).
- **Public Headless Access**: When configured in settings, public GET requests return title, description, canonical, robots directives, OpenGraph, Twitter card tags, and fully compiled `@graph` JSON-LD schema array.

### `POST /apexseo/v1/cache/purge`
- **Controller**: `src/API/CacheRestController.php`
- **Request Body**: `{ "type": "all" | "post" | "urls", "targets": [142, "/contact/"] }`.
- **Response**: `{ "success": true, "purged_count": 14, "duration_ms": 1.2 }`.
- **Error Codes**: `400 Bad Request`, `403 Forbidden`, `500 File System Error`.

### `POST /apexseo/v1/migration/run`
- **Controller**: `src/API/MigrationRestController.php`
- **Request Body**: `{ "source": "yoast" | "rank_math" | "aioseo" | "seopress" | "tsf" | "wp_rocket" | "litespeed" | "redirection", "batch_size": 500, "offset": 0 }`.
- **Response**: `{ "status": "processing" | "completed", "migrated_records": 500, "total_records": 2450, "next_offset": 500 }`.
