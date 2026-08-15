# Apex SEO Platform — Master Architecture Blueprint

## 1. Directory Structure Map
```
wp-content/plugins/apex-seo/
├── apex-seo.php                         (Main Plugin Bootstrap)
├── uninstall.php                        (Safe Cleanup Handler)
├── readme.txt                           (WP Plugin Header & Changelog)
├── composer.json                        (PSR-4 Autoloading Map)
├── phpunit.xml                          (Test Suite Configuration)
├── phpcs.xml                            (WordPress Coding Standards Config)
│
├── src/
│   ├── Core/                            (Bootstrap, Service Container, Event Dispatcher, Environment)
│   ├── SEO/                             (Titles, Meta, Robots, Canonical, Social, Indexables, Sitemaps)
│   ├── Schema/                          (52 Built-in Types, Graph Merger, Conditions, Variables, Validator)
│   ├── Media/                           (Image Optimizer, WebP, AVIF, Image SEO, Queue, Media Library)
│   ├── Performance/                     (CSS, JavaScript Defer/Delay, Fonts, LazyLoad, Preload, Diagnostics)
│   ├── Cache/                           (Page Cache, Redis, Memcached, Purge Engine)
│   ├── Server/                          (LiteSpeed, OpenLiteSpeed, Nginx, Apache Adapters)
│   ├── CDN/                             (Cloudflare, Generic CDN URL Rewriter)
│   ├── AI/                              (AIVisibility, Crawlers, Virtual llms.txt, Gemini API Integration)
│   ├── Analytics/                       (Search Console, GA4, Matomo, Rank Tracker, Instant Indexing)
│   ├── Database/                        (Custom Table Schema, Optimizer, Migrator)
│   ├── Migration/                       (Yoast, Rank Math, AIOSEO, SEOPress, Redirection Importers)
│   ├── WooCommerce/                     (Product SEO, Schema, Shop Archive, Dynamic Cache Protections)
│   ├── API/                             (REST API Endpoints, Headless JSON Payload, Abilities API)
│   ├── CLI/                             (WP-CLI Command Implementations)
│   └── Admin/                           (Menus, MetaBoxes, AdminBar, Diagnostics, Conflict Detector)
│
├── assets/
│   ├── css/                             (Admin & Metabox Styling, RTL Styles)
│   ├── js/                              (Admin Scripting, Real-Time Content Analyzer, Chart Visualizers)
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
