# Yoast SEO Reference Audit

## 1. Source Inventory
- Repository: https://github.com/Yoast/wordpress-seo
- Version Scope: Yoast SEO Free & Premium Documentation Analysis

## 2. Architecture & Class Mapping
- **Indexables System**: `Yoast\WP\SEO\Models\Indexable`, `Yoast\WP\SEO\Repositories\Indexable_Repository`
- **Metadata Generation**: `Yoast\WP\SEO\Generators\Open_Graph_Generator`, `Yoast\WP\SEO\Generators\Twitter_Generator`, `Yoast\WP\SEO\Generators\Schema_Generator`
- **Sitemap System**: `Yoast\WP\SEO\Sitemaps\Xml_Sitemap_Feed`, `WPSEO_Sitemap_Provider`
- **Content Analysis**: `Yoast\WP\SEO\Presenters\Abstract_Presenter`, `YoastSEO.js` analysis engine
- **Redirects (Premium)**: Redirect manager, 301/302/307/401/451 handling, regex matching
- **Internal Linking (Premium)**: Link suggestion engine, cornerstone content analysis

## 3. Key Feature Scope
1. Dynamic Meta Titles & Descriptions with variable replacement (`%%title%%`, `%%sep%%`, etc.)
2. Structured Data Schema Graph (`@graph` format with `@id` deduplication)
3. Canonical URL management and pagination canonicals
4. XML Sitemaps (Posts, Pages, CPTs, Taxonomies, Authors, Images)
5. Content & Readability Analysis (Flesch Reading Ease, passive voice, sentence length, keyphrase density)
6. CornerStone Content designation
7. Social Meta (Open Graph, Twitter Cards, Pinterest)
8. Redirect Manager & 404 handling (Premium)
9. Internal Link Suggestions (Premium)
10. WooCommerce SEO Integration (WooCommerce Yoast extension)
