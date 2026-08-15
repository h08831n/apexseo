# 13 - Lossless Migration Engine Specification

## 1. Migration Overview
The Apex SEO Migration Engine enables one-click, lossless importing from 6 major SEO and redirection plugins:
1. **Yoast SEO (Free + Premium)**
2. **Rank Math (Free + Pro)**
3. **All in One SEO (AIOSEO Free + Pro)**
4. **SEOPress (Free + Pro)**
5. **The SEO Framework (TSF)**
6. **Redirection Plugin (John Godley)**

---

## 2. Source Metadata Key Mappings

### 2.1 Post & Term Meta Mapping Matrix

| Apex SEO Field | Yoast SEO Meta Key | Rank Math Meta Key | AIOSEO Meta Key | SEOPress Meta Key |
|---|---|---|---|---|
| **Title** | `_yoast_wpseo_title` | `rank_math_title` | `_aioseo_title` | `_seopress_titles_title` |
| **Meta Description**| `_yoast_wpseo_metadesc` | `rank_math_description` | `_aioseo_description` | `_seopress_titles_desc` |
| **Focus Keyword** | `_yoast_wpseo_focuskw` | `rank_math_focus_keyword`| `_aioseo_keywords` | `_seopress_analysis_target_kw`|
| **Canonical URL** | `_yoast_wpseo_canonical` | `rank_math_canonical_url`| `_aioseo_canonical_url`| `_seopress_robots_canonical` |
| **Robots Noindex** | `_yoast_wpseo_meta-robots-noindex` | `rank_math_robots` (`noindex`) | `_aioseo_noindex` | `_seopress_robots_index` (`no`) |
| **Robots Nofollow** | `_yoast_wpseo_meta-robots-nofollow`| `rank_math_robots` (`nofollow`)| `_aioseo_nofollow` | `_seopress_robots_follow` (`no`) |
| **Open Graph Title**| `_yoast_wpseo_opengraph-title` | `rank_math_facebook_title` | `_aioseo_og_title` | `_seopress_social_fb_title` |
| **Open Graph Desc** | `_yoast_wpseo_opengraph-description` | `rank_math_facebook_description` | `_aioseo_og_description` | `_seopress_social_fb_desc` |
| **Open Graph Image**| `_yoast_wpseo_opengraph-image` | `rank_math_facebook_image` | `_aioseo_og_image_url` | `_seopress_social_fb_img` |
| **Twitter Title** | `_yoast_wpseo_twitter-title` | `rank_math_twitter_title` | `_aioseo_twitter_title` | `_seopress_social_twitter_title` |
| **Twitter Desc** | `_yoast_wpseo_twitter-description` | `rank_math_twitter_description` | `_aioseo_twitter_description` | `_seopress_social_twitter_desc` |
| **Twitter Image** | `_yoast_wpseo_twitter-image` | `rank_math_twitter_image` | `_aioseo_twitter_image_url` | `_seopress_social_twitter_img` |

### 2.2 Redirect Table Mappings
- **Yoast Premium / Rank Math (`wp_rank_math_redirections`) / Redirection Plugin (`wp_redirection_items`)**:
  - Source URL, Target URL, Status Code (301, 302, 307, 410), Matching Type (exact, regex), and Hit Counts are imported directly into `wp_apex_redirects`.

---

## 3. Migration Safety Protocol
1. **Detect**: Identifies installed and inactive plugin options.
2. **Count & Analyze**: Calculates total records to be converted.
3. **Backup Snapshot**: Creates temporary backup in `wp_options` table.
4. **Chunked Execution**: Migrates records in batches of 250 via AJAX / WP-CLI.
5. **Verify**: Confirms row count parity.
6. **Rollback**: Administrator can revert changes with a single click.
