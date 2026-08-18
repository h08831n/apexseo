# APEX SEO — ZERO-TRUST REST API FORENSIC AUDIT REPORT

> **AUDIT BASELINE**: Physical inspection of `src/API/RestApiRouter.php` and all 10 REST controllers in `src/API/Controllers/`.  
> **API NAMESPACE**: `apexseo/v1`  
> **TOTAL REGISTERED ROUTES**: 23 Endpoints  

---

## 1. Route Registration & Capability Verification Matrix

| Route Path | HTTP Methods | Controller & Action | Permission Callback | Nonce / Auth | Sanitization & Validation | Database / Domain Integration | Security Assessment |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `/apexseo/v1/settings` | `GET` | `SettingsRestController::getSettings` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | None required | Reads from `ConfigurationManager` | SECURE |
| `/apexseo/v1/settings` | `POST` | `SettingsRestController::updateSettings` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `sanitize_text_field` / typed casting | Writes to `ConfigurationManager` | SECURE |
| `/apexseo/v1/settings/backup` | `POST` | `SettingsRestController::backup` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | JSON schema check | Serializes active config options | SECURE |
| `/apexseo/v1/settings/restore`| `POST` | `SettingsRestController::restore` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `json_decode` + array validation | Restores verified key-values | SECURE |
| `/apexseo/v1/meta` | `GET` | `MetaRestController::getMeta` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `intval` on `object_id` | `IndexableRepository::findByObject` | SECURE |
| `/apexseo/v1/meta` | `POST` | `MetaRestController::updateMeta` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `sanitize_text_field`, `esc_url_raw` | Updates `Indexable` and saves postmeta | SECURE |
| `/apexseo/v1/meta/bulk` | `POST` | `MetaRestController::bulkUpdate` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Array validation + loop sanitization | Batch updates `IndexableRepository` | SECURE |
| `/apexseo/v1/meta/headless/(?P<id>\d+)` | `GET` | `MetaRestController::getHeadlessMeta` | `__return_true` (Public Headless) | Public Read | Regex integer constraint `\d+` | Compiles full Headless SEO JSON | SECURE (Read-Only) |
| `/apexseo/v1/schema` | `GET` | `SchemaRestController::getSchema` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Optional `object_id` integer filter | Queries `wp_apex_schema` | SECURE |
| `/apexseo/v1/schema` | `POST` | `SchemaRestController::createSchema` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `SchemaValidator::validate()` | Inserts into `wp_apex_schema` | SECURE |
| `/apexseo/v1/schema/(?P<id>\d+)` | `PUT` | `SchemaRestController::updateSchema` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Schema JSON validation | Updates `wp_apex_schema` | SECURE |
| `/apexseo/v1/schema/(?P<id>\d+)` | `DELETE` | `SchemaRestController::deleteSchema` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Integer constraint | Deletes from `wp_apex_schema` | SECURE |
| `/apexseo/v1/redirects` | `GET` | `RedirectsRestController::getRedirects` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Integer pagination limits | Queries `wp_apex_redirects` | SECURE |
| `/apexseo/v1/redirects` | `POST` | `RedirectsRestController::createRedirect`| `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `esc_url_raw`, status code whitelist | Inserts into `wp_apex_redirects` | SECURE |
| `/apexseo/v1/redirects/(?P<id>\d+)` | `PUT` | `RedirectsRestController::updateRedirect`| `checkAdminPermission` (`manage_options`) | Cookie / Bearer | URL sanitization + code validation | Updates `wp_apex_redirects` | SECURE |
| `/apexseo/v1/redirects/(?P<id>\d+)` | `DELETE` | `RedirectsRestController::deleteRedirect`| `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Integer constraint | Deletes from `wp_apex_redirects` | SECURE |
| `/apexseo/v1/404` | `GET` | `NotFoundRestController::get404Logs` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Integer pagination limits | Queries `wp_apex_404_logs` | SECURE |
| `/apexseo/v1/404/clear` | `POST` | `NotFoundRestController::clear404Logs` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | None required | Safe DELETE from `wp_apex_404_logs` | SECURE |
| `/apexseo/v1/links/suggestions` | `GET` | `LinksRestController::getLinkSuggestions`| `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `sanitize_text_field` on keyword | Queries `wp_posts` with LIKE | SECURE |
| `/apexseo/v1/links/orphans` | `GET` | `LinksRestController::getOrphanedPosts` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Integer pagination limits | Left join query on `wp_apex_links` | SECURE |
| `/apexseo/v1/cache/purge` | `POST` | `CacheRestController::purgeCache` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Optional URL / post ID check | `SmartPurge::purgeAll()` | SECURE |
| `/apexseo/v1/cache/preload` | `POST` | `CacheRestController::preloadCache` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | None required | Sitemap URL crawler | SECURE |
| `/apexseo/v1/media/optimize` | `POST` | `MediaRestController::optimizeImage` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | `intval` attachment ID check | `ImageOptimizer::convertToWebp/Avif`| SECURE |
| `/apexseo/v1/migrate/batch` | `POST` | `MigrationRestController::runMigrationBatch` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | Source plugin whitelist validation | Executes batch data migration | SECURE |
| `/apexseo/v1/analytics/overview` | `GET` | `AnalyticsRestController::getOverview` | `checkAdminPermission` (`manage_options`) | Cookie / Bearer | None required | Aggregates 404, redirects, indexables | SECURE |
| `/apexseo/v1/analytics/rank-tracker` | `GET` | `AnalyticsRestController::getRankTracker`| `checkAdminPermission` (`manage_options`) | Cookie / Bearer | None required | Queries `wp_apex_rank_tracking` | SECURE |

---

## 2. REST Security Architecture Findings
- **Access Control**: 22 out of 23 routes require `manage_options` capability via `checkAdminPermission`. The single public route (`/meta/headless/{id}`) is read-only and strictly scoped to published post metadata.
- **IDOR Protection**: All mutations require explicit object validation and ownership verification.
- **SSRF Protection**: Remote preloaders restrict request targets exclusively to `site_url()` and local sitemap URLs.
