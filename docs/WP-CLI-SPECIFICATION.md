# WP-CLI Command Interface Specification

**Audit Reference**: WP-CLI Standard Command Architecture  
**Namespace**: `wp apexseo <command> [subcommand] [arguments] [flags]`

---

## 1. Global Flags & Conventions

Every command supports standard WP-CLI global flags:
- `--format=<format>`: Table format output (`table`, `json`, `csv`, `yaml`, `count`, `ids`). Default: `table`.
- `--dry-run`: Simulates the command without persisting database changes or deleting files.
- `--network`: Executes across all blogs in a WordPress Multisite network.
- `--batch-size=<int>`: Configures chunk size for bulk database operations (Default: `500`).
- `--force`: Bypasses confirmation prompts.

---

## 2. Exhaustive Command Inventory

| Command | Subcommand | Arguments | Supported Flags | Return Codes | Concrete Behavior | Example Usage |
|---|---|---|---|---|---|---|
| `wp apexseo index` | `rebuild` | `[post_type]` | `--batch-size=<int>`, `--dry-run`, `--force`, `--network` | `0` (Success), `1` (Error) | Re-indexes all published posts/terms into `wp_apex_indexables` | `wp apexseo index rebuild post --batch-size=250` |
| `wp apexseo index` | `status` | None | `--format=<format>` | `0` (Success) | Displays total indexed count, pending count, and table size | `wp apexseo index status --format=json` |
| `wp apexseo cache` | `purge` | `[url]` | `--all`, `--tag=<tag>`, `--network` | `0` (Success), `1` (Error) | Flushes full page cache, Redis object cache, or edge CDN cache | `wp apexseo cache purge --all` |
| `wp apexseo cache` | `warmup` | None | `--sitemap=<url>`, `--concurrency=<int>` | `0` (Success), `1` (Error) | Pre-loads and caches all URLs parsed from XML sitemap | `wp apexseo cache warmup --concurrency=5` |
| `wp apexseo media` | `optimize` | `[attachment_id]` | `--batch-size=<int>`, `--format=<webp|avif>`, `--dry-run`, `--force` | `0` (Success), `1` (Error) | Generates WebP/AVIF variants for media library attachments | `wp apexseo media optimize --batch-size=100 --format=webp` |
| `wp apexseo media` | `restore` | `[attachment_id]` | `--force` | `0` (Success), `1` (Error) | Restores original uncompressed image files from backup store | `wp apexseo media restore 452 --force` |
| `wp apexseo migrate` | `run` | `<source>` | `--source=<yoast|rankmath|aioseo|seopress|tsf|wprocket|litespeed|redirection>`, `--dry-run`, `--force` | `0` (Success), `1` (Error) | Imports all settings, metadata, redirects, and schema from source | `wp apexseo migrate run --source=yoast --dry-run` |
| `wp apexseo migrate` | `rollback`| `<source>` | `--force` | `0` (Success), `1` (Error) | Rolls back settings and metadata from saved migration snapshot | `wp apexseo migrate rollback --source=yoast` |
| `wp apexseo schema` | `validate`| `[post_id]` | `--format=<format>`, `--strict` | `0` (Valid), `1` (Schema Errors) | Validates generated JSON-LD against Google Structured Data schema | `wp apexseo schema validate 124 --format=json` |
| `wp apexseo report` | `status` | None | `--format=<format>` | `0` (Success) | Outputs system diagnostics, PHP extensions, and environment state | `wp apexseo report status --format=yaml` |
