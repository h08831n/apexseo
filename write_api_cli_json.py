#!/usr/bin/env python3
import os
import json

DOCS_DIR = "docs"

rest_routes = [
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/status",
        "methods": "GET",
        "controller": "RestApiRouter",
        "action": "getStatus",
        "permission_callback": "restAdminPermissionCallback",
        "sanitization": "None",
        "validation": "None"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/settings",
        "methods": "GET",
        "controller": "SettingsRestController",
        "action": "getSettings",
        "permission_callback": "checkAdminPermission",
        "sanitization": "None",
        "validation": "None"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/settings",
        "methods": "POST",
        "controller": "SettingsRestController",
        "action": "updateSettings",
        "permission_callback": "checkAdminPermission",
        "sanitization": "sanitize_text_field",
        "validation": "JSON array structure validation"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/meta/(?P<object_type>[a-z_-]+)/(?P<object_id>\\d+)",
        "methods": "GET",
        "controller": "MetaRestController",
        "action": "getMeta",
        "permission_callback": "checkAdminPermission",
        "sanitization": "intval, sanitize_key",
        "validation": "Regex \\d+, [a-z_-]+"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/meta/(?P<object_type>[a-z_-]+)/(?P<object_id>\\d+)",
        "methods": "POST",
        "controller": "MetaRestController",
        "action": "updateMeta",
        "permission_callback": "checkAdminPermission",
        "sanitization": "sanitize_text_field, esc_url_raw",
        "validation": "Regex \\d+, [a-z_-]+"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/schema",
        "methods": "GET",
        "controller": "SchemaRestController",
        "action": "getSchema",
        "permission_callback": "checkAdminPermission",
        "sanitization": "intval",
        "validation": "Optional object_id"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/schema",
        "methods": "POST",
        "controller": "SchemaRestController",
        "action": "createSchema",
        "permission_callback": "checkAdminPermission",
        "sanitization": "SchemaValidator",
        "validation": "SchemaValidator::validate"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/schema/(?P<id>\\d+)",
        "methods": "PUT",
        "controller": "SchemaRestController",
        "action": "updateSchema",
        "permission_callback": "checkAdminPermission",
        "sanitization": "SchemaValidator",
        "validation": "SchemaValidator::validate"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/schema/(?P<id>\\d+)",
        "methods": "DELETE",
        "controller": "SchemaRestController",
        "action": "deleteSchema",
        "permission_callback": "checkAdminPermission",
        "sanitization": "intval",
        "validation": "Regex \\d+"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/redirects",
        "methods": "GET",
        "controller": "RedirectsRestController",
        "action": "getRedirects",
        "permission_callback": "checkAdminPermission",
        "sanitization": "intval",
        "validation": "Limit & offset"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/redirects",
        "methods": "POST",
        "controller": "RedirectsRestController",
        "action": "createRedirect",
        "permission_callback": "checkAdminPermission",
        "sanitization": "esc_url_raw",
        "validation": "URL format + status code whitelist"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/redirects/(?P<id>\\d+)",
        "methods": "PUT",
        "controller": "RedirectsRestController",
        "action": "updateRedirect",
        "permission_callback": "checkAdminPermission",
        "sanitization": "esc_url_raw",
        "validation": "Regex \\d+"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/redirects/(?P<id>\\d+)",
        "methods": "DELETE",
        "controller": "RedirectsRestController",
        "action": "deleteRedirect",
        "permission_callback": "checkAdminPermission",
        "sanitization": "intval",
        "validation": "Regex \\d+"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/monitor/404",
        "methods": "GET",
        "controller": "NotFoundRestController",
        "action": "get404Logs",
        "permission_callback": "checkAdminPermission",
        "sanitization": "intval",
        "validation": "Pagination limits"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/monitor/404",
        "methods": "DELETE",
        "controller": "NotFoundRestController",
        "action": "clear404Logs",
        "permission_callback": "checkAdminPermission",
        "sanitization": "None",
        "validation": "None"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/links/suggestions",
        "methods": "GET",
        "controller": "LinksRestController",
        "action": "getLinkSuggestions",
        "permission_callback": "checkAdminPermission",
        "sanitization": "sanitize_text_field",
        "validation": "Keyword string validation"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/analytics/overview",
        "methods": "GET",
        "controller": "AnalyticsRestController",
        "action": "getOverview",
        "permission_callback": "checkAdminPermission",
        "sanitization": "None",
        "validation": "None"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/analytics/rank-tracker",
        "methods": "GET",
        "controller": "AnalyticsRestController",
        "action": "getRankTracker",
        "permission_callback": "checkAdminPermission",
        "sanitization": "None",
        "validation": "None"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/cache/purge",
        "methods": "POST",
        "controller": "CacheRestController",
        "action": "purgeCache",
        "permission_callback": "checkAdminPermission",
        "sanitization": "intval, esc_url_raw",
        "validation": "Optional post_id or url"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/cache/preload",
        "methods": "POST",
        "controller": "CacheRestController",
        "action": "preloadCache",
        "permission_callback": "checkAdminPermission",
        "sanitization": "None",
        "validation": "None"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/media/optimize",
        "methods": "POST",
        "controller": "MediaRestController",
        "action": "optimizeImage",
        "permission_callback": "checkUploadPermission",
        "sanitization": "intval",
        "validation": "Attachment ID integer"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/media/bulk-optimize",
        "methods": "POST",
        "controller": "MediaRestController",
        "action": "bulkOptimize",
        "permission_callback": "checkUploadPermission",
        "sanitization": "intval",
        "validation": "Batch size integer"
    },
    {
        "namespace": "apexseo/v1",
        "route": "/apexseo/v1/migration/run",
        "methods": "POST",
        "controller": "MigrationRestController",
        "action": "runMigrationBatch",
        "permission_callback": "checkAdminPermission",
        "sanitization": "sanitize_text_field",
        "validation": "Source plugin whitelist"
    }
]

cli_commands = [
    {
        "root": "wp apexseo",
        "command": "index",
        "full_command": "wp apexseo index",
        "class": "IndexCommand",
        "subcommands": ["reindex", "status", "clean"],
        "shortdesc": "Manage and rebuild Apex SEO indexables."
    },
    {
        "root": "wp apexseo",
        "command": "cache",
        "full_command": "wp apexseo cache",
        "class": "CacheCommand",
        "subcommands": ["purge", "preload", "status"],
        "shortdesc": "Purge, warmup, and preload cache layers."
    },
    {
        "root": "wp apexseo",
        "command": "media",
        "full_command": "wp apexseo media",
        "class": "MediaCommand",
        "subcommands": ["optimize", "restore", "status"],
        "shortdesc": "Optimize and restore WebP/AVIF media attachments."
    },
    {
        "root": "wp apexseo",
        "command": "redirect",
        "full_command": "wp apexseo redirect",
        "class": "RedirectCommand",
        "subcommands": ["add", "list", "delete", "export"],
        "shortdesc": "Add and list 301/302 URL redirection rules."
    },
    {
        "root": "wp apexseo",
        "command": "db",
        "full_command": "wp apexseo db",
        "class": "DatabaseCommand",
        "subcommands": ["clean", "optimize", "check"],
        "shortdesc": "Clean old 404 logs, expired transients, and optimize database."
    },
    {
        "root": "wp apexseo",
        "command": "migrate",
        "full_command": "wp apexseo migrate",
        "class": "MigrateCommand",
        "subcommands": ["run", "status", "rollback"],
        "shortdesc": "Import SEO metadata and redirects from legacy SEO plugins."
    },
    {
        "root": "wp apexseo",
        "command": "sitemap",
        "full_command": "wp apexseo sitemap",
        "class": "SitemapCommand",
        "subcommands": ["rebuild", "status", "ping"],
        "shortdesc": "Rebuild and cache XML sitemaps."
    },
    {
        "root": "wp apexseo",
        "command": "doctor",
        "full_command": "wp apexseo doctor",
        "class": "DoctorCommand",
        "subcommands": ["status", "check", "fix"],
        "shortdesc": "Diagnose system health and database integrity."
    },
    {
        "root": "wp apexseo",
        "command": "report",
        "full_command": "wp apexseo report",
        "class": "DoctorCommand",
        "subcommands": ["generate", "export"],
        "shortdesc": "Output system report and environment diagnostics."
    },
    {
        "root": "wp apexseo",
        "command": "schema",
        "full_command": "wp apexseo schema",
        "class": "SchemaCommand",
        "subcommands": ["validate", "generate", "list"],
        "shortdesc": "Validate JSON-LD structured data schemas."
    }
]

with open(os.path.join(DOCS_DIR, "REST-ROUTE-MATRIX-AUTHORITATIVE.json"), "w", encoding="utf-8") as f:
    json.dump(rest_routes, f, indent=2)

with open(os.path.join(DOCS_DIR, "WPCLI-MATRIX-AUTHORITATIVE.json"), "w", encoding="utf-8") as f:
    json.dump(cli_commands, f, indent=2)

print(f"Wrote {len(rest_routes)} REST routes and {len(cli_commands)} WP-CLI commands.")
