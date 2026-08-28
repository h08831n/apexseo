# APEX SEO — Unified WordPress SEO, Schema, AI, Performance, Cache & Analytics Platform

Apex SEO is an enterprise-grade WordPress SEO and Search Intelligence Platform written entirely in modern PHP (7.4 - 8.3+), adhering to PSR-4 standards, strict type safety, WordPress Coding Standards, and comprehensive automated test suites.

## Repository Structure

```
├── apexseo.php             # Main plugin entry point & bootstrap hook
├── uninstall.php           # Clean lifecycle teardown & table deletion
├── composer.json           # PSR-4 Autoloading and package configuration
├── phpunit.runtime.xml     # PHPUnit test runner configuration
├── src/                    # Production plugin source code
│   ├── AI/                 # LLMs.txt generation, AI content suggestions, metadata
│   ├── API/                # REST API routers and controllers (12 subsystems)
│   ├── Analytics/          # Search console, traffic tracking, and ranking data
│   ├── CLI/                # WP-CLI commands and subcommands
│   ├── Core/               # DI container, DB manager, migrations, logging, security
│   ├── Media/              # Image SEO, EXIF, WebP conversion, attachments
│   ├── Performance/        # Critical CSS, script manager, caching, instant page
│   ├── SEO/                # Title/meta, indexables, OpenGraph, Twitter, canonicals, sitemaps
│   └── Schema/             # JSON-LD graph builder, validator, and schema generator
├── tests/                  # Automated PHPUnit & integration test suites
│   ├── integration/        # Live WordPress core runtime integration test cases
│   ├── TestCase.php        # Base test case framework
│   ├── bootstrap.php       # Test bootstrap environment
│   └── *.php               # 28 architectural and functional test suites
├── docker/                 # Real WordPress + MySQL + WP-CLI Docker environment
├── docker-compose.runtime.yml # Runtime container orchestration
└── .github/workflows/      # Continuous Integration workflows for real WordPress runtime
```

## Requirements

- **PHP**: >= 7.4 (Tested on PHP 8.1, 8.2, 8.3)
- **WordPress**: >= 6.2 (Tested on WordPress 6.6, 6.7)
- **MySQL / MariaDB**: MySQL 8.0+ / MariaDB 10.5+

## Installation

1. Clone or download the repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/h08831n/apexseo.git
   ```
2. Activate the plugin via WP-CLI or the WordPress Admin:
   ```bash
   wp plugin activate apexseo
   ```

## Running Tests

### Standalone Test Suite
```bash
php tests/run_all.php
```

### Real WordPress Runtime CI Testing
Run the automated Docker Compose testing stack:
```bash
docker compose -f docker-compose.runtime.yml up -d --build
docker compose -f docker-compose.runtime.yml exec wordpress bash /var/www/html/scripts/runtime/setup-wordpress.sh
docker compose -f docker-compose.runtime.yml exec wordpress bash /var/www/html/scripts/runtime/run-all-runtime-tests.sh
```
