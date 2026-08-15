# Apex SEO Platform — Master Architecture Blueprint (Evidence-Locked)

## 1. Architectural Dependency Rules & Layering

To prevent tight coupling and ensure testability, the Apex SEO architecture strictly enforces directional dependency boundaries:

```
┌────────────────────────────────────────────────────────┐
│               Core / Domain Contracts                  │
│       (Interfaces, Models, Events, Value Objects)      │
└───────────────────────────┬────────────────────────────┘
                            │ (depends on)
┌───────────────────────────▼────────────────────────────┐
│                  Application Services                  │
│    (SEO Presenters, Schema Builders, Cache Engine)     │
└───────────────────────────┬────────────────────────────┘
                            │ (depends on)
┌───────────────────────────▼────────────────────────────┐
│                 Infrastructure Adapters                │
│    (LiteSpeed, Redis, Imagick, Cloudflare, OpenAI)     │
└───────────────────────────┬────────────────────────────┘
                            │ (depends on)
┌───────────────────────────▼────────────────────────────┐
│               WordPress Integration Layer              │
│    (Hooks, WP REST API, Settings API, WP-CLI, Admin)   │
└────────────────────────────────────────────────────────┘
```

### Strict Isolation Rules:
1. **Frontend Isolation**: Frontend request execution **MUST NOT** instantiate Admin UI controllers, Migration parsers, Schema editors, or Diagnostics loggers.
2. **Server Capability Decoupling**: Cache and Image optimization services **MUST NOT** directly invoke server-specific binaries (`avifenc`, `redis-cli`, `litespeed_finish_request`) without passing through the respective `CacheDriverInterface` or `ImageEncoderInterface` with environmental availability detection.
3. **Database Layering**: Admin and SEO controllers **MUST NOT** execute raw SQL queries directly against `$wpdb`. All queries must route through dedicated Repository and Model abstractions (`IndexableRepository`, `RedirectRepository`, `SchemaRepository`).
4. **AI & Cloud Decoupling**: AI capabilities and Instant Indexing must implement provider interfaces (`AIProviderInterface`, `IndexingProviderInterface`), preventing vendor lock-in.

---

## 2. Directory Structure Map
```
wp-content/plugins/apexseo/
├── apexseo.php                         (Main Plugin Bootstrap)
├── uninstall.php                        (Safe Cleanup Handler)
├── readme.txt                           (WP Plugin Header & Changelog)
├── composer.json                        (PSR-4 Autoloading Map)
├── phpunit.xml                          (Test Suite Configuration)
├── phpcs.xml                            (WordPress Coding Standards Config)
│
├── src/
│   ├── Core/                            (Bootstrap, Service Container, Event Dispatcher, Environment)
│   ├── SEO/                             (Titles, Meta, Robots, Canonical, Social, Indexables, Sitemaps, Redirects, 404)
│   ├── Schema/                          (62 Concrete Types, Graph Merger, Conditions ALL/ANY/NOT, Variables, Validator)
│   ├── Media/                           (Image Optimizer, WebP, AVIF, Image SEO, Queue, Media Library)
│   ├── Performance/                     (CSS, JavaScript Defer/Delay, Fonts, LazyLoad, Preload, Diagnostics)
│   ├── Cache/                           (Page Cache, Redis, Memcached, Purge Engine)
│   ├── Server/                          (LiteSpeed, OpenLiteSpeed, Nginx, Apache Adapters)
│   ├── CDN/                             (Cloudflare, Generic CDN URL Rewriter)
│   ├── AI/                              (AIVisibility, Crawlers, Virtual llms.txt, AIProviderInterface)
│   ├── Analytics/                       (Search Console, GA4, Matomo, Rank Tracker, Instant Indexing)
│   ├── Database/                        (8 Custom Tables, Schema Migrator, Optimizer)
│   ├── Migration/                       (7 Ecosystems: Yoast, RM, AIOSEO, SEOPress, TSF, WPRocket, LSCache)
│   ├── WooCommerce/                     (Product SEO, Schema, Shop Archive, Dynamic Cache Protections)
│   ├── API/                             (REST API Endpoints, Headless JSON Payload, Abilities API)
│   ├── CLI/                             (WP-CLI Command Suite: wp apexseo *)
│   └── Admin/                           (Menus, MetaBoxes, AdminBar, Diagnostics, Conflict Detector)
│
├── assets/
│   ├── css/                             (Admin & Metabox Styling, RTL Styles)
│   ├── js/                              (Admin Scripting, Real-Time Content Analyzer)
│   └── images/                          (Logos, Status Icons)
│
├── languages/                           (apex-seo.pot, Translation Catalogs)
├── tests/                               (Unit, Integration, Security, Schema Tests)
└── docs/                                (Master Architecture & Specifications Suite)
```

---

## 2. Service Provider & Dependency Injection Pipeline
Apex SEO uses a lightweight, zero-overhead Service Container (`ApexSEO\Core\Container`) where each subsystem is registered as a lazy-loaded service provider:

```php
namespace ApexSEO\Core;

class Plugin {
    private static ?Plugin $instance = null;
    private Container $container;

    public static function getInstance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->container = new Container();
        $this->registerProviders();
        $this->boot();
    }

    private function registerProviders(): void {
        $this->container->register(Database\DatabaseServiceProvider::class);
        $this->container->register(SEO\SEOServiceProvider::class);
        $this->container->register(Schema\SchemaServiceProvider::class);
        $this->container->register(Media\MediaServiceProvider::class);
        $this->container->register(Performance\PerformanceServiceProvider::class);
        $this->container->register(Cache\CacheServiceProvider::class);
        $this->container->register(AI\AIServiceProvider::class);
        $this->container->register(Analytics\AnalyticsServiceProvider::class);
        $this->container->register(API\APIServiceProvider::class);
        $this->container->register(Admin\AdminServiceProvider::class);
    }

    private function boot(): void {
        $this->container->boot();
    }
}
```

---

## 3. High-Performance Frontend Execution Lifecycle
To guarantee negligible frontend memory and CPU overhead:
1. Admin-only classes, analytics modules, schema template editors, media optimizers, and migration tools are **never instantiated on frontend requests**.
2. Frontend metadata generation queries the cached `wp_apex_indexables` table in a single indexed query (`object_id` + `object_type`), bypassing expensive runtime recalculation.
3. Page caching intercepts requests at the earliest possible hook (`init` / `template_redirect`), serving pre-rendered static HTML or emitting LiteSpeed server cache tags with sub-millisecond TTFB.
