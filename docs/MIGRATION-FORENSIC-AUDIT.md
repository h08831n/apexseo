# Multi-Source Migration Forensic Audit

**Audit Date**: 2026-08-15  
**Audit Target**: Migration Engine, Importers & Exporters, Feature APEX-192  
**Evaluation Standard**: Zero-Data-Loss Migration Mapping, Batch Processing, Rollback Safety

---

## 1. Supported Source Plugins & Field Mappings

The migration architecture specifies deterministic mapping from 5 primary WordPress SEO plugins into the locked `wp_apex_indexables` and `wp_apex_redirects` tables.

| Source Plugin | Source Meta Keys / Tables | Apex SEO Target Column |
|---|---|---|
| **Yoast SEO** | `_yoast_wpseo_title`<br>`_yoast_wpseo_metadesc`<br>`_yoast_wpseo_canonical`<br>`_yoast_wpseo_focuskw`<br>`_yoast_wpseo_opengraph-title`<br>`_yoast_wpseo_opengraph-description`<br>`_yoast_wpseo_opengraph-image`<br>`_yoast_wpseo_meta-robots-noindex` | `apex_indexables.title`<br>`apex_indexables.description`<br>`apex_indexables.canonical_url`<br>`apex_indexables.primary_focus_keyword`<br>`apex_indexables.og_title`<br>`apex_indexables.og_description`<br>`apex_indexables.og_image`<br>`apex_indexables.is_robots_noindex` |
| **Rank Math** | `rank_math_title`<br>`rank_math_description`<br>`rank_math_canonical_url`<br>`rank_math_focus_keyword`<br>`rank_math_facebook_title`<br>`rank_math_facebook_description`<br>`rank_math_facebook_image`<br>`rank_math_robots` | `apex_indexables.title`<br>`apex_indexables.description`<br>`apex_indexables.canonical_url`<br>`apex_indexables.primary_focus_keyword`<br>`apex_indexables.og_title`<br>`apex_indexables.og_description`<br>`apex_indexables.og_image`<br>`apex_indexables.is_robots_noindex` |
| **All in One SEO (AIOSEO)** | `_aioseo_title`<br>`_aioseo_description`<br>`_aioseo_canonical_url`<br>`_aioseo_og_title`<br>`_aioseo_og_description` | `apex_indexables.title`<br>`apex_indexables.description`<br>`apex_indexables.canonical_url`<br>`apex_indexables.og_title`<br>`apex_indexables.og_description` |
| **SEOPress** | `_seopress_titles_title`<br>`_seopress_titles_desc`<br>`_seopress_social_fb_title`<br>`_seopress_social_fb_desc` | `apex_indexables.title`<br>`apex_indexables.description`<br>`apex_indexables.og_title`<br>`apex_indexables.og_description` |
| **Redirection Plugin** | `wp_redirection_items` (columns: `url`, `action_data`, `action_code`, `match_type`) | `wp_apex_redirects` (`source_url`, `target_url`, `status_code`, `match_type`) |

---

## 2. Migration Execution Flow & Safety Safeguards

1. **Non-Destructive Import**: Migrations read third-party meta keys without deleting them, allowing instant rollback.
2. **Batch Chunking**: Processes posts in batches of 500 using `DatabaseManager::beginTransaction()` and `DatabaseManager::commit()` to avoid execution timeouts.
3. **Multisite Batching**: Iterates across blogs using `MultisiteManager::runInBlogContext()`.

---

## 3. Implementation Status

- **Database Schemas**: ✅ **LOCKED & READY**
- **Migration Engine Architecture**: ✅ **SPECIFIED**
- **PHP Batch Importer Classes**: ⚠️ **PENDING IMPLEMENTATION IN `/src/Migration/`**
