# APEX SEO — ZERO-TRUST WP-CLI FORENSIC AUDIT REPORT

> **AUDIT BASELINE**: Physical inspection of `src/Core/CLI/CliManager.php` and all 10 command classes in `src/CLI/`.  
> **COMMAND ROOT**: `wp apex` (and alias `wp apexseo`)  
> **TOTAL COMMAND SUITES**: 10 Executable Commands  

---

## 1. WP-CLI Command Inspection & Validation Matrix

| Command | Subcommands / Flags | Source Class & Method | Database Interaction | Dry-Run Behavior | Exit Codes & Errors | Test File | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `wp apex cache purge` | `[--all] [--post_id=<id>] [--url=<url>]` | `CacheCommand::purge()` | Filesystem cache directory | N/A | `WP_CLI::success`, `WP_CLI::error` | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex cache preload`| `[--sitemap=<url>] [--concurrency=<n>]` | `CacheCommand::preload()` | Filesystem cache writer | N/A | Progress tracking output | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex index reindex`| `[--post_type=<type>] [--batch-size=<n>]` | `IndexCommand::reindex()` | `wp_apex_indexables` | N/A | Batch count logging | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex media optimize`| `[--id=<id>] [--all] [--format=<webp\|avif>]`| `MediaCommand::optimize()` | `wp_apex_image_history` | N/A | Conversion stats output | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex redirect add` | `<source> <target> [--code=<code>] [--regex]` | `RedirectCommand::add()` | `wp_apex_redirects` | N/A | Source duplicate check | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex redirect list`| `[--format=<table\|json\|csv>] [--limit=<n>]`| `RedirectCommand::list()` | `wp_apex_redirects` | N/A | Formatted CLI output | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex db clean` | `[--all] [--revisions] [--transients] [--dry-run]` | `DatabaseCommand::clean()` | `wp_posts, wp_comments, wp_options` | Supported (`--dry-run` calculates counts without deletion) | Clean summary table | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex migrate run` | `--source=<yoast\|rankmath\|aioseo>` | `MigrateCommand::run()` | `wp_apex_indexables, wp_apex_redirects` | N/A | Step-by-step progress | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex sitemap rebuild`| `[--type=<posts\|pages\|taxonomies>]` | `SitemapCommand::rebuild()` | Filesystem cache / transient | N/A | XML verification output | `CliSubsystemTest.php` | IMPLEMENTED |
| `wp apex doctor` | `[--format=<table\|json>]` | `DoctorCommand::status()` | Health checks on 8 custom tables | N/A | Green/Red diagnostic report | `CliSubsystemTest.php` | IMPLEMENTED |

---

## 2. WP-CLI Behavioral Findings
- **Registration**: All commands registered under `wp apex` root namespace via `WP_CLI::add_command()` during `cli_init` hook.
- **Safety**: Destructive operations like `wp apex db clean` support `--dry-run` to preview removable records before execution.
- **Output Formats**: Tabular, JSON, and standard text output supported across commands.
