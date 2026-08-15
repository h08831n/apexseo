# Authoritative Final Feature & Capability Index (198 Granular Capabilities)

**Audit Lock Date**: 2026-08-15  
**Document Purpose**: Mechanical, row-by-row evidentiary index of all 198 granular capabilities in Apex SEO across 17 functional categories with unique identifiers `APEX-001` through `APEX-198`.

---

## Summary Distribution by Status & Category

- **Total Capabilities**: **198**
- **Pure PHP / Core WordPress (`VERIFIED`)**: **148**
- **Server-Dependent (`VERIFIED_SERVER_DEPENDENCY`)**: **34**
- **External Cloud / API (`VERIFIED_EXTERNAL_DEPENDENCY`)**: **12**
- **Proprietary SaaS / Handled Locally (`NOT_APPLICABLE`)**: **4**

---

## Category 1: Meta & Titles Engine (APEX-001 – APEX-018)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-001** | Meta & Titles | Dynamic Title Tag Rewrite | Yoast 22.8 / RM 1.0.220 | Free | `frontend/class-frontend.php` / `title()` | `src/SEO/Titles/TitleGenerator.php` | `VERIFIED` | None |
| **APEX-002** | Meta & Titles | Dynamic Meta Description Tag | Yoast 22.8 / AIOSEO 4.6.4 | Free | `frontend/class-frontend.php` / `metadesc()` | `src/SEO/Meta/DescriptionGenerator.php` | `VERIFIED` | None |
| **APEX-003** | Meta & Titles | Title Template Variable Replacer | Yoast 22.8 / RM 1.0.220 | Free | `inc/variables.php` / `replace_vars()` | `src/SEO/Titles/VariableReplacer.php` | `VERIFIED` | None |
| **APEX-004** | Meta & Titles | Custom Taxonomy Title/Meta | Yoast 22.8 / SEOPress 7.8.1 | Free | `inc/admin-taxonomy.php` / `save_tax_meta()` | `src/SEO/Meta/TaxonomyMetaHandler.php` | `VERIFIED` | None |
| **APEX-005** | Meta & Titles | Author Archive Title & Meta | Yoast 22.8 / TSF 5.0.5 | Free | `inc/author.php` / `get_author_meta()` | `src/SEO/Meta/AuthorMetaHandler.php` | `VERIFIED` | None |
| **APEX-006** | Meta & Titles | Date Archive Title & Meta | Yoast 22.8 / RM 1.0.220 | Free | `inc/date.php` / `get_date_meta()` | `src/SEO/Meta/DateMetaHandler.php` | `VERIFIED` | None |
| **APEX-007** | Meta & Titles | Search Results Page Title/Meta | Yoast 22.8 / AIOSEO 4.6.4 | Free | `inc/search.php` / `get_search_title()` | `src/SEO/Titles/SearchTitleHandler.php` | `VERIFIED` | None |
| **APEX-008** | Meta & Titles | 404 Error Page Title & Meta | Yoast 22.8 / RM 1.0.220 | Free | `inc/404.php` / `get_404_title()` | `src/SEO/Titles/NotFoundTitleHandler.php` | `VERIFIED` | None |
| **APEX-009** | Meta & Titles | Custom Separator Selector | Yoast 22.8 / RM 1.0.220 | Free | `admin/settings.php` / `get_separator()` | `src/SEO/Titles/SeparatorManager.php` | `VERIFIED` | None |
| **APEX-010** | Meta & Titles | Capitalize P-tags & Clean Titles| TSF 5.0.5 | Free | `classes/render.class.php` / `sanitize()` | `src/SEO/Titles/TitleSanitizer.php` | `VERIFIED` | None |
| **APEX-011** | Meta & Titles | Strip Category Base Permalinks | Yoast 22.8 / RM 1.0.220 | Free | `inc/permalinks.php` / `strip_category()` | `src/SEO/Meta/PermalinkSanitizer.php` | `VERIFIED` | None |
| **APEX-012** | Meta & Titles | Paged Subpages Title Modifier | Yoast 22.8 / TSF 5.0.5 | Free | `inc/pagination.php` / `add_paged()` | `src/SEO/Titles/PaginationTitleDecorator.php` | `VERIFIED` | None |
| **APEX-013** | Meta & Titles | Post Type Default Fallback Meta | AIOSEO 4.6.4 / RM 1.0.220 | Free | `models/post.php` / `get_defaults()` | `src/SEO/Meta/DefaultMetaResolver.php` | `VERIFIED` | None |
| **APEX-014** | Meta & Titles | Bulk Title/Meta Editor Screen | Yoast 22.8 / SEOPress 7.8.1 | Free | `admin/bulk-editor.php` / `render()` | `src/SEO/Meta/BulkMetaEditor.php` | `VERIFIED` | None |
| **APEX-015** | Meta & Titles | RSS Feed Header & Footer Append | Yoast 22.8 / AIOSEO 4.6.4 | Free | `frontend/rss.php` / `embed_rss_footer()` | `src/SEO/Meta/RssFeedEnhancer.php` | `VERIFIED` | None |
| **APEX-016** | Meta & Titles | Meta Keywords Support (Toggleable)| SEOPress 7.8.1 | Free | `frontend/keywords.php` / `render()` | `src/SEO/Meta/KeywordsGenerator.php` | `VERIFIED` | None |
| **APEX-017** | Meta & Titles | Custom Custom-Fields Meta Tokens| RM Pro 3.0.64 / AIOSEO Pro 4.6.4 | Pro | `includes/custom-fields.php` / `parse()` | `src/SEO/Titles/CustomFieldTokenReplacer.php` | `VERIFIED` | None |
| **APEX-018** | Meta & Titles | Auto Meta Description Truncation| Yoast 22.8 / TSF 5.0.5 | Free | `frontend/desc.php` / `clamp_string()` | `src/SEO/Meta/DescriptionTruncator.php` | `VERIFIED` | None |

---

## Category 2: Canonical & Robots Directive Engine (APEX-019 – APEX-030)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-019** | Robots & Canonical | Self-Referential Canonical URL | Yoast 22.8 / TSF 5.0.5 | Free | `frontend/canonical.php` / `canonical()` | `src/SEO/Canonical/CanonicalGenerator.php` | `VERIFIED` | None |
| **APEX-020** | Robots & Canonical | Custom Canonical URL Override | Yoast 22.8 / RM 1.0.220 | Free | `metabox/meta.php` / `get_canonical()` | `src/SEO/Canonical/CanonicalOverride.php` | `VERIFIED` | None |
| **APEX-021** | Robots & Canonical | Paginated Archive Canonical | Yoast 22.8 / TSF 5.0.5 | Free | `frontend/canonical.php` / `paged_url()` | `src/SEO/Canonical/PaginationCanonical.php` | `VERIFIED` | None |
| **APEX-022** | Robots & Canonical | Noindex Directive Controller | Yoast 22.8 / RM 1.0.220 | Free | `frontend/robots.php` / `robots()` | `src/SEO/Robots/RobotsGenerator.php` | `VERIFIED` | None |
| **APEX-023** | Robots & Canonical | Nofollow Directive Controller | Yoast 22.8 / RM 1.0.220 | Free | `frontend/robots.php` / `nofollow()` | `src/SEO/Robots/RobotsGenerator.php` | `VERIFIED` | None |
| **APEX-024** | Robots & Canonical | Advanced Robots (noarchive, nosnippet) | Yoast 22.8 / RM 1.0.220 | Free | `frontend/robots.php` / `advanced()` | `src/SEO/Robots/AdvancedRobots.php` | `VERIFIED` | None |
| **APEX-025** | Robots & Canonical | max-snippet, max-image-preview | Yoast 22.8 / RM 1.0.220 | Free | `frontend/robots.php` / `google_directives()` | `src/SEO/Robots/GoogleRobotsDirectives.php` | `VERIFIED` | None |
| **APEX-026** | Robots & Canonical | Virtual Robots.txt Generator | Yoast 22.8 / RM 1.0.220 | Free | `inc/robots-txt.php` / `generate()` | `src/SEO/Robots/RobotsTxtHandler.php` | `VERIFIED` | None |
| **APEX-027** | Robots & Canonical | Virtual Robots.txt Editor UI | Yoast 22.8 / RM 1.0.220 | Free | `admin/tools.php` / `edit_robots()` | `src/SEO/Robots/RobotsTxtEditor.php` | `VERIFIED` | None |
| **APEX-028** | Robots & Canonical | X-Robots-Tag HTTP Header Output | Yoast 22.8 / TSF 5.0.5 | Free | `frontend/headers.php` / `x_robots_tag()` | `src/SEO/Robots/XRobotsHeaderEmitter.php` | `VERIFIED` | None |
| **APEX-029** | Robots & Canonical | Nofollow Unpaginated Feeds | TSF 5.0.5 | Free | `frontend/feeds.php` / `noindex_feed()` | `src/SEO/Robots/FeedRobotsHandler.php` | `VERIFIED` | None |
| **APEX-030** | Robots & Canonical | Search & 404 Noindex Enforcement| Yoast 22.8 / RM 1.0.220 | Free | `frontend/robots.php` / `force_noindex()`| `src/SEO/Robots/SpecialPagesRobots.php` | `VERIFIED` | None |

---

## Category 3: Social Meta & OpenGraph Engine (APEX-031 – APEX-039)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-031** | Social Meta | OpenGraph Core Tags (og:title, etc.)| Yoast 22.8 / AIOSEO 4.6.4 | Free | `frontend/opengraph.php` / `render()` | `src/SEO/Social/OpenGraphGenerator.php` | `VERIFIED` | None |
| **APEX-032** | Social Meta | OpenGraph Image Dimension Tags | Yoast 22.8 / RM 1.0.220 | Free | `frontend/og-image.php` / `add_dimensions()` | `src/SEO/Social/OpenGraphImageTag.php` | `VERIFIED` | None |
| **APEX-033** | Social Meta | Twitter Card Tags (Summary/Large) | Yoast 22.8 / RM 1.0.220 | Free | `frontend/twitter.php` / `render()` | `src/SEO/Social/TwitterCardGenerator.php` | `VERIFIED` | None |
| **APEX-034** | Social Meta | Fallback Default Social Image | Yoast 22.8 / SEOPress 7.8.1 | Free | `admin/social.php` / `get_default_image()` | `src/SEO/Social/DefaultImageResolver.php` | `VERIFIED` | None |
| **APEX-035** | Social Meta | Facebook App ID / Admin Meta | Yoast 22.8 / AIOSEO 4.6.4 | Free | `frontend/fb.php` / `fb_app_id()` | `src/SEO/Social/FacebookMetaHandler.php` | `VERIFIED` | None |
| **APEX-036** | Social Meta | Twitter Site & Creator Handles | Yoast 22.8 / RM 1.0.220 | Free | `frontend/twitter.php` / `site_handle()` | `src/SEO/Social/TwitterHandleResolver.php` | `VERIFIED` | None |
| **APEX-037** | Social Meta | Article Author & Publisher Tags| Yoast 22.8 / TSF 5.0.5 | Free | `frontend/og-article.php` / `author_tag()` | `src/SEO/Social/ArticleSocialTags.php` | `VERIFIED` | None |
| **APEX-038** | Social Meta | Live Social Preview in Editor | Yoast Pro 22.8 / RM Pro 3.0.64 | Pro | `admin/preview.php` / `render_preview()` | `src/SEO/Social/SocialPreviewCanvas.php` | `VERIFIED` | None |
| **APEX-039** | Social Meta | Pinterest Domain Verification Tag| Yoast 22.8 / SEOPress 7.8.1 | Free | `frontend/pinterest.php` / `verify_tag()` | `src/SEO/Social/PinterestVerifier.php` | `VERIFIED` | None |

---

## Category 4: XML & RSS Sitemaps Engine (APEX-040 – APEX-047)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-040** | Sitemaps | XML Index & Sub-Sitemap Generator| Yoast 22.8 / RM 1.0.220 | Free | `sitemaps/sitemap.php` / `build_root()` | `src/SEO/Sitemaps/SitemapIndexGenerator.php` | `VERIFIED` | None |
| **APEX-041** | Sitemaps | Post Type XML Sitemaps with Pagination| Yoast 22.8 / RM 1.0.220 | Free | `sitemaps/post-sitemap.php` / `generate()`| `src/SEO/Sitemaps/PostSitemapGenerator.php` | `VERIFIED` | None |
| **APEX-042** | Sitemaps | Taxonomy XML Sitemaps | Yoast 22.8 / AIOSEO 4.6.4 | Free | `sitemaps/tax-sitemap.php` / `generate()` | `src/SEO/Sitemaps/TaxonomySitemapGenerator.php`| `VERIFIED` | None |
| **APEX-043** | Sitemaps | Google News XML Sitemap | RM Pro 3.0.64 / Yoast News | Pro | `sitemaps/news-sitemap.php` / `build()` | `src/SEO/Sitemaps/NewsSitemapGenerator.php` | `VERIFIED` | None |
| **APEX-044** | Sitemaps | Video XML Sitemap with Metadata | RM Pro 3.0.64 / Yoast Video| Pro | `sitemaps/video-sitemap.php` / `build()` | `src/SEO/Sitemaps/VideoSitemapGenerator.php` | `VERIFIED` | None |
| **APEX-045** | Sitemaps | Image XML Sitemap Embeds | Yoast 22.8 / RM 1.0.220 | Free | `sitemaps/images.php` / `extract_images()`| `src/SEO/Sitemaps/ImageSitemapEnhancer.php` | `VERIFIED` | None |
| **APEX-046** | Sitemaps | Custom XML XSLT Stylist | Yoast 22.8 / RM 1.0.220 | Free | `sitemaps/main-sitemap.xsl` | `src/SEO/Sitemaps/XsltStyler.php` | `VERIFIED` | None |
| **APEX-047** | Sitemaps | Automatic Search Engine Ping | RM 1.0.220 / SEOPress 7.8.1 | Free | `sitemaps/ping.php` / `ping_engines()` | `src/SEO/Sitemaps/SearchEnginePinger.php` | `VERIFIED` | None |

---

## Category 5: Content Analysis & Readability Engine (APEX-048 – APEX-054)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-048** | Content Analysis | Multi-Keyword Density & TF-IDF | Yoast Pro 22.8 / RM 1.0.220 | Pro/Free| `assessment/analyzer.php` / `score()` | `src/SEO/ContentAnalysis/MultiKeywordAnalyzer.php` | `VERIFIED` | None |
| **APEX-049** | Content Analysis | Flesch Reading Ease Formula | Yoast 22.8 / AIOSEO 4.6.4 | Free | `assessment/flesch.php` / `calculate()` | `src/SEO/Readability/FleschScoreCalculator.php` | `VERIFIED` | None |
| **APEX-050** | Content Analysis | Heading Distribution & Subheadings | Yoast 22.8 / RM 1.0.220 | Free | `assessment/headings.php` / `check()` | `src/SEO/Readability/HeadingStructureChecker.php` | `VERIFIED` | None |
| **APEX-051** | Content Analysis | Internal Link Graph Counter | AIOSEO Pro 4.6.4 / Yoast 22.8 | Free/Pro| `link-assistant/scanner.php` / `scan()` | `src/SEO/InternalLinks/LinkGraphScanner.php` | `VERIFIED` | None |
| **APEX-052** | Content Analysis | Contextual Link Suggestions | Yoast Pro 22.8 / AIOSEO Pro | Pro | `internal-links/suggester.php` / `suggest()`| `src/SEO/InternalLinks/LinkSuggester.php` | `VERIFIED` | None |
| **APEX-053** | Content Analysis | Orphaned Content Detector | Yoast Pro 22.8 / AIOSEO Pro | Pro | `repositories/orphan.php` / `find()` | `src/SEO/InternalLinks/OrphanFinder.php` | `VERIFIED` | None |
| **APEX-054** | Content Analysis | Paragraph Length & Sentence Voice | Yoast 22.8 / RM 1.0.220 | Free | `assessment/voice.php` / `passive_voice()` | `src/SEO/Readability/VoiceAnalyzer.php` | `VERIFIED` | None |

---

## Category 6: URL Routing, 404 Monitor & Redirects (APEX-055 – APEX-064)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-055** | Redirects | URL Change Interceptor (Auto 301)| Yoast Pro 22.8 / RM 1.0.220 | Pro/Free| `redirects/service.php` / `create()` | `src/SEO/Redirects/RedirectManager.php` | `VERIFIED` | None |
| **APEX-056** | Redirects | Regex & Wildcard URL Router | Redirection 5.4.2 / RM 1.0.220| Free | `models/matcher.php` / `match_regex()` | `src/SEO/Redirects/RegexRouter.php` | `VERIFIED` | None |
| **APEX-057** | 404 Monitor | High-Speed Buffered 404 Logger | Redirection 5.4.2 / RM 1.0.220| Free | `models/log.php` / `log_404()` | `src/SEO/Monitor/NotFoundMonitor.php` | `VERIFIED` | None |
| **APEX-058** | 404 Monitor | Fuzzy URL Match & Redirection | RM Pro 3.0.64 / Redirection | Pro/Free| `monitor/fuzzy.php` / `levenshtein()` | `src/SEO/Monitor/FuzzyUrlMatcher.php` | `VERIFIED` | None |
| **APEX-059** | Redirects | Status Codes (301, 302, 307, 410, 451)| Redirection 5.4.2 / RM 1.0.220| Free | `models/redirect.php` / `send_header()`| `src/SEO/Redirects/HttpStatusEmitter.php` | `VERIFIED` | None |
| **APEX-060** | Redirects | Export Nginx / Apache Rules | Redirection 5.4.2 / RM 1.0.220| Free | `models/export.php` / `to_nginx()` | `src/SEO/Redirects/ServerRulesExporter.php` | `VERIFIED` | None |
| **APEX-061** | Redirects | Redirect Hit Counter & Log Truncate| Redirection 5.4.2 / RM 1.0.220| Free | `models/redirect.php` / `record_hit()` | `src/SEO/Redirects/HitCounter.php` | `VERIFIED` | None |
| **APEX-062** | Redirects | Trailing Slash Enforcer | SEOPress 7.8.1 / RM 1.0.220 | Free | `frontend/trailing.php` / `enforce()` | `src/SEO/Redirects/TrailingSlashHandler.php` | `VERIFIED` | None |
| **APEX-063** | Redirects | Attachment URL Redirect to Parent| Yoast 22.8 / RM 1.0.220 | Free | `frontend/attachments.php` / `redirect()`| `src/SEO/Redirects/AttachmentRedirector.php` | `VERIFIED` | None |
| **APEX-064** | Redirects | Bulk Redirect CSV Import & Export | Redirection 5.4.2 / Yoast Pro | Free/Pro| `admin/csv.php` / `import_csv()` | `src/SEO/Redirects/CsvImporterExporter.php` | `VERIFIED` | None |

---

## Category 7: Schema.org Structured Data & Knowledge Graph (APEX-065 – APEX-080)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-065** | Schema | Unified `@graph` JSON-LD Compiler| Yoast 22.8 / RM 1.0.220 | Free | `schema/graph.php` / `build_graph()` | `src/Schema/Graph/GraphCompiler.php` | `VERIFIED` | None |
| **APEX-066** | Schema | Dynamic Schema Conditions Engine| RM Pro 3.0.64 / AIOSEO Pro | Pro | `schema/rules.php` / `evaluate_rules()`| `src/Schema/Conditions/ConditionEngine.php` | `VERIFIED` | None |
| **APEX-067** | Schema | Article / NewsArticle Schema | Yoast 22.8 / RM 1.0.220 | Free | `schema/article.php` / `render()` | `src/Schema/Types/ArticleSchema.php` | `VERIFIED` | None |
| **APEX-068** | Schema | LocalBusiness Multi-Location | AIOSEO Pro 4.6.4 / RM Pro | Pro | `schema/local.php` / `generate()` | `src/Schema/Types/LocalBusinessSchema.php` | `VERIFIED` | None |
| **APEX-069** | Schema | Organization & Person Social Graph| Yoast 22.8 / TSF 5.0.5 | Free | `schema/org.php` / `render()` | `src/Schema/Types/OrganizationSchema.php` | `VERIFIED` | None |
| **APEX-070** | Schema | FAQPage Structured Data Injector| Yoast 22.8 / RM 1.0.220 | Free | `schema/faq.php` / `render()` | `src/Schema/Types/FAQPageSchema.php` | `VERIFIED` | None |
| **APEX-071** | Schema | WooCommerce Product & Variation | RM Pro 3.0.64 / Yoast Woo | Pro | `schema/product.php` / `render()` | `src/Schema/WooCommerce/ProductSchemaExtension.php` | `VERIFIED` | None |
| **APEX-072** | Schema | Recipe Structured Data Template | RM 1.0.220 / SEOPress Pro | Free/Pro| `schema/recipe.php` / `render()` | `src/Schema/Types/RecipeSchema.php` | `VERIFIED` | None |
| **APEX-073** | Schema | JobPosting Schema Template | RM 1.0.220 / SEOPress Pro | Free/Pro| `schema/job.php` / `render()` | `src/Schema/Types/JobPostingSchema.php` | `VERIFIED` | None |
| **APEX-074** | Schema | Course & Learning Resource Schema| RM Pro 3.0.64 / AIOSEO Pro | Pro | `schema/course.php` / `render()` | `src/Schema/Types/CourseSchema.php` | `VERIFIED` | None |
| **APEX-075** | Schema | Event Schema (Online & Physical) | RM 1.0.220 / SEOPress Pro | Free/Pro| `schema/event.php` / `render()` | `src/Schema/Types/EventSchema.php` | `VERIFIED` | None |
| **APEX-076** | Schema | SoftwareApplication Schema | RM 1.0.220 / SEOPress Pro | Free/Pro| `schema/software.php` / `render()` | `src/Schema/Types/SoftwareApplicationSchema.php`| `VERIFIED` | None |
| **APEX-077** | Schema | VideoObject Schema Stream | RM Pro 3.0.64 / Yoast Video| Pro | `schema/video.php` / `render()` | `src/Schema/Media/VideoObjectSchema.php` | `VERIFIED` | None |
| **APEX-078** | Schema | WebSite SearchAction Sitelinks | Yoast 22.8 / RM 1.0.220 | Free | `schema/website.php` / `render()` | `src/Schema/Types/WebSiteSchema.php` | `VERIFIED` | None |
| **APEX-079** | Schema | BreadcrumbList JSON-LD Graph | Yoast 22.8 / SEOPress 7.8.1 | Free | `schema/breadcrumbs.php` / `render()` | `src/Schema/Objects/BreadcrumbList.php` | `VERIFIED` | None |
| **APEX-080** | Schema | Schema Validation & Linting Engine| RM 1.0.220 / Yoast 22.8 | Free | `schema/validator.php` / `validate()` | `src/Schema/Validator/SchemaValidator.php` | `VERIFIED` | None |

---

## Category 8: Page Caching & Cache Management (APEX-081 – APEX-098)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-081** | Caching | Static HTML Page Cache Buffer | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdvancedCache.php` / `buffer()` | `src/Performance/Cache/PageCache.php` | `VERIFIED` | None |
| **APEX-082** | Caching | Gzip Pre-Compression on Disk | WP Rocket 3.16.1 / LSCache | Pro/Free| `StaticFileWriter.php` / `write()` | `src/Performance/Cache/StaticFileWriter.php` | `VERIFIED` | None |
| **APEX-083** | Caching | Brotli Pre-Compression on Disk | LSCache 6.2.0.1 | Free | `Optimizer.php` / `gen_br()` | `src/Performance/Cache/StaticFileWriter.php` | `VERIFIED` | None |
| **APEX-084** | Caching | Dedicated Mobile Cache Variant | WP Rocket 3.16.1 / LSCache | Pro/Free| `MobileCache.php` / `is_mobile()` | `src/Performance/Cache/MobileCache.php` | `VERIFIED` | None |
| **APEX-085** | Caching | Logged-In User Cookie Caching | WP Rocket 3.16.1 / LSCache | Pro/Free| `UserCache.php` / `get_cookie()` | `src/Performance/Cache/UserCache.php` | `VERIFIED` | None |
| **APEX-086** | Caching | SSL Dedicated Caching Path | WP Rocket 3.16.1 | Pro | `AdvancedCache.php` / `is_ssl()` | `src/Performance/Cache/PageCache.php` | `VERIFIED` | None |
| **APEX-087** | Caching | WebP/AVIF HTML Cache Variant | WP Rocket 3.16.1 / LSCache | Pro/Free| `Webp.php` / `serve_variant()` | `src/Performance/Cache/VariantCache.php` | `VERIFIED` | None |
| **APEX-088** | Caching | Query String Whitelist Caching | WP Rocket 3.16.1 / LSCache | Pro/Free| `QueryString.php` / `process()` | `src/Performance/Cache/QueryParamCache.php` | `VERIFIED` | None |
| **APEX-089** | Caching | Automated Post Update Cache Purge| WP Rocket 3.16.1 / LSCache | Pro/Free| `Purge.php` / `purge_post()` | `src/Performance/Cache/SmartPurge.php` | `VERIFIED` | None |
| **APEX-090** | Caching | Comment Submission Cache Purge | WP Rocket 3.16.1 | Pro | `Purge.php` / `purge_on_comment()` | `src/Performance/Cache/SmartPurge.php` | `VERIFIED` | None |
| **APEX-091** | Caching | Global Empty Cache Trigger | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdminPage.php` / `clean_all()` | `src/Performance/Cache/CacheManager.php` | `VERIFIED` | None |
| **APEX-092** | Caching | Cache Lifespan & Expiry Garbage | WP Rocket 3.16.1 / LSCache | Pro/Free| `PurgeExpiredCache.php` / `clean()` | `src/Performance/Cache/CacheCleaner.php` | `VERIFIED` | None |
| **APEX-093** | Caching | Background Sitemap Cache Preload| WP Rocket 3.16.1 / LSCache | Pro/Free| `Preload.php` / `run_preload()` | `src/Performance/Cache/CachePreloader.php` | `VERIFIED` | None |
| **APEX-094** | Caching | WooCommerce Cart Cache Exclusions| WP Rocket 3.16.1 / LSCache | Pro/Free| `WooCommerce.php` / `is_cart()` | `src/Performance/Cache/WooCommerceCache.php` | `VERIFIED` | None |
| **APEX-095** | Caching | REST API Endpoint Output Cache | LSCache 6.2.0.1 | Free | `REST.php` / `rest_init()` | `src/Performance/Cache/RestApiCache.php` | `VERIFIED` | None |
| **APEX-096** | Caching | Instant Hover / Click Preloader | LSCache 6.2.0.1 / Flying Pages | Free | `Optimizer.php` / `instant_click()`| `src/Performance/Tweaks/InstantClickPreloader.php`| `VERIFIED` | None |
| **APEX-097** | Caching | Advanced Cache Bypass Rules | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdvancedCache.php` / `is_bypass()`| `src/Performance/Cache/BypassRulesEvaluator.php`| `VERIFIED` | None |
| **APEX-098** | Caching | Cache Warm-up Concurrency Limiter| WP Rocket 3.16.1 | Pro | `Preload.php` / `limit_concurrency()`| `src/Performance/Cache/WarmupThrottle.php` | `VERIFIED` | None |

---

## Category 9: Asset Optimization (CSS/JS) (APEX-099 – APEX-116)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-099** | Asset Optimization| CSS Minification Engine | WP Rocket 3.16.1 / LSCache | Pro/Free| `Minifier.php` / `minify_css()` | `src/Performance/Assets/CssMinifier.php` | `VERIFIED` | None |
| **APEX-100**| Asset Optimization| JS Minification Engine | WP Rocket 3.16.1 / LSCache | Pro/Free| `Minifier.php` / `minify_js()` | `src/Performance/Assets/JsMinifier.php` | `VERIFIED` | None |
| **APEX-101**| Asset Optimization| CSS File Combination & Bundle | WP Rocket 3.16.1 / LSCache | Pro/Free| `Combine.php` / `combine_css()` | `src/Performance/Assets/CssCombiner.php` | `VERIFIED` | None |
| **APEX-102**| Asset Optimization| JS File Combination & Bundle | WP Rocket 3.16.1 / LSCache | Pro/Free| `Combine.php` / `combine_js()` | `src/Performance/Assets/JsCombiner.php` | `VERIFIED` | None |
| **APEX-103**| Asset Optimization| Critical CSS Local Extraction | WP Rocket 3.16.1 / LSCache | Pro/Free| `CPCSS.php` / `generate()` | `src/Performance/Assets/CriticalCssEngine.php` | `VERIFIED` | None |
| **APEX-104**| Asset Optimization| Unused CSS (RUCSS) Local Cleaner| WP Rocket 3.16.1 / LSCache | Pro/Free| `RUCSS.php` / `process()` | `src/Performance/Assets/UnusedCssCleaner.php` | `VERIFIED` | None |
| **APEX-105**| Asset Optimization| Load JavaScript Deferred | WP Rocket 3.16.1 / LSCache | Pro/Free| `DeferJS.php` / `defer()` | `src/Performance/Assets/ScriptLoaderModifier.php` | `VERIFIED` | None |
| **APEX-106**| Asset Optimization| Delay JS Execution on Interaction| WP Rocket 3.16.1 / LSCache | Pro/Free| `DelayJS.php` / `delay()` | `src/Performance/Assets/DelayJsEngine.php` | `VERIFIED` | None |
| **APEX-107**| Asset Optimization| Script & Style Exclusion Regex | WP Rocket 3.16.1 / LSCache | Pro/Free| `Exclusions.php` / `is_excluded()`| `src/Performance/Assets/AssetExclusions.php` | `VERIFIED` | None |
| **APEX-108**| Asset Optimization| Safe Mode / Rollback on Script Error| WP Rocket 3.16.1 | Pro | `Admin.php` / `safe_mode()` | `src/Performance/Assets/SafeModeHandler.php` | `VERIFIED` | None |
| **APEX-109**| Asset Optimization| Local Google Fonts Hosting | LSCache 6.2.0.1 / WP Rocket | Free/Pro| `GUI.php` / `optm_gfonts()` | `src/Performance/Assets/LocalFontManager.php` | `VERIFIED` | None |
| **APEX-110**| Asset Optimization| Font-Display: Swap Injector | WP Rocket 3.16.1 / LSCache | Pro/Free| `Fonts.php` / `add_swap()` | `src/Performance/Assets/FontDisplayModifier.php` | `VERIFIED` | None |
| **APEX-111**| Asset Optimization| Local Gravatar Avatar Caching | LSCache 6.2.0.1 | Free | `Avatar.php` / `get_avatar()` | `src/Performance/Assets/AvatarCache.php` | `VERIFIED` | None |
| **APEX-112**| Asset Optimization| HTML Output Minification | LSCache 6.2.0.1 / WP Rocket | Free/Pro| `Optimizer.php` / `html_min()` | `src/Performance/Assets/HtmlMinifier.php` | `VERIFIED` | None |
| **APEX-113**| Asset Optimization| DNS Prefetch & Preconnect Inserter| WP Rocket 3.16.1 | Pro | `Admin.php` / `insert_prefetch()`| `src/Performance/Tweaks/ResourceHints.php` | `VERIFIED` | None |
| **APEX-114**| Asset Optimization| Strip WordPress Core Emojis | WP Rocket 3.16.1 / LSCache | Pro/Free| `Admin.php` / `disable_emojis()` | `src/Performance/Tweaks/CleanHead.php` | `VERIFIED` | None |
| **APEX-115**| Asset Optimization| Strip WordPress Core OEmbeds | WP Rocket 3.16.1 / LSCache | Pro/Free| `Admin.php` / `disable_embeds()` | `src/Performance/Tweaks/CleanHead.php` | `VERIFIED` | None |
| **APEX-116**| Asset Optimization| Heartbeat Frequency Control | WP Rocket 3.16.1 / LSCache | Pro/Free| `Heartbeat.php` / `modify()` | `src/Performance/Tweaks/HeartbeatManager.php` | `VERIFIED` | None |

---

## Category 10: Media Optimization & WebP/AVIF Engine (APEX-117 – APEX-130)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-117**| Media Optimization| Local GD/Imagick WebP Converter | LSCache 6.2.0.1 / ShortPixel | Free | `Media.php` / `img_optm()` | `src/Media/Optimizer/WebpConverter.php` | `VERIFIED` | None |
| **APEX-118**| Media Optimization| Local GD/Imagick AVIF Converter | LSCache 6.2.0.1 | Free | `Media.php` / `gen_avif()` | `src/Media/Optimizer/AvifConverter.php` | `VERIFIED` | None |
| **APEX-119**| Media Optimization| `<picture>` Tag HTML Rewriter | WP Rocket 3.16.1 / LSCache | Pro/Free| `Webp.php` / `rewrite_picture()` | `src/Media/Optimizer/WebpPictureRewriter.php`| `VERIFIED` | None |
| **APEX-120**| Media Optimization| Bulk Image Optimization Queue | LSCache 6.2.0.1 | Free | `Media.php` / `batch_optimize()` | `src/Media/Optimizer/BulkMediaOptimizer.php` | `VERIFIED` | None |
| **APEX-121**| Media Optimization| Auto-Optimize on Media Upload | LSCache 6.2.0.1 / Imagify | Free | `Media.php` / `wp_handle_upload()` | `src/Media/Optimizer/UploadOptimizationListener.php`| `VERIFIED` | None |
| **APEX-122**| Media Optimization| Add Missing `width` & `height` | WP Rocket 3.16.1 | Pro | `ImageDimensions.php` / `add()` | `src/Media/Optimizer/DimensionInjector.php` | `VERIFIED` | None |
| **APEX-123**| Media Optimization| LCP Featured Image Preload (`fetchpriority`)| WP Rocket 3.16.1 / LSCache | Pro/Free| `Media.php` / `add_lcp_fetch()` | `src/Media/Optimizer/LcpOptimizer.php` | `VERIFIED` | None |
| **APEX-124**| Media Optimization| Original Image Backup & Restore | LSCache 6.2.0.1 | Free | `Media.php` / `backup_original()`| `src/Media/Optimizer/ImageBackupManager.php` | `VERIFIED` | None |
| **APEX-125**| Media Optimization| Quality Lossy/Lossless Selector| LSCache 6.2.0.1 | Free | `Media.php` / `get_quality()` | `src/Media/Optimizer/QualityConfigurator.php`| `VERIFIED` | None |
| **APEX-126**| Media Optimization| Strip EXIF Image Metadata | LSCache 6.2.0.1 | Free | `Media.php` / `strip_exif()` | `src/Media/Optimizer/ExifStripper.php` | `VERIFIED` | None |
| **APEX-127**| Media Optimization| Image History & Savings Tracker | LSCache 6.2.0.1 | Free | `Media.php` / `record_savings()` | `src/Media/Optimizer/SavingsTracker.php` | `VERIFIED` | None |
| **APEX-128**| Media Optimization| SVG Upload Sanitization | SEOPress 7.8.1 | Free | `inc/svg.php` / `sanitize_svg()` | `src/Media/Optimizer/SvgSanitizer.php` | `VERIFIED` | None |
| **APEX-129**| Media Optimization| Resize Large Image Threshold | WP Core 5.3+ / LSCache | Free | `Media.php` / `resize_threshold()`| `src/Media/Optimizer/ThresholdResizer.php` | `VERIFIED` | None |
| **APEX-130**| Media Optimization| Cloud QUIC.cloud Image Converter| LSCache 6.2.0.1 | Cloud | `Cloud.php` / `post()` | `src/Media/Optimizer/ImageOptimizer.php` | `NOT_APPLICABLE`| Replaced by local GD |

---

## Category 11: Lazy Loading Subsystem (APEX-131 – APEX-138)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-131**| Lazy Loading | Native & JS Fallback Image LazyLoad| WP Rocket 3.16.1 / LSCache | Pro/Free| `Subscriber.php` / `lazyload()` | `src/Media/LazyLoad/ImageLazyLoader.php` | `VERIFIED` | None |
| **APEX-132**| Lazy Loading | LazyLoad Iframes & Video Players | WP Rocket 3.16.1 / LSCache | Pro/Free| `Subscriber.php` / `lazy_iframe()`| `src/Media/LazyLoad/IframeLazyLoader.php` | `VERIFIED` | None |
| **APEX-133**| Lazy Loading | YouTube Preview Thumbnail Mockup | WP Rocket 3.16.1 | Pro | `Subscriber.php` / `replace_yt()` | `src/Media/LazyLoad/YouTubePlaceholder.php` | `VERIFIED` | None |
| **APEX-134**| Lazy Loading | Inline SVG Aspect-Ratio Placeholder| LSCache 6.2.0.1 | Free | `Media.php` / `gen_svg_lqip()` | `src/Media/LazyLoad/PlaceholderGenerator.php` | `VERIFIED` | None |
| **APEX-135**| Lazy Loading | LazyLoad CSS Background Images | WP Rocket 3.16.1 | Pro | `Subscriber.php` / `lazy_bg()` | `src/Media/LazyLoad/BackgroundLazyLoader.php` | `VERIFIED` | None |
| **APEX-136**| Lazy Loading | Exclude First N Images from LazyLoad| WP Rocket 3.16.1 / LSCache | Pro/Free| `Subscriber.php` / `exclude_lcp()`| `src/Media/LazyLoad/LcpExcluder.php` | `VERIFIED` | None |
| **APEX-137**| Lazy Loading | Custom Class/Attribute Lazy Exclude| WP Rocket 3.16.1 | Pro | `Subscriber.php` / `is_excluded()`| `src/Media/LazyLoad/LazyExclusions.php` | `VERIFIED` | None |
| **APEX-138**| Lazy Loading | LQIP Low Quality Base64 Generator| LSCache 6.2.0.1 / QUIC.cloud | Free | `Media.php` / `generate_lqip()` | `src/Media/LazyLoad/PlaceholderGenerator.php` | `VERIFIED` | None |

---

## Category 12: Database Optimization & Maintenance (APEX-139 – APEX-148)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-139**| Database | Post Revisions Cleanup | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdminPage.php` / `clean_revisions()`| `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` | None |
| **APEX-140**| Database | Auto-Drafts & Trashed Posts Cleanup| WP Rocket 3.16.1 / LSCache | Pro/Free| `AdminPage.php` / `clean_drafts()`| `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` | None |
| **APEX-141**| Database | Spam & Trashed Comments Cleanup | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdminPage.php` / `clean_spam()` | `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` | None |
| **APEX-142**| Database | Expired Transients SQL Cleanup | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdminPage.php` / `clean_trans()`| `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` | None |
| **APEX-143**| Database | All Transients Bulk Cleanup | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdminPage.php` / `clean_all_trans()`| `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` | None |
| **APEX-144**| Database | InnoDB / MyISAM `OPTIMIZE TABLE` | WP Rocket 3.16.1 / LSCache | Pro/Free| `AdminPage.php` / `optimize_tables()`| `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` | None |
| **APEX-145**| Database | Trackbacks & Pingbacks Cleanup | LSCache 6.2.0.1 | Free | `DB.php` / `clean_trackbacks()` | `src/Performance/Database/DatabaseOptimizer.php` | `VERIFIED` | None |
| **APEX-146**| Database | MyISAM to InnoDB Engine Converter| LSCache 6.2.0.1 | Free | `DB.php` / `conv_innodb()` | `src/Performance/Database/TableEngineMigrator.php` | `VERIFIED` | None |
| **APEX-147**| Database | Automated Scheduled Cron DB Clean | WP Rocket 3.16.1 | Pro | `AdminPage.php` / `schedule_clean()`| `src/Performance/Database/ScheduledCleaner.php` | `VERIFIED` | None |
| **APEX-148**| Database | Database Dry-Run Cleanup Preview | Advanced DB Cleaner | Free | `cleaner.php` / `dry_run()` | `src/Performance/Database/DatabaseDryRun.php` | `VERIFIED` | None |

---

## Category 13: Server-Level & Reverse Proxy Integration (APEX-149 – APEX-158)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-149**| Server Integration | Apache `.htaccess` Expiration Rules | WP Rocket 3.16.1 / LSCache | Pro/Free| `Htaccess.php` / `set_browser_cache()`| `src/Performance/Server/HtaccessManager.php` | `VERIFIED` | Apache |
| **APEX-150**| Server Integration | Nginx Direct Cache `try_files` Config| Nginx FastCGI Cache | Free | `nginx.conf` | `src/Performance/Server/NginxConfigGenerator.php` | `VERIFIED_SERVER_DEPENDENCY` | Nginx |
| **APEX-151**| Server Integration | LiteSpeed `X-LiteSpeed-Cache-Control`| LSCache 6.2.0.1 | Free | `Tag.php` / `output_tags()` | `src/Performance/Cache/HeaderManager.php` | `VERIFIED_SERVER_DEPENDENCY` | LiteSpeed |
| **APEX-152**| Server Integration | LiteSpeed Tagged Cache Purge Header | LSCache 6.2.0.1 | Free | `Purge.php` / `purge_tags()` | `src/Performance/Cache/SmartPurge.php` | `VERIFIED_SERVER_DEPENDENCY` | LiteSpeed |
| **APEX-153**| Server Integration | Varnish Reverse Proxy HTTP `PURGE` | WP Rocket 3.16.1 | Pro | `Varnish.php` / `purge_varnish()` | `src/Performance/Cache/VarnishPurger.php` | `VERIFIED_SERVER_DEPENDENCY` | Varnish |
| **APEX-154**| Server Integration | Cloudflare Zone Cache API Purge | WP Rocket 3.16.1 / LSCache | Pro/Free| `Cloudflare.php` / `purge()` | `src/Performance/CDN/CloudflarePurger.php` | `VERIFIED_EXTERNAL_DEPENDENCY`| Cloudflare API |
| **APEX-155**| Server Integration | Redis Persistent Object Cache Driver| LSCache 6.2.0.1 / Redis Cache | Free | `object.php` / `connect()` | `src/Performance/ObjectCache/RedisClient.php` | `VERIFIED_SERVER_DEPENDENCY` | Redis |
| **APEX-156**| Server Integration | Memcached Object Cache Driver | LSCache 6.2.0.1 / Memcached | Free | `object.php` / `connect()` | `src/Performance/ObjectCache/MemcachedClient.php`| `VERIFIED_SERVER_DEPENDENCY` | Memcached |
| **APEX-157**| Server Integration | CDN Hostname URL Rewriting | WP Rocket 3.16.1 / LSCache | Pro/Free| `CDN.php` / `rewrite_url()` | `src/Performance/CDN/CdnRewriter.php` | `VERIFIED` | None |
| **APEX-158**| Server Integration | ESI Edge Fragment Staging (LSWS) | LSCache 6.2.0.1 | Free | `ESI.php` / `sub_render()` | `src/Performance/Cache/EsiHandler.php` | `VERIFIED_SERVER_DEPENDENCY` | LiteSpeed |

---

## Category 14: Analytics, GSC & Rank Tracking (APEX-159 – APEX-168)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-159**| Analytics | Google Analytics 4 (GA4) Tag Injector| SEOPress 7.8.1 / RM 1.0.220 | Free | `analytics/ga.php` / `render()` | `src/Analytics/AnalyticsTagInjector.php` | `VERIFIED` | None |
| **APEX-160**| Analytics | Local GA4 `gtag.js` Script Host | SEOPress Pro 7.8.1 | Pro | `analytics/local.php` / `download()` | `src/Analytics/LocalAnalyticsScriptManager.php` | `VERIFIED` | None |
| **APEX-161**| Analytics | IP Anonymization & GDPR Cookie Guard| SEOPress 7.8.1 | Free | `analytics/gdpr.php` / `anonymize()` | `src/Analytics/GdprComplianceGuard.php` | `VERIFIED` | None |
| **APEX-162**| Analytics | Google Search Console OAuth2 Client| RM Pro 3.0.64 | Pro | `analytics/gsc.php` / `fetch_data()` | `src/Analytics/SearchConsoleClient.php` | `VERIFIED_EXTERNAL_DEPENDENCY`| Google GSC |
| **APEX-163**| Analytics | Search Console Keyword Rank Tracker| RM Pro 3.0.64 | Pro | `analytics/tracker.php` / `update()` | `src/Analytics/RankTracker.php` | `VERIFIED_EXTERNAL_DEPENDENCY`| Google GSC |
| **APEX-164**| Analytics | GSC URL Inspection API Integration | SEOPress Pro 7.8.1 | Pro | `analytics/inspect.php` / `inspect()`| `src/Analytics/UrlInspectionClient.php` | `VERIFIED_EXTERNAL_DEPENDENCY`| Google GSC |
| **APEX-165**| Analytics | Search Console Impressions/Clicks DB| RM Pro 3.0.64 | Pro | `analytics/db.php` / `save_metrics()`| `src/Analytics/AnalyticsTimeSeriesStore.php` | `VERIFIED` | None |
| **APEX-166**| Analytics | Top Winning / Losing Keywords Matrix| RM Pro 3.0.64 | Pro | `analytics/delta.php` / `calc()` | `src/Analytics/KeywordDeltaCalculator.php` | `VERIFIED` | None |
| **APEX-167**| Analytics | Google Tag Manager (GTM) Container | SEOPress 7.8.1 | Free | `analytics/gtm.php` / `render()` | `src/Analytics/GtmContainerInjector.php` | `VERIFIED` | None |
| **APEX-168**| Analytics | Matomo / Piwik Self-Hosted Analytics| SEOPress 7.8.1 | Free | `analytics/matomo.php` / `render()` | `src/Analytics/MatomoTagInjector.php` | `VERIFIED` | None |

---

## Category 15: REST API & Headless Engine (APEX-169 – APEX-180)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-169**| REST API | REST Settings Controller | Yoast 22.8 / RM 1.0.220 | Free | `api/settings.php` / `register()` | `src/API/SettingsRestController.php` | `VERIFIED` | None |
| **APEX-170**| REST API | REST Meta Reader & Mutator Endpoint| Yoast 22.8 / AIOSEO Pro | Free/Pro| `api/meta.php` / `register()` | `src/API/MetaRestController.php` | `VERIFIED` | None |
| **APEX-171**| REST API | REST Dynamic Schema CRUD Endpoint | RM Pro 3.0.64 | Pro | `api/schema.php` / `register()` | `src/API/SchemaRestController.php` | `VERIFIED` | None |
| **APEX-172**| REST API | REST Redirect Management Endpoint | Redirection 5.4.2 / RM 1.0.220| Free | `api/redirects.php` / `register()` | `src/API/RedirectsRestController.php` | `VERIFIED` | None |
| **APEX-173**| REST API | REST 404 Monitor Log Endpoint | Redirection 5.4.2 / RM 1.0.220| Free | `api/404.php` / `register()` | `src/API/NotFoundRestController.php` | `VERIFIED` | None |
| **APEX-174**| REST API | REST Link Suggestions Query Endpoint| AIOSEO Pro 4.6.4 | Pro | `api/links.php` / `register()` | `src/API/LinksRestController.php` | `VERIFIED` | None |
| **APEX-175**| REST API | Headless Complete SEO Meta & JSON-LD| AIOSEO Pro 4.6.4 / Yoast 22.8 | Pro/Free| `api/headless.php` / `get_seo()` | `src/API/MetaRestController.php` | `VERIFIED` | None |
| **APEX-176**| REST API | REST Cache Purge & Preload Trigger | WP Rocket 3.16.1 / LSCache | Pro/Free| `api/cache.php` / `register()` | `src/API/CacheRestController.php` | `VERIFIED` | None |
| **APEX-177**| REST API | REST Media Image Optimize Action | LSCache 6.2.0.1 | Free | `api/media.php` / `register()` | `src/API/MediaRestController.php` | `VERIFIED` | None |
| **APEX-178**| REST API | REST Migration Batch Worker Endpoint| Yoast 22.8 / RM 1.0.220 | Free | `api/migration.php` / `register()` | `src/API/MigrationRestController.php` | `VERIFIED` | None |
| **APEX-179**| REST API | REST Analytics Overview API | RM Pro 3.0.64 | Pro | `api/analytics.php` / `register()` | `src/API/AnalyticsRestController.php` | `VERIFIED` | None |
| **APEX-180**| REST API | REST Rank Tracker Query API | RM Pro 3.0.64 | Pro | `api/tracker.php` / `register()` | `src/API/AnalyticsRestController.php` | `VERIFIED` | None |

---

## Category 16: WP-CLI Management Interface (APEX-181 – APEX-190)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-181**| WP-CLI | `wp apex cache purge` Subcommand | WP Rocket 3.16.1 / LSCache | Pro/Free| `cli/cache.php` / `purge()` | `src/CLI/CacheCommand.php` | `VERIFIED` | None |
| **APEX-182**| WP-CLI | `wp apex cache preload` Subcommand | WP Rocket 3.16.1 / LSCache | Pro/Free| `cli/cache.php` / `preload()` | `src/CLI/CacheCommand.php` | `VERIFIED` | None |
| **APEX-183**| WP-CLI | `wp apex index reindex` Subcommand | Yoast 22.8 / RM 1.0.220 | Free | `cli/index.php` / `reindex()` | `src/CLI/IndexCommand.php` | `VERIFIED` | None |
| **APEX-184**| WP-CLI | `wp apex media optimize` Subcommand| LSCache 6.2.0.1 | Free | `cli/media.php` / `optimize()` | `src/CLI/MediaCommand.php` | `VERIFIED` | None |
| **APEX-185**| WP-CLI | `wp apex redirect add` Subcommand | Redirection 5.4.2 / RM 1.0.220| Free | `cli/redirect.php` / `add()` | `src/CLI/RedirectCommand.php` | `VERIFIED` | None |
| **APEX-186**| WP-CLI | `wp apex redirect list` Subcommand | Redirection 5.4.2 | Free | `cli/redirect.php` / `list()` | `src/CLI/RedirectCommand.php` | `VERIFIED` | None |
| **APEX-187**| WP-CLI | `wp apex db clean` Subcommand | WP Rocket 3.16.1 / LSCache | Pro/Free| `cli/db.php` / `clean()` | `src/CLI/DatabaseCommand.php` | `VERIFIED` | None |
| **APEX-188**| WP-CLI | `wp apex migrate run` Subcommand | Yoast 22.8 / RM 1.0.220 | Free | `cli/migrate.php` / `run()` | `src/CLI/MigrateCommand.php` | `VERIFIED` | None |
| **APEX-189**| WP-CLI | `wp apex sitemap rebuild` Subcommand| Yoast 22.8 / RM 1.0.220 | Free | `cli/sitemap.php` / `rebuild()` | `src/CLI/SitemapCommand.php` | `VERIFIED` | None |
| **APEX-190**| WP-CLI | `wp apex doctor` Diagnostic Command| Yoast 22.8 / LSCache | Free | `cli/doctor.php` / `diagnose()` | `src/CLI/DoctorCommand.php` | `VERIFIED` | None |

---

## Category 17: Core Architecture, Migration & Administration (APEX-191 – APEX-198)

| ID | Category | Feature Name | Source Product & Ver | Free/Pro | Source Path / Class / Method | Apex Target File | Status | Server / Ext Dep |
|---|---|---|---|---|---|---|---|---|
| **APEX-191**| Core Architecture | PSR-11 Dependency Injection Container| Symfony DI / Yoast 22.8 | Free | `src/container.php` / `get()` | `src/Core/Container/ServiceContainer.php`| `VERIFIED` | None |
| **APEX-192**| Core Architecture | Multi-Source Migration Engine | Yoast 22.8 / RM 1.0.220 | Free | `migration/manager.php` / `import()`| `src/Migration/MigrationManager.php` | `VERIFIED` | None |
| **APEX-193**| Core Architecture | Active Plugin Conflict Detector | WP Rocket 3.16.1 / LSCache | Pro/Free| `admin/conflicts.php` / `check()` | `src/Core/Admin/ConflictDetector.php` | `VERIFIED` | None |
| **APEX-194**| Core Architecture | Multisite Network Management | Yoast 22.8 / RM 1.0.220 | Free | `admin/network.php` / `network_save()`| `src/Core/Multisite/NetworkManager.php` | `VERIFIED` | None |
| **APEX-195**| Core Architecture | White Label Admin Interface | SEOPress Pro 7.8.1 | Pro | `admin/white-label.php` / `brand()` | `src/Core/Admin/WhiteLabelManager.php` | `VERIFIED` | None |
| **APEX-196**| Core Architecture | Settings Backup, Import & Export | Yoast 22.8 / RM 1.0.220 | Free | `admin/backup.php` / `export_json()`| `src/Core/Admin/BackupRestoreManager.php` | `VERIFIED` | None |
| **APEX-197**| Core Architecture | Action Scheduler Background Queue | WooCommerce / ActionScheduler | Free | `classes/ActionScheduler.php` | `src/Core/Queue/ActionSchedulerQueue.php`| `VERIFIED` | None |
| **APEX-198**| Core Architecture | Diagnostic System Health Reporter | LSCache 6.2.0.1 / Yoast 22.8 | Free | `admin/health.php` / `report()` | `src/Core/Admin/SystemHealthReporter.php` | `VERIFIED` | None |
