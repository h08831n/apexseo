# Exhaustive 8-Source Migration & Data Translation Matrix

**Audit Reference**: Yoast SEO, Rank Math, All in One SEO, SEOPress, The SEO Framework, WP Rocket, LiteSpeed Cache, Redirection  
**Methodology**: Explicit 1:1 data translation mapping with realistic feasibility classifications.

---

## 1. Migration Classification Model

Every field and setting is categorized under one of 5 strict transformation types:

1. **`DIRECT`**: 1:1 exact equivalent in data structure, storage type, and meaning (e.g. meta title string -> `wp_apex_indexables.title`).
2. **`TRANSFORMED`**: Data is structurally rewritten to fit Apex SEO's normalized schema (e.g. converting Yoast serialized robots array to Apex integer bitmask/booleans).
3. **`PARTIAL`**: Core functionality is preserved, but secondary vendor-specific options are omitted (e.g. importing Cloudflare Zone ID and Email but requiring API Token entry).
4. **`NOT_APPLICABLE`**: Proprietary cloud services or deprecated features (e.g. QUIC.cloud credit balance, Zapier webhooks).
5. **`UNSUPPORTED`**: Features tied exclusively to closed proprietary third-party server modules that cannot run on standard WordPress.

---

## 2. Exhaustive Migration Source Mapping Matrix

| Source Product | Source Data Store / Meta Key / Table | Target Apex SEO Table / Option | Field Mapping | Transformation Classification | Rollback Strategy |
|---|---|---|---|---|---|
| **1. Yoast SEO** | `_yoast_wpseo_title` | `wp_apex_indexables.title` | Direct string copy with token conversion (`%%title%%` -> `%%title%%`) | `DIRECT` | Reversible snapshot in `wp_options` |
| **1. Yoast SEO** | `_yoast_wpseo_metadesc` | `wp_apex_indexables.description`| Direct string copy | `DIRECT` | Reversible snapshot in `wp_options` |
| **1. Yoast SEO** | `_yoast_wpseo_focuskw` | `wp_apex_indexables.primary_focus_keyword` | Direct string copy | `DIRECT` | Reversible snapshot |
| **1. Yoast SEO** | `_yoast_wpseo_canonical` | `wp_apex_indexables.canonical` | URL validation + direct copy | `DIRECT` | Reversible snapshot |
| **1. Yoast SEO** | `_yoast_wpseo_meta-robots-noindex` | `wp_apex_indexables.is_robots_noindex` | `1` => `1`, `2` => `0` (default index) | `TRANSFORMED` | Reversible snapshot |
| **1. Yoast SEO** | `_yoast_wpseo_meta-robots-nofollow`| `wp_apex_indexables.is_robots_nofollow`| `1` => `1`, `0` => `0` | `TRANSFORMED` | Reversible snapshot |
| **1. Yoast SEO** | `_yoast_wpseo_opengraph-title` | `wp_apex_indexables.open_graph_data` | JSON subkey `og_title` | `TRANSFORMED` | Reversible snapshot |
| **1. Yoast SEO** | `_yoast_wpseo_opengraph-image` | `wp_apex_indexables.open_graph_data` | JSON subkey `og_image` | `TRANSFORMED` | Reversible snapshot |
| **1. Yoast SEO** | `wp_yoast_seo_redirects` (Premium) | `wp_apex_redirects` | `url` -> `source`, `target` -> `target`, `type` -> `status_code` | `DIRECT` | Table backup in `wp_options` |
| **2. Rank Math** | `rank_math_title` | `wp_apex_indexables.title` | Direct string copy with `%title%` token translation | `TRANSFORMED` | Reversible snapshot |
| **2. Rank Math** | `rank_math_description` | `wp_apex_indexables.description`| Direct string copy | `DIRECT` | Reversible snapshot |
| **2. Rank Math** | `rank_math_focus_keyword` | `wp_apex_indexables.primary_focus_keyword` | Extracts first keyword if comma-separated | `TRANSFORMED` | Reversible snapshot |
| **2. Rank Math** | `rank_math_canonical_url` | `wp_apex_indexables.canonical` | Direct URL copy | `DIRECT` | Reversible snapshot |
| **2. Rank Math** | `rank_math_robots` (array) | `wp_apex_indexables.is_robots_noindex` | Evaluates presence of `'noindex'` in serialized array | `TRANSFORMED` | Reversible snapshot |
| **2. Rank Math** | `wp_rank_math_redirections` | `wp_apex_redirects` | Maps `sources`, `url_to`, `header_code`, `hits` | `DIRECT` | Table backup |
| **2. Rank Math** | `wp_rank_math_404_logs` | `wp_apex_404_logs` | Maps `uri`, `accessed`, `times_accessed`, `ip` | `DIRECT` | Table backup |
| **2. Rank Math** | `wp_rank_math_schema` (Pro) | `wp_apex_schema` | Maps schema JSON templates and display conditions | `DIRECT` | Table backup |
| **3. AIOSEO** | `_aioseo_title` | `wp_apex_indexables.title` | Direct string copy with `#post_title` translation | `TRANSFORMED` | Reversible snapshot |
| **3. AIOSEO** | `_aioseo_description` | `wp_apex_indexables.description`| Direct string copy | `DIRECT` | Reversible snapshot |
| **3. AIOSEO** | `_aioseo_canonical_url` | `wp_apex_indexables.canonical` | Direct URL copy | `DIRECT` | Reversible snapshot |
| **3. AIOSEO** | `_aioseo_noindex` | `wp_apex_indexables.is_robots_noindex` | Boolean conversion | `DIRECT` | Reversible snapshot |
| **3. AIOSEO** | `wp_aioseo_redirects` (Pro) | `wp_apex_redirects` | Maps source URL, target URL, and redirect type | `DIRECT` | Table backup |
| **4. SEOPress** | `_seopress_titles_title` | `wp_apex_indexables.title` | Direct string copy | `DIRECT` | Reversible snapshot |
| **4. SEOPress** | `_seopress_titles_desc` | `wp_apex_indexables.description`| Direct string copy | `DIRECT` | Reversible snapshot |
| **4. SEOPress** | `_seopress_robots_canonical` | `wp_apex_indexables.canonical` | Direct URL copy | `DIRECT` | Reversible snapshot |
| **4. SEOPress** | `_seopress_robots_index` | `wp_apex_indexables.is_robots_noindex` | `'yes'` => `1`, `''` => `0` | `TRANSFORMED` | Reversible snapshot |
| **4. SEOPress** | `wp_seopress_redirections` | `wp_apex_redirects` | Maps redirection table rows | `DIRECT` | Table backup |
| **5. The SEO Framework** | `_genesis_title` / `autodescription` | `wp_apex_indexables.title` | Direct title extraction | `DIRECT` | Reversible snapshot |
| **5. The SEO Framework** | `_genesis_description` | `wp_apex_indexables.description`| Direct description extraction | `DIRECT` | Reversible snapshot |
| **5. The SEO Framework** | `_genesis_canonical_uri` | `wp_apex_indexables.canonical` | Direct canonical extraction | `DIRECT` | Reversible snapshot |
| **5. The SEO Framework** | `_genesis_noindex` | `wp_apex_indexables.is_robots_noindex` | Boolean conversion | `DIRECT` | Reversible snapshot |
| **6. WP Rocket** | `wp_rocket_settings.cache_mobile` | `apex_cache_settings.separate_mobile_cache` | `1` => `1`, `0` => `0` | `DIRECT` | Options snapshot |
| **6. WP Rocket** | `wp_rocket_settings.cache_logged_user` | `apex_cache_settings.user_cache` | `1` => `1`, `0` => `0` | `DIRECT` | Options snapshot |
| **6. WP Rocket** | `wp_rocket_settings.purge_interval` | `apex_cache_settings.cache_lifespan` | Time in seconds converted | `DIRECT` | Options snapshot |
| **6. WP Rocket** | `wp_rocket_settings.exclude_css` | `apex_perf_settings.css_exclusions` | Array of stylesheet paths | `DIRECT` | Options snapshot |
| **6. WP Rocket** | `wp_rocket_settings.exclude_inline_js` | `apex_perf_settings.js_delay_exclusions` | Array of JS strings/scripts | `DIRECT` | Options snapshot |
| **6. WP Rocket** | `wp_rocket_settings.delay_js` | `apex_perf_settings.delay_js_execution` | Boolean flag toggle | `DIRECT` | Options snapshot |
| **6. WP Rocket** | `wp_rocket_settings.lazyload` | `apex_perf_settings.lazyload_images` | Boolean flag toggle | `DIRECT` | Options snapshot |
| **6. WP Rocket** | `wp_rocket_settings.cloudflare_zone_id` | `apex_cdn_settings.cloudflare_zone_id` | Zone ID string copy | `PARTIAL` (Requires Token) | Options snapshot |
| **7. LiteSpeed Cache** | `litespeed.conf.cache-priv` | `apex_cache_settings.user_cache` | `1` => `1` | `DIRECT` | Options snapshot |
| **7. LiteSpeed Cache** | `litespeed.conf.optm-css_min` | `apex_perf_settings.css_minify` | `1` => `1` | `DIRECT` | Options snapshot |
| **7. LiteSpeed Cache** | `litespeed.conf.optm-js_defer` | `apex_perf_settings.js_defer` | `1` => `1` | `DIRECT` | Options snapshot |
| **7. LiteSpeed Cache** | `litespeed.conf.media-lazy` | `apex_perf_settings.lazyload_images` | `1` => `1` | `DIRECT` | Options snapshot |
| **7. LiteSpeed Cache** | `litespeed.conf.object_cache-host` | `apex_cache_settings.redis_host` | Host / Port strings copied | `DIRECT` | Options snapshot |
| **7. LiteSpeed Cache** | QUIC.cloud QC credit balances | None | Proprietary cloud credits | `NOT_APPLICABLE` | None |
| **8. Redirection** | `wp_redirection_items` | `wp_apex_redirects` | `url` -> `source`, `action_data` -> `target`, `action_code` -> `status_code`, `match_type` -> `matching_type` | `DIRECT` | Table backup in `wp_options` |
