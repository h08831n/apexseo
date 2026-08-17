# Phase 3 Batch 3: WP-CLI Subsystem — Implementation Report

**Audit Date**: 2026-08-17  
**Subsystem**: WP-CLI Command Interface (`src/CLI/`, `src/Core/CLI/`)  
**Capabilities**: APEX-181 through APEX-190  
**Root Command**: `wp apexseo`

---

## 1. Subsystem Architecture

The WP-CLI subsystem provides an enterprise-grade command line interface for orchestrating Apex SEO operations, automating migrations, clearing caches, generating XML sitemaps, optimizing media attachments, managing redirects, and running deep system diagnostics.

### Base Command Architecture
- **Base Class**: `ApexSEO\CLI\AbstractCliCommand`
- **Output Formats Supported**: `table`, `json`, `csv`, `yaml`, `count`, `ids`
- **Safeguards**:
  - `--dry-run`: Simulates changes without committing database transactions or mutating files.
  - `--force` / `--yes`: Explicit confirmation required for destructive actions.
  - `--batch-size`: Enforces upper-bound chunk limits to prevent PHP memory exhaustion.

---

## 2. Command Implementation Matrix

| Capability ID | Command Signature | Concrete Class | Method | Supported Flags | Return Codes | Description |
|---|---|---|---|---|---|---|
| **APEX-181** | `wp apexseo cache purge [url]` | `CacheCommand` | `purge()` | `--all`, `--tag=<tag>`, `--network` | `0`, `1` | Purges page cache, Redis/Memcached object cache, and edge CDN tags. |
| **APEX-182** | `wp apexseo cache warmup` / `preload` | `CacheCommand` | `warmup()`, `preload()` | `--sitemap=<url>`, `--concurrency=<int>` | `0`, `1` | Pre-warms and caches XML sitemap URLs asynchronously. |
| **APEX-183** | `wp apexseo index rebuild [post_type]` | `IndexCommand` | `rebuild()` | `--batch-size=<int>`, `--dry-run`, `--force`, `--network` | `0`, `1` | Re-indexes all published posts/terms into `wp_apex_indexables`. |
| **APEX-183b**| `wp apexseo index status` | `IndexCommand` | `status()` | `--format=<format>` | `0` | Displays index total count, breakdown, and health status. |
| **APEX-184** | `wp apexseo media optimize [id]` | `MediaCommand` | `optimize()` | `--batch-size=<int>`, `--format=<webp\|avif>`, `--dry-run`, `--force` | `0`, `1` | Generates next-gen WebP/AVIF variants for image attachments. |
| **APEX-184b**| `wp apexseo media restore <id>` | `MediaCommand` | `restore()` | `--force` | `0`, `1` | Restores original uncompressed image files from backup store. |
| **APEX-185** | `wp apexseo redirect add <src> <tgt>` | `RedirectCommand` | `add()` | `[code]`, `--regex` | `0`, `1` | Registers a 301/302 redirect with loop detection and duplicate guards. |
| **APEX-186** | `wp apexseo redirect list` | `RedirectCommand` | `list()` | `--format=<format>`, `--per-page=<int>` | `0` | Lists registered redirects with hit counts and target destinations. |
| **APEX-187** | `wp apexseo db clean` | `DatabaseCommand` | `clean()` | `--days=<int>`, `--dry-run`, `--force`, `--yes` | `0`, `1` | Cleans expired 404 logs, expired transients, and orphaned indexables. |
| **APEX-188** | `wp apexseo migrate run <source>` | `MigrateCommand` | `run()` | `--source=<src>`, `--batch-size=<int>`, `--dry-run`, `--force` | `0`, `1` | Migrates SEO metadata from Yoast, RankMath, AIOSEO, SEOPress, TSF, etc. |
| **APEX-188b**| `wp apexseo migrate rollback <source>`| `MigrateCommand` | `rollback()` | `--force` | `0`, `1` | Restores snapshot prior to migration execution. |
| **APEX-189** | `wp apexseo sitemap rebuild` | `SitemapCommand` | `rebuild()` | `--format=<format>` | `0` | Regenerates all XML sitemaps and purges transient sitemap caches. |
| **APEX-190** | `wp apexseo doctor` / `report status` | `DoctorCommand` | `diagnose()`, `status()` | `--format=<format>` | `0` | Runs deep system health check, table verification, and PHP checks. |
| **APEX-080** | `wp apexseo schema validate [post_id]`| `SchemaCommand` | `validate()` | `--format=<format>`, `--strict` | `0`, `1` | Validates JSON-LD schema against Google Rich Result rules. |

---

## 3. Verification & Test Suite

All 10 commands have been verified in `tests/CliSubsystemTest.php`:
- Command Registration (`testCliManagerCommandRegistration`)
- Index Rebuild & Status (`testIndexCommandRebuildAndStatus`)
- Cache Purge & Warmup (`testCacheCommandPurgeAndWarmup`)
- Media Optimize & Restore (`testMediaCommandOptimizeAndRestore`)
- Redirect Add, Loop Prevention, & List (`testRedirectCommandAddAndList`)
- Database Cleanup & Dry-Run (`testDatabaseCommandClean`)
- Migration Execution & Invalid Source Rejection (`testMigrateCommandRunAndRollback`)
- Sitemap Rebuild (`testSitemapCommandRebuild`)
- System Doctor Diagnostics (`testDoctorCommandDiagnose`)
- Schema Validation & Violation Detection (`testSchemaCommandValidate`)
