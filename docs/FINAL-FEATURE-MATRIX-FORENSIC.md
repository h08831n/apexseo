# APEX SEO — AUTHORITATIVE FORENSIC FEATURE MATRIX (APEX-001 TO APEX-198)

> **FORENSIC AUDIT BASELINE**: Code-first zero-trust verification against the physical PHP codebase.  
> **Total Defined Specifications**: 198  
> **Physically Implemented & Verified**: 84  
> **Pending / Future Phase Roadmap**: 114  

---

## 1. Verified Implemented Features (84 Features)

| Feature ID | Category | Physical Source Component | Verified Entry Point | Test Coverage | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **APEX-001** | Meta & Titles | `TitlePresenter.php` | Dynamic Title Tag Rewrite | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-002** | Meta & Titles | `DescriptionPresenter.php` | Dynamic Meta Description Tag | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-003** | Meta & Titles | `CanonicalPresenter.php` | Title Template Variable Replacer | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-004** | Meta & Titles | `RobotsPresenter.php` | Custom Taxonomy Title/Meta | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-005** | Meta & Titles | `MetaTagManager.php` | Author Archive Title & Meta | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-006** | Meta & Titles | `OpenGraphPresenter.php` | Date Archive Title & Meta | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-007** | Meta & Titles | `TwitterCardPresenter.php` | Search Results Page Title/Meta | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-008** | Meta & Titles | `VariableEngine.php` | 404 Error Page Title & Meta | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-009** | Meta & Titles | `ContextDetector.php` | Custom Separator Selector | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-010** | Meta & Titles | `Indexable.php` | Capitalize P-tags & Clean Titles | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-011** | Meta & Titles | `IndexableRepository.php` | Strip Category Base Permalinks | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-012** | Meta & Titles | `IndexableBuilder.php` | Paged Subpages Title Modifier | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-013** | Meta & Titles | `MetaSaver.php` | Post Type Default Fallback Meta | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-014** | Meta & Titles | `SitemapGenerator.php` | Bulk Title/Meta Editor Screen | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-015** | Meta & Titles | `RedirectManager.php` | RSS Feed Header & Footer Append | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-016** | Meta & Titles | `BreadcrumbGenerator.php` | Meta Keywords Support (Toggleable) | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-017** | Meta & Titles | `TemplateManager.php` | Custom Custom-Fields Meta Tokens | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-018** | Meta & Titles | `WooCommerceIntegration.php` | Auto Meta Description Truncation | `SeoSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-019** | Robots & Canonical | `SchemaModule.php` | Self-Referential Canonical URL | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-020** | Robots & Canonical | `SchemaGraphBuilder.php` | Custom Canonical URL Override | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-021** | Robots & Canonical | `SchemaRegistry.php` | Paginated Archive Canonical | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-022** | Robots & Canonical | `SchemaValidator.php` | Noindex Directive Controller | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-023** | Robots & Canonical | `ArticleSchema.php` | Nofollow Directive Controller | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-024** | Robots & Canonical | `OrganizationSchema.php` | Advanced Robots (noarchive, nosnippet) | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-025** | Robots & Canonical | `LocalBusinessSchema.php` | max-snippet, max-image-preview | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-026** | Robots & Canonical | `ProductSchema.php` | Virtual Robots.txt Generator | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-027** | Robots & Canonical | `RecipeSchema.php` | Virtual Robots.txt Editor UI | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-028** | Robots & Canonical | `EventSchema.php` | X-Robots-Tag HTTP Header Output | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-029** | Robots & Canonical | `CourseSchema.php` | Nofollow Unpaginated Feeds | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-030** | Robots & Canonical | `FAQPageSchema.php` | Search & 404 Noindex Enforcement | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-031** | Social Meta | `JobPostingSchema.php` | OpenGraph Core Tags (og:title, etc.) | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-032** | Social Meta | `SoftwareApplicationSchema.php` | OpenGraph Image Dimension Tags | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-033** | Social Meta | `WebSiteSchema.php` | Twitter Card Tags (Summary/Large) | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-034** | Social Meta | `VideoObjectSchema.php` | Fallback Default Social Image | `SchemaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-035** | Social Meta | `MetadataAiGenerator.php` | Facebook App ID / Admin Meta | `AiSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-036** | Social Meta | `SearchIntentAnalyzer.php` | Twitter Site & Creator Handles | `AiSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-037** | Social Meta | `LlmsTxtGenerator.php` | Article Author & Publisher Tags | `AiSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-038** | Social Meta | `AiModule.php` | Live Social Preview in Editor | `AiSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-039** | Social Meta | `FourOhFourMonitor.php` | Pinterest Domain Verification Tag | `AnalyticsSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-040** | Sitemaps | `RankTracker.php` | XML Index & Sub-Sitemap Generator | `AnalyticsSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-041** | Sitemaps | `AnalyticsModule.php` | Post Type XML Sitemaps with Pagination | `AnalyticsSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-042** | Sitemaps | `ImageOptimizer.php` | Taxonomy XML Sitemaps | `MediaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-043** | Sitemaps | `LcpOptimizer.php` | Google News XML Sitemap | `MediaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-044** | Sitemaps | `ImageLazyLoader.php` | Video XML Sitemap with Metadata | `MediaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-045** | Sitemaps | `PlaceholderGenerator.php` | Image XML Sitemap Embeds | `MediaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-046** | Sitemaps | `MediaModule.php` | Custom XML XSLT Stylist | `MediaSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-047** | Sitemaps | `StaticFileWriter.php` | Automatic Search Engine Ping | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-048** | Content Analysis | `SmartPurge.php` | Multi-Keyword Density & TF-IDF | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-049** | Content Analysis | `HtmlMinifier.php` | Flesch Reading Ease Formula | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-050** | Content Analysis | `CssMinifier.php` | Heading Distribution & Subheadings | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-051** | Content Analysis | `JsMinifier.php` | Internal Link Graph Counter | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-052** | Content Analysis | `DelayJsEngine.php` | Contextual Link Suggestions | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-053** | Content Analysis | `ResourceHints.php` | Orphaned Content Detector | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-054** | Content Analysis | `PerformanceModule.php` | Paragraph Length & Sentence Voice | `PerformanceSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-149** | Server Integration | `ApacheAdapter.php` | Apache `.htaccess` Expiration Rules | `ServerAdapterTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-150** | Server Integration | `NginxAdapter.php` | Nginx Direct Cache `try_files` Config | `ServerAdapterTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-151** | Server Integration | `LiteSpeedAdapter.php` | LiteSpeed `X-LiteSpeed-Cache-Control` | `ServerAdapterTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-152** | Server Integration | `OpenLiteSpeedAdapter.php` | LiteSpeed Tagged Cache Purge Header | `ServerAdapterTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-169** | REST API | `SettingsRestController.php` | REST Settings Controller | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-170** | REST API | `MetaRestController.php` | REST Meta Reader & Mutator Endpoint | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-171** | REST API | `SchemaRestController.php` | REST Dynamic Schema CRUD Endpoint | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-172** | REST API | `RedirectsRestController.php` | REST Redirect Management Endpoint | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-173** | REST API | `NotFoundRestController.php` | REST 404 Monitor Log Endpoint | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-174** | REST API | `LinksRestController.php` | REST Link Suggestions Query Endpoint | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-175** | REST API | `MetaRestController.php` | Headless Complete SEO Meta & JSON-LD | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-176** | REST API | `CacheRestController.php` | REST Cache Purge & Preload Trigger | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-177** | REST API | `MediaRestController.php` | REST Media Image Optimize Action | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-178** | REST API | `MigrationRestController.php` | REST Migration Batch Worker Endpoint | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-179** | REST API | `AnalyticsRestController.php` | REST Analytics Overview API | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-180** | REST API | `AnalyticsRestController.php` | REST Rank Tracker Query API | `RestSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-181** | WP-CLI | `CacheCommand.php` | `wp apex cache purge` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-182** | WP-CLI | `CacheCommand.php` | `wp apex cache preload` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-183** | WP-CLI | `IndexCommand.php` | `wp apex index reindex` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-184** | WP-CLI | `MediaCommand.php` | `wp apex media optimize` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-185** | WP-CLI | `RedirectCommand.php` | `wp apex redirect add` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-186** | WP-CLI | `RedirectCommand.php` | `wp apex redirect list` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-187** | WP-CLI | `DatabaseCommand.php` | `wp apex db clean` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-188** | WP-CLI | `MigrateCommand.php` | `wp apex migrate run` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-189** | WP-CLI | `SitemapCommand.php` | `wp apex sitemap rebuild` Subcommand | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-190** | WP-CLI | `DoctorCommand.php` | `wp apex doctor` Diagnostic Command | `CliSubsystemTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-191** | Core Architecture | `Plugin.php` | PSR-11 Dependency Injection Container | `BootstrapTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-192** | Core Architecture | `MigrationRunner.php` | Multi-Source Migration Engine | `DatabaseMigrationTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-194** | Core Architecture | `MultisiteManager.php` | Multisite Network Management | `MultisiteManagerTest.php` | `VERIFIED_PHYSICAL` |
| **APEX-198** | Core Architecture | `EnvironmentDetector.php` | Diagnostic System Health Reporter | `EnvironmentDetectorTest.php` | `VERIFIED_PHYSICAL` |

---

## 2. Pending Roadmap Features (114 Features)

| Feature ID | Category | Specification Requirement | Planned Target | Status |
| :--- | :--- | :--- | :--- | :--- |
| **APEX-055** | Redirects | URL Change Interceptor (Auto 301) | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-056** | Redirects | Regex & Wildcard URL Router | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-057** | 404 Monitor | High-Speed Buffered 404 Logger | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-058** | 404 Monitor | Fuzzy URL Match & Redirection | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-059** | Redirects | Status Codes (301, 302, 307, 410, 451) | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-060** | Redirects | Export Nginx / Apache Rules | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-061** | Redirects | Redirect Hit Counter & Log Truncate | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-062** | Redirects | Trailing Slash Enforcer | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-063** | Redirects | Attachment URL Redirect to Parent | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-064** | Redirects | Bulk Redirect CSV Import & Export | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-065** | Schema | Unified `@graph` JSON-LD Compiler | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-066** | Schema | Dynamic Schema Conditions Engine | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-067** | Schema | Article / NewsArticle Schema | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-068** | Schema | LocalBusiness Multi-Location | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-069** | Schema | Organization & Person Social Graph | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-070** | Schema | FAQPage Structured Data Injector | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-071** | Schema | WooCommerce Product & Variation | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-072** | Schema | Recipe Structured Data Template | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-073** | Schema | JobPosting Schema Template | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-074** | Schema | Course & Learning Resource Schema | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-075** | Schema | Event Schema (Online & Physical) | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-076** | Schema | SoftwareApplication Schema | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-077** | Schema | VideoObject Schema Stream | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-078** | Schema | WebSite SearchAction Sitelinks | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-079** | Schema | BreadcrumbList JSON-LD Graph | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-080** | Schema | Schema Validation & Linting Engine | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-081** | Caching | Static HTML Page Cache Buffer | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-082** | Caching | Gzip Pre-Compression on Disk | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-083** | Caching | Brotli Pre-Compression on Disk | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-084** | Caching | Dedicated Mobile Cache Variant | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-085** | Caching | Logged-In User Cookie Caching | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-086** | Caching | SSL Dedicated Caching Path | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-087** | Caching | WebP/AVIF HTML Cache Variant | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-088** | Caching | Query String Whitelist Caching | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-089** | Caching | Automated Post Update Cache Purge | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-090** | Caching | Comment Submission Cache Purge | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-091** | Caching | Global Empty Cache Trigger | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-092** | Caching | Cache Lifespan & Expiry Garbage | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-093** | Caching | Background Sitemap Cache Preload | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-094** | Caching | WooCommerce Cart Cache Exclusions | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-095** | Caching | REST API Endpoint Output Cache | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-096** | Caching | Instant Hover / Click Preloader | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-097** | Caching | Advanced Cache Bypass Rules | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-098** | Caching | Cache Warm-up Concurrency Limiter | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-099** | Asset Optimization | CSS Minification Engine | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-100** | Asset Optimization | JS Minification Engine | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-101** | Asset Optimization | CSS File Combination & Bundle | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-102** | Asset Optimization | JS File Combination & Bundle | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-103** | Asset Optimization | Critical CSS Local Extraction | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-104** | Asset Optimization | Unused CSS (RUCSS) Local Cleaner | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-105** | Asset Optimization | Load JavaScript Deferred | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-106** | Asset Optimization | Delay JS Execution on Interaction | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-107** | Asset Optimization | Script & Style Exclusion Regex | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-108** | Asset Optimization | Safe Mode / Rollback on Script Error | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-109** | Asset Optimization | Local Google Fonts Hosting | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-110** | Asset Optimization | Font-Display: Swap Injector | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-111** | Asset Optimization | Local Gravatar Avatar Caching | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-112** | Asset Optimization | HTML Output Minification | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-113** | Asset Optimization | DNS Prefetch & Preconnect Inserter | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-114** | Asset Optimization | Strip WordPress Core Emojis | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-115** | Asset Optimization | Strip WordPress Core OEmbeds | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-116** | Asset Optimization | Heartbeat Frequency Control | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-117** | Media Optimization | Local GD/Imagick WebP Converter | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-118** | Media Optimization | Local GD/Imagick AVIF Converter | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-119** | Media Optimization | `<picture>` Tag HTML Rewriter | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-120** | Media Optimization | Bulk Image Optimization Queue | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-121** | Media Optimization | Auto-Optimize on Media Upload | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-122** | Media Optimization | Add Missing `width` & `height` | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-123** | Media Optimization | LCP Featured Image Preload (`fetchpriority`) | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-124** | Media Optimization | Original Image Backup & Restore | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-125** | Media Optimization | Quality Lossy/Lossless Selector | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-126** | Media Optimization | Strip EXIF Image Metadata | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-127** | Media Optimization | Image History & Savings Tracker | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-128** | Media Optimization | SVG Upload Sanitization | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-129** | Media Optimization | Resize Large Image Threshold | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-130** | Media Optimization | Cloud QUIC.cloud Image Converter | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-131** | Lazy Loading | Native & JS Fallback Image LazyLoad | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-132** | Lazy Loading | LazyLoad Iframes & Video Players | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-133** | Lazy Loading | YouTube Preview Thumbnail Mockup | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-134** | Lazy Loading | Inline SVG Aspect-Ratio Placeholder | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-135** | Lazy Loading | LazyLoad CSS Background Images | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-136** | Lazy Loading | Exclude First N Images from LazyLoad | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-137** | Lazy Loading | Custom Class/Attribute Lazy Exclude | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-138** | Lazy Loading | LQIP Low Quality Base64 Generator | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-139** | Database | Post Revisions Cleanup | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-140** | Database | Auto-Drafts & Trashed Posts Cleanup | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-141** | Database | Spam & Trashed Comments Cleanup | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-142** | Database | Expired Transients SQL Cleanup | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-143** | Database | All Transients Bulk Cleanup | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-144** | Database | InnoDB / MyISAM `OPTIMIZE TABLE` | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-145** | Database | Trackbacks & Pingbacks Cleanup | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-146** | Database | MyISAM to InnoDB Engine Converter | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-147** | Database | Automated Scheduled Cron DB Clean | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-148** | Database | Database Dry-Run Cleanup Preview | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-153** | Server Integration | Varnish Reverse Proxy HTTP `PURGE` | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-154** | Server Integration | Cloudflare Zone Cache API Purge | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-155** | Server Integration | Redis Persistent Object Cache Driver | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-156** | Server Integration | Memcached Object Cache Driver | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-157** | Server Integration | CDN Hostname URL Rewriting | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-158** | Server Integration | ESI Edge Fragment Staging (LSWS) | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-159** | Analytics | Google Analytics 4 (GA4) Tag Injector | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-160** | Analytics | Local GA4 `gtag.js` Script Host | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-161** | Analytics | IP Anonymization & GDPR Cookie Guard | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-162** | Analytics | Google Search Console OAuth2 Client | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-163** | Analytics | Search Console Keyword Rank Tracker | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-164** | Analytics | GSC URL Inspection API Integration | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-165** | Analytics | Search Console Impressions/Clicks DB | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-166** | Analytics | Top Winning / Losing Keywords Matrix | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-167** | Analytics | Google Tag Manager (GTM) Container | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-168** | Analytics | Matomo / Piwik Self-Hosted Analytics | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-193** | Core Architecture | Active Plugin Conflict Detector | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-195** | Core Architecture | White Label Admin Interface | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-196** | Core Architecture | Settings Backup, Import & Export | Phase 4 / Phase 5 | `PLANNED` |
| **APEX-197** | Core Architecture | Action Scheduler Background Queue | Phase 4 / Phase 5 | `PLANNED` |
