# APEX SEO — Real WordPress CI Runtime Audit & Architecture Report

## 1. Executive Environment Statement

**CRITICAL COMPLIANCE NOTICE**:
In accordance with strict environment evaluation, the local development container operates under user-space containerization (`4.19.0-gvisor`) where nested daemon virtualization (`dockerd`) and unprivileged kernel mounts are physically blocked. Consequently, real runtime verification cannot execute in the local container sandbox without fabricating or simulating results.

To achieve genuine, zero-simulation runtime verification, a complete, reproducible **Real WordPress CI Runtime Environment** has been engineered for native execution on GitHub Actions runners (`ubuntu-latest` with Docker Compose, official WordPress images, MySQL 8.0, PHP 8.1/8.2/8.3, WP-CLI, and curl).

> **Current Local Sandbox Status**: `CODE_IMPLEMENTED`  
> **Current Local Sandbox Runtime Status**: `RUNTIME_NOT_VERIFIED`  
> **CI Runtime Configuration**: `FULLY_CONFIGURED` (Awaiting GitHub Actions runner trigger)

---

## 2. CI Runtime Infrastructure Specification

| Component | Target CI Runtime Specification | Configuration File |
| :--- | :--- | :--- |
| **CI Workflow Engine** | GitHub Actions (`ubuntu-latest`) | `.github/workflows/wordpress-runtime.yml` |
| **Orchestration** | Docker Compose v2 | `docker-compose.runtime.yml` |
| **Web Server / PHP** | Official WordPress Image (`wordpress:php8.2-apache`, `php8.1`) | `docker/wordpress-runtime/Dockerfile.wordpress` |
| **Database Engine** | MySQL 8.0 (`mysql:8.0`) / MariaDB 10.11 | `docker-compose.runtime.yml` |
| **CLI Runtime** | Official WP-CLI (`wordpress:cli-php8.2`) | `docker/wordpress-runtime/Dockerfile.wpcli` |
| **Test Orchestrator** | Shell Test Harness & PHPUnit | `scripts/runtime/run-all-runtime-tests.sh`, `phpunit.runtime.xml` |
| **Mount Points** | `./wp-content/plugins/apexseo` → `/var/www/html/wp-content/plugins/apexseo` | Live bind-mount |

---

## 3. Subsystem Verification Matrix & Test Gate Specifications

### 3.1 Relational Database Verification (`scripts/runtime/verify-db.sh`)
- **Verification Target**: 9 custom locked tables created during plugin lifecycle.
  1. `wp_apex_indexables`: Primary key, `uk_object_lookup`, `idx_permalink_hash`, `idx_seo_score`.
  2. `wp_apex_schema`: JSON-LD storage, conditions, global status.
  3. `wp_apex_redirects`: 301/302 routing rules, MD5 hashing, hit counter.
  4. `wp_apex_404_logs`: `uk_uri_hash`, referer logging, redirection flags.
  5. `wp_apex_links`: Internal/external link graph relationships.
  6. `wp_apex_image_history`: Compression byte savings, WebP/AVIF format tracking.
  7. `wp_apex_analytics`: Search console clicks, impressions, CTR, position.
  8. `wp_apex_rank_tracking`: Keyword position history, search volume.
  9. `wp_apex_content_analysis`: Composite, SEO, and readability scores, 7 analyzer JSON payloads.
- **Assertions**: Table existence, column definitions, index integrity, and real INSERT/SELECT/UPDATE/DELETE transactions.
- **Classification**: `CODE_IMPLEMENTED` | `RUNTIME_NOT_VERIFIED` (Static test passed; live DB execution awaits CI runner).

### 3.2 Real Phase 4 Content Analysis End-to-End Test (`scripts/runtime/verify-phase4.sh`)
- **Execution Path**: Real `wp_insert_post()` → WordPress `save_post` action hook → `SeoModule` → `ContentAnalysisService` → `ContentAnalyzer` → 7 Core Analyzers → Database persistence.
- **Multi-lingual Fixtures**:
  - English Post: Multi-heading (H1-H4), passive voice sentences, transition words, internal & external links, focus keywords.
  - Persian (Farsi) Post: RTL layout, Persian keywords, transition words, readability evaluation.
- **Assertions**: Verified row insertion into `wp_apex_content_analysis`, synchronization into `wp_apex_indexables`, and link extraction into `wp_apex_links`.
- **Classification**: `CODE_IMPLEMENTED` | `RUNTIME_NOT_VERIFIED`.

### 3.3 Real REST API Endpoint Test Suite (`scripts/runtime/verify-rest.sh`)
- **Execution Path**: Live HTTP requests via curl to `http://localhost:8080/wp-json/apexseo/v1/...`.
- **Tested Subsystems**:
  - `GET /status`: Root healthcheck & version.
  - `GET /settings`, `POST /settings`: Configuration persistence.
  - `GET /redirects`, `POST /redirects`: Redirect rule CRUD.
  - `GET /404`: Error log retrieval.
  - `GET /cache/status`, `POST /cache/purge`: Cache clearing.
  - `GET /media/status`: Image compression status.
  - `POST /schema/validate`: JSON-LD syntax validator.
  - `GET /analytics/overview`: Search metrics overview.
  - `GET /analysis/post/{id}`, `POST /analysis/post/{id}`: Live on-demand content analysis.
- **Security Check**: Unauthorized requests without credentials must return HTTP 401/403.
- **Classification**: `CODE_IMPLEMENTED` | `RUNTIME_NOT_VERIFIED`.

### 3.4 Real WP-CLI Command Suite (`scripts/runtime/verify-cli.sh`)
- **Commands Validated**:
  - `wp apexseo` (Root information)
  - `wp apexseo doctor` & `wp apexseo report` (System health)
  - `wp apexseo analysis post <id>`, `all`, `reindex` (Batch content analysis)
  - `wp apexseo cache purge --all`
  - `wp apexseo sitemap rebuild`
  - `wp apexseo schema validate --type=Article`
  - `wp apexseo redirect list`
  - `wp apexseo index rebuild`
  - `wp apexseo db cleanup`
- **Classification**: `CODE_IMPLEMENTED` | `RUNTIME_NOT_VERIFIED`.

### 3.5 Real Frontend HTTP, Phase 5A & 5B Permalinks/Robots (`scripts/runtime/verify-frontend.sh`)
- **Phase 5A Frontend Head Verification**:
  - Real HTML validation for `<title>`, `<meta name="description">`, `<link rel="canonical">`, `property="og:title"`, `name="twitter:card"`, `application/ld+json`.
- **Phase 5B Permalinks & Robots Verification**:
  - Real `/robots.txt` output with AI crawler rules (`GPTBot`, `CCBot`, `ClaudeBot`, `Google-Extended`, `Bytespider`, `PerplexityBot`).
  - Real `/sitemap_index.xml` XML rendering.
  - Real `/llms.txt` AI manifest delivery.
  - Real `X-Robots-Tag: noindex` headers on 404 error responses, search queries (`/?s=...`), and RSS feeds (`/feed/`).
  - Category base stripping 301 redirection (`/category/cloud-computing/` → `/cloud-computing/`).
- **Classification**: `CODE_IMPLEMENTED` | `RUNTIME_NOT_VERIFIED`.

### 3.6 Security Failure Injection Suite (`scripts/runtime/verify-security.sh`)
- **Injection Vectors Tested**:
  - Malicious redirect target schemes (`javascript:alert(1)`, `data:text/html,...`).
  - Malformed regex patterns (unclosed parentheses).
  - Malformed JSON body requests.
  - Invalid schema requests lacking `@context` or `@type`.
  - Non-existent and negative post IDs (`/analysis/post/999999`, `/analysis/post/-1`).
- **Assertions**: Live endpoints return standard HTTP 400 Bad Request or HTTP 404 Not Found without uncaught PHP fatals.
- **Classification**: `CODE_IMPLEMENTED` | `RUNTIME_NOT_VERIFIED`.

### 3.7 Performance Smoke Benchmarks (`scripts/runtime/verify-performance.sh`)
- **Real Benchmark Metrics to be Recorded in CI**:
  - Frontend Homepage TTFB and Total Response Time.
  - `robots.txt` and `sitemap_index.xml` Time to First Byte.
  - REST API `/status` and `/settings` latency.
  - In-process WP-CLI post analysis execution duration.
- **Classification**: `CODE_IMPLEMENTED` | `RUNTIME_NOT_VERIFIED`.

---

## 4. Summary

The complete integration test harness, container specifications, orchestration manifests, and multi-version GitHub Actions workflow are fully implemented and verified statically. The next git push to GitHub will automatically trigger the real WordPress runtime execution on native Ubuntu runners.
