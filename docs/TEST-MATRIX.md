# Comprehensive Test Matrix & Quality Assurance Specification

**Audit Reference**: Modern PHP / WordPress Testing Standards  
**Target Code Coverage**: **>= 85% Line Coverage** Across All Domain & Core Modules

---

## 1. Environment & Compatibility Test Matrix

| Dimension | Supported Versions / Configurations | CI Automated Matrix | Validation Criteria |
|---|---|---|---|
| **PHP Runtime** | 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 | Tested across all 6 versions | Zero deprecation notices, typed properties where supported, strict typing compliance |
| **WordPress Core** | 6.2, 6.3, 6.4, 6.5, 6.6, 6.7 | Tested across latest minor of each | Core hook compatibility, block editor schema integration |
| **WooCommerce** | 8.0, 8.5, 9.0+ | Matrix integration suite | High-Performance Order Storage (HPOS) compatibility, product schema verification |
| **Database Engines** | MySQL 5.7, 8.0, 8.4, MariaDB 10.3, 10.6, 11.2 | Dockerized integration runners | Table creation via `dbDelta()`, index efficiency, zero SQL syntax errors |
| **Web Servers** | Nginx, Apache 2.4, LiteSpeed, OpenLiteSpeed | Virtual host test suite | `.htaccess` rule generation, LiteSpeed purge headers, Nginx FastCGI purge |
| **Multisite** | Subdomain & Subdirectory configurations | Dedicated multisite test runner | Table prefix isolation, network activation, `switch_to_blog()` integrity |

---

## 2. Test Suite Architecture

```
┌────────────────────────────────────────────────────────┐
│                   Apex SEO Test Suite                  │
└───────────┬────────────────┬────────────────┬──────────┘
            │                │                │
            ▼                ▼                ▼
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│    Unit Tests    │ │Integration Tests │ │    E2E Tests     │
│  (Brain Monkey / │ │(WP_UnitTestCase /│ │  (Playwright /   │
│     Mockery)     │ │  Docker MySQL)   │ │ Headless Browser)│
└──────────────────┘ └──────────────────┘ └──────────────────┘
```

### 2.1 Unit Test Suite (`tests/Unit/`)
- **Framework**: PHPUnit 9.6+ with **Brain Monkey** for WordPress hook/function mocking and **Mockery** for dependency injection mocking.
- **Scope**: Pure business logic (Meta Title token parser, Readability Flesch-Kincaid formula, Schema graph resolver, Cache rule evaluator).
- **Execution Speed**: < 2.0 seconds for complete unit suite (Zero database queries).

### 2.2 Integration Test Suite (`tests/Integration/`)
- **Framework**: `WP_UnitTestCase` with a live MariaDB/MySQL test database.
- **Scope**: Relational table migrations, database queries, redirect routing, REST API controllers, sitemap XML rendering, and migration importers.

### 2.3 End-to-End (E2E) Test Suite (`tests/E2E/`)
- **Framework**: **Playwright** running against a live WordPress instance.
- **Scope**: Admin settings saving, Block Editor sidebar interaction, schema builder modal, media bulk optimization progress bar, frontend HTML source validation.

### 2.4 Performance & Memory Regression Suite (`tests/Performance/`)
- **Framework**: PHPUnit custom performance assertions.
- **Scope**: Verifies frontend head render < 15ms and memory consumption < 4MB.

---

## 3. Test Coverage Thresholds

| Module Group | Minimum Line Coverage | Critical Path Target |
|---|---|---|
| **Core & Container** | **95%** | 100% |
| **SEO Meta & Canonical** | **90%** | 98% |
| **Schema Engine & Graph**| **90%** | 98% |
| **Cache Drivers & Rules**| **90%** | 98% |
| **Database Migrations** | **95%** | 100% |
| **Migration Importers** | **85%** | 95% |
| **REST API Controllers** | **85%** | 95% |
| **Overall Plugin Target**| **>= 85%** | **>= 92%** |
