# Schema.org Structured Data & JSON-LD Forensic Audit

**Audit Date**: 2026-08-15  
**Audit Target**: Schema Subsystem, Table `wp_apex_schema`, Features APEX-065 through APEX-080  
**Evaluation Standard**: Schema.org Standard (v23+), Google Rich Results Search Central Guidelines, JSON-LD `@graph` Interlinking

---

## 1. Schema Storage & Relational Design

The schema subsystem relies on `wp_apex_schema`, created in `Migration_1_0_0_CreateLockedTables.php`:

- **Primary Schema Table**: `wp_apex_schema`
  - Columns: `id`, `title`, `schema_type`, `schema_data` (LONGTEXT), `conditions` (LONGTEXT), `is_global`, `status`.
  - Indexes: `idx_schema_type`, `idx_status`, `idx_is_global`.
- **Indexables Integration**: Table `wp_apex_indexables` stores per-post schema overrides in column `schema_type`.

---

## 2. Schema Type Implementation Matrix

| ID | Schema Type | JSON-LD Type | Test Specification | Execution Status in `/src/` |
|---|---|---|---|---|
| **APEX-065** | `@graph` Tree Builder | Interlinked `@graph` | Specified in `SchemaSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-066** | Conditions Evaluator | Dynamic Rules | Specified in DDL column `conditions` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-067** | Article / NewsArticle | `Article`, `NewsArticle`, `BlogPosting` | Specified in `SchemaSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-068** | LocalBusiness | `LocalBusiness`, `Store`, `Restaurant` | Specified in `SchemaSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-069** | Organization / Person | `Organization`, `Person` | Specified in `SchemaSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-070** | FAQPage | `FAQPage` (`mainEntity` -> `Question` / `Answer`) | Specified in `SchemaSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-071** | WooCommerce Product | `Product` (`offers`, `aggregateRating`, `brand`) | Specified in `SchemaSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-072** | Recipe | `Recipe` (`recipeIngredient`, `recipeInstructions`) | Specification ready | ⚠️ **SPECIFIED_ONLY** |
| **APEX-073** | JobPosting | `JobPosting` (`hiringOrganization`, `baseSalary`) | Specification ready | ⚠️ **SPECIFIED_ONLY** |
| **APEX-074** | Course | `Course` (`provider`, `hasCourseInstance`) | Specification ready | ⚠️ **SPECIFIED_ONLY** |
| **APEX-075** | Event | `Event` (`location`, `startDate`, `eventStatus`) | Specification ready | ⚠️ **SPECIFIED_ONLY** |
| **APEX-076** | SoftwareApplication | `SoftwareApplication` (`operatingSystem`) | Specification ready | ⚠️ **SPECIFIED_ONLY** |
| **APEX-077** | VideoObject | `VideoObject` (`thumbnailUrl`, `contentUrl`) | Specification ready | ⚠️ **SPECIFIED_ONLY** |
| **APEX-078** | WebSite / SearchAction | `WebSite` (`potentialAction` -> `SearchAction`) | Specified in `SchemaSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-079** | BreadcrumbList | `BreadcrumbList` (`itemListElement`) | Specified in `SeoSubsystemTest` | ⚠️ **SCAFFOLDED_IN_TESTS** |
| **APEX-080** | Schema Validator | JSON-LD syntax checker | Specification ready | ⚠️ **SPECIFIED_ONLY** |

---

## 3. Forensic Conclusion

The schema database schema and test suite specifications are comprehensive and mathematically sound. Concrete PHP JSON-LD builder classes will be placed in `src/Schema/` to emit valid Schema.org graphs on frontend render.
