# APEX SEO — PHYSICAL REPOSITORY INVENTORY REPORT

> **AUDIT TIMESTAMP**: 2026-08-18T19:37:00Z  
> **AUDIT TARGET**: `https://github.com/h08831n/apexseo`  
> **VERIFICATION ENGINE**: Zero-Trust Physical File & AST Scanner  

---

## 1. Summary Filesystem Metrics

| Metric | Physical Count | Verification Location |
| :--- | :--- | :--- |
| **Production PHP Files (src/ + root)** | **120** | `wp-content/plugins/apexseo/src/` (118) + `apexseo.php` + `uninstall.php` (2) |
| **Test PHP Files (tests/)** | **22** | `wp-content/plugins/apexseo/tests/` (22) |
| **Total Repository PHP Files** | **142** | Complete plugin package directory |
| **Concrete PHP Classes** | **106** | Verified concrete classes in production PHP |
| **Abstract Base Classes** | **3** | `AbstractRestController`, `AbstractCliCommand`, `AbstractSchemaType` |
| **Core Interfaces** | **9** | Authoritative registered interfaces |
| **Traits** | **0** | No traits used in current architecture |
| **REST API Routes** | **23** | Registered in `RestApiRouter.php` and 10 REST controllers |
| **WP-CLI Root Commands** | **10** | Registered under `wp apex` / `wp apexseo` in `CliManager.php` |
| **Locked Database Tables** | **8** | Relational tables in `Migration_1_0_0_CreateLockedTables.php` |
| **Schema Types** | **12** | Rich snippet Schema.org classes in `src/Schema/Types/` |
| **Automated Test Methods** | **97** | Across 18 test suite files in `tests/` |
| **Automated Assertions** | **340** | Real unit and integration assertions |

---

## 2. Complete Authoritative Interfaces Inventory (9 Interfaces)

| # | Interface Name | Namespace | FQCN | File Location |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `ContainerInterface` | `ApexSEO\Core\Container` | `ApexSEO\Core\Container\ContainerInterface` | `src/Core/Container/ContainerInterface.php` |
| 2 | `BootableInterface` | `ApexSEO\Core\Contracts` | `ApexSEO\Core\Contracts\BootableInterface` | `src/Core/Contracts/BootableInterface.php` |
| 3 | `HookableInterface` | `ApexSEO\Core\Contracts` | `ApexSEO\Core\Contracts\HookableInterface` | `src/Core/Contracts/HookableInterface.php` |
| 4 | `ModuleInterface` | `ApexSEO\Core\Contracts` | `ApexSEO\Core\Contracts\ModuleInterface` | `src/Core/Contracts/ModuleInterface.php` |
| 5 | `ServiceContractInterface` | `ApexSEO\Core\Contracts` | `ApexSEO\Core\Contracts\ServiceContractInterface` | `src/Core/Contracts/ServiceContractInterface.php` |
| 6 | `MigrationInterface` | `ApexSEO\Core\Database` | `ApexSEO\Core\Database\MigrationInterface` | `src/Core/Database/MigrationInterface.php` |
| 7 | `ServerAdapterInterface` | `ApexSEO\Core\Environment\Server` | `ApexSEO\Core\Environment\Server\ServerAdapterInterface` | `src/Core/Environment/Server/ServerAdapterInterface.php` |
| 8 | `LoggerInterface` | `ApexSEO\Core\Logging` | `ApexSEO\Core\Logging\LoggerInterface` | `src/Core/Logging/LoggerInterface.php` |
| 9 | `SchemaTypeInterface` | `ApexSEO\Schema\Types` | `ApexSEO\Schema\Types\SchemaTypeInterface` | `src/Schema/Types/SchemaTypeInterface.php` |

---

## 3. Abstract Classes Inventory (3 Classes)

| # | Class Name | FQCN | File Location |
| :--- | :--- | :--- | :--- |
| 1 | `AbstractRestController` | `ApexSEO\API\Controllers\AbstractRestController` | `src/API/Controllers/AbstractRestController.php` |
| 2 | `AbstractCliCommand` | `ApexSEO\CLI\AbstractCliCommand` | `src/CLI/AbstractCliCommand.php` |
| 3 | `AbstractSchemaType` | `ApexSEO\Schema\Types\AbstractSchemaType` | `src/Schema/Types/AbstractSchemaType.php` |

---

## 4. Locked Database Schema Inventory (8 Tables)

| # | Table Name | Migration Source | Primary Key | Key Indexes |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `apex_indexables` | `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `uk_object_lookup`, `idx_permalink_hash`, `idx_seo_score` |
| 2 | `apex_schema` | `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `idx_object_id`, `idx_schema_type`, `idx_is_global` |
| 3 | `apex_redirects` | `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `uk_source_hash`, `idx_status`, `idx_hits` |
| 4 | `apex_404_logs` | `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `uk_uri_hash`, `idx_hit_count`, `idx_last_seen` |
| 5 | `apex_links` | `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `idx_post_id`, `idx_target_post_id`, `idx_url_hash` |
| 6 | `apex_image_history`| `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `uk_attachment_id`, `idx_format_served` |
| 7 | `apex_analytics` | `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `uk_object_date`, `idx_date`, `idx_clicks` |
| 8 | `apex_rank_tracking`| `Migration_1_0_0_CreateLockedTables.php` | `id (BIGINT)` | `uk_keyword_url`, `idx_current_position` |
