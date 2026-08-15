# 10 - Admin UI, MetaBoxes & Media Integration Specification

## 1. WordPress Admin Menu Structure
Apex SEO registers a top-level admin menu with logically grouped submenus:

```
Apex SEO (Menu Slug: 'apex-seo')
├── Dashboard               (apex-seo)
├── SEO Settings            (apex-seo-titles)
│   ├── General Titles & Meta
│   ├── Post Types Defaults
│   ├── Taxonomies Defaults
│   ├── Author & Date Archives
│   ├── Social (Open Graph / Twitter)
│   ├── Breadcrumbs
│   └── Robots & Advanced
├── Schema Engine           (apex-seo-schema)
│   ├── Built-in Types (52)
│   ├── Custom Schemas
│   ├── Display Conditions
│   └── Schema Validator
├── Content & Links         (apex-seo-content)
│   ├── Content Analysis
│   ├── Readability
│   ├── Internal Links Index
│   └── Orphan Content
├── Redirects & 404         (apex-seo-redirects)
│   ├── Redirect Rules (301/302/Regex)
│   ├── 404 Request Monitor
│   └── Auto-Redirect Settings
├── Sitemaps                (apex-seo-sitemaps)
│   ├── XML Sitemap Settings
│   ├── Post Types & Taxonomies Inclusion
│   └── HTML Sitemap
├── Media & Images          (apex-seo-media)
│   ├── Image Compression Settings
│   ├── WebP & AVIF Generation
│   ├── Auto Image SEO (ALT/Title)
│   └── Bulk Optimization Queue
├── Performance & Cache     (apex-seo-performance)
│   ├── Page Cache (Desktop/Mobile)
│   ├── CSS & JS Optimization (Defer/Delay)
│   ├── Fonts & Lazy Loading
│   └── Redis / Memcached Status
├── AI & Visibility         (apex-seo-ai)
│   ├── AI Bot Access (GPTBot/ClaudeBot)
│   ├── Virtual llms.txt Editor
│   └── Gemini API Content Assistant
├── Analytics & Indexing    (apex-seo-analytics)
│   ├── Search Console Integration
│   ├── Instant Indexing (Google & IndexNow)
│   └── Rank Tracker
├── Database Cleaner        (apex-seo-database)
│   ├── Revisions & Transients Cleanup
│   └── Autoload Options Analyzer
├── Tools & Migration       (apex-seo-tools)
│   ├── One-Click Migration (Yoast, Rank Math, AIOSEO, SEOPress)
│   ├── Export / Import Settings
│   └── System Status & Diagnostics
└── Settings                (apex-seo-settings)
```

---

## 2. Universal Post MetaBox Specification
Available on all public post types (`post`, `page`, WooCommerce `product`, and custom CPTs):

- **Tab 1: General**: Focus Keyword, Snippet Preview (Desktop/Mobile), Title Editor, Meta Description Editor, Canonical Override.
- **Tab 2: Social**: Custom Facebook/OpenGraph Title, Description, Image; Twitter Card Type, Title, Image.
- **Tab 3: Schema**: Selected Schema Type, Custom Property Overrides, Primary Entity Toggle.
- **Tab 4: Content Analysis**: Real-time scores for Keyword Density, Headings, Link counts, Readability (Flesch Ease).
- **Tab 5: AI & GEO**: AI Citability Score, FAQ Generation, AI Snippet Suggestions via Gemini API.
- **Tab 6: Advanced**: Meta Robots (`noindex`, `nofollow`, `noarchive`), Breadcrumb Title Override, Redirect URL.

---

## 3. Attachment Media Screen & Columns
- **Media List Table (`upload.php`)**: Optimization Status, Original Size, Compressed Size, Savings %, WebP Status, AVIF Status, Image SEO Score.
- **Bulk Actions**: `Optimize Images`, `Generate WebP`, `Generate AVIF`, `Restore Originals`.
