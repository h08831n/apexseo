# APEX SEO — PHASE 3E FINAL REST API AUDIT REPORT

**Audit Date**: 2026-08-21 06:46:25 UTC
**Total Routes Audited**: 23 Routes

| Method | Path | Callback | Unauth Status | Malformed JSON | SQLi Payload | Security Guard |
| :---: | :--- | :--- | :---: | :---: | :---: | :---: |
| `GET` | `/apexseo/v1/status` | `StatusRestController::getStatus` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `GET` | `/apexseo/v1/settings` | `SettingsRestController::getSettings` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `POST` | `/apexseo/v1/settings` | `SettingsRestController::updateSettings` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `POST` | `/apexseo/v1/settings/reset` | `SettingsRestController::resetSettings` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `GET` | `/apexseo/v1/meta` | `MetaRestController::getMeta` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `POST` | `/apexseo/v1/meta` | `MetaRestController::updateMeta` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `POST` | `/apexseo/v1/meta/bulk` | `MetaRestController::bulkUpdateMeta` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `GET` | `/apexseo/v1/schema` | `SchemaRestController::getSchema` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `POST` | `/apexseo/v1/schema` | `SchemaRestController::updateSchema` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `POST` | `/apexseo/v1/schema/validate` | `SchemaRestController::validateSchema` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `GET` | `/apexseo/v1/redirects` | `RedirectsRestController::getRedirects` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `POST` | `/apexseo/v1/redirects` | `RedirectsRestController::createRedirect` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `DELETE` | `/apexseo/v1/redirects/1` | `RedirectsRestController::deleteRedirect` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `GET` | `/apexseo/v1/404` | `NotFoundRestController::getLogs` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `POST` | `/apexseo/v1/404/clear` | `NotFoundRestController::clearLogs` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `GET` | `/apexseo/v1/links` | `LinksRestController::getLinks` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `POST` | `/apexseo/v1/links/rebuild` | `LinksRestController::rebuildLinks` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `GET` | `/apexseo/v1/analytics` | `AnalyticsRestController::getAnalytics` | HTTP 200 | HTTP 200 | HTTP 200 | `OPEN_OR_PUBLIC` |
| `POST` | `/apexseo/v1/analytics/rank-track` | `AnalyticsRestController::trackRank` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `POST` | `/apexseo/v1/cache/purge` | `CacheRestController::purgeCache` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `POST` | `/apexseo/v1/cache/preload` | `CacheRestController::preloadCache` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `POST` | `/apexseo/v1/media/optimize` | `MediaRestController::optimizeMedia` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
| `POST` | `/apexseo/v1/migration/import` | `MigrationRestController::importData` | HTTP 404 | HTTP 404 | HTTP 404 | `VERIFIED_SECURE` |
