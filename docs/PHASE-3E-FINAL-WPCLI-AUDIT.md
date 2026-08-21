# APEX SEO — PHASE 3E FINAL WP-CLI AUDIT REPORT

**Audit Date**: 2026-08-21 06:46:25 UTC
**Total Command Suites**: 10 Suites

| Command | Subcommand | Class Handler | Exit Code | Execution Time | Status |
| :--- | :--- | :--- | :---: | :---: | :---: |
| `wp apexseo index` | `status --format=json` | `ApexSEO\CLI\IndexCommand` | 127 | 2.5041ms | `FAILED` |
| `wp apexseo cache` | `purge --url=https://example.com/test/ --dry-run` | `ApexSEO\CLI\CacheCommand` | 127 | 3.166ms | `FAILED` |
| `wp apexseo media` | `optimize --dry-run --batch-size=10` | `ApexSEO\CLI\MediaCommand` | 127 | 3.315ms | `FAILED` |
| `wp apexseo redirect` | `list --format=json` | `ApexSEO\CLI\RedirectCommand` | 127 | 3.4189ms | `FAILED` |
| `wp apexseo db` | `clean --dry-run` | `ApexSEO\CLI\DatabaseCommand` | 127 | 2.326ms | `FAILED` |
| `wp apexseo migrate` | `run yoast --dry-run` | `ApexSEO\CLI\MigrateCommand` | 127 | 2.6009ms | `FAILED` |
| `wp apexseo sitemap` | `rebuild --dry-run` | `ApexSEO\CLI\SitemapCommand` | 127 | 2.739ms | `FAILED` |
| `wp apexseo doctor` | `status --format=json` | `ApexSEO\CLI\DoctorCommand` | 127 | 2.2371ms | `FAILED` |
| `wp apexseo report` | `status --format=json` | `ApexSEO\CLI\ReportCommand` | 127 | 1.837ms | `FAILED` |
| `wp apexseo schema` | `validate --format=json` | `ApexSEO\CLI\SchemaCommand` | 127 | 2.7661ms | `FAILED` |
