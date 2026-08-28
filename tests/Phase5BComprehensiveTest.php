<?php
namespace ApexSEO\Tests;

use PHPUnit\Framework\TestCase;
use ApexSEO\SEO\Permalinks\CategoryBaseStripper;
use ApexSEO\SEO\Meta\MetaKeywordsPresenter;
use ApexSEO\SEO\Meta\CanonicalPresenter;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\SEO\Robots\RobotsTxtManager;
use ApexSEO\SEO\Robots\RobotsHeaderManager;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;
use ApexSEO\SEO\Social\SocialPreviewService;
use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Container\Container;
use ApexSEO\SEO\SeoModule;

/**
 * Phase 5B Comprehensive Production Verification Tests.
 */
class Phase5BComprehensiveTest extends TestCase {

    /**
     * Test APEX-011: Category Base Permalinks Stripper.
     */
    public function testCategoryBaseStripper() {
        $config = new ConfigurationManager();
        $config->set('strip_category_base', true);
        $stripper = new CategoryBaseStripper($config);

        $this->assertTrue($stripper->isEnabled());

        // Test link filtering
        $original = 'https://example.com/category/tech/ai/';
        $stripped = $stripper->filterCategoryLink($original);
        $this->assertEquals('https://example.com/tech/ai/', $stripped);

        // Test rewrite rules modification
        $mockCategories = [
            (object) ['slug' => 'tech', 'parent' => 0, 'term_id' => 1],
            (object) ['slug' => 'ai', 'parent' => 1, 'term_id' => 2],
        ];
        $rules = $stripper->modifyCategoryRewriteRules([]);
        $this->assertIsArray($rules);

        // Disabled state
        $config->set('strip_category_base', false);
        $this->assertFalse($stripper->isEnabled());
        $this->assertEquals($original, $stripper->filterCategoryLink($original));
    }

    /**
     * Test APEX-016: Meta Keywords Presenter (Toggleable, legacy feature).
     */
    public function testMetaKeywordsPresenter() {
        $config = new ConfigurationManager();
        $config->set('enable_meta_keywords', false);
        $presenter = new MetaKeywordsPresenter($config);

        // Disabled by default -> returns empty string
        $this->assertFalse($presenter->isEnabled());
        $this->assertEmpty($presenter->render('seo, wordpress, apex'));
        $this->assertEmpty($presenter->renderHtmlTag('seo, wordpress, apex'));

        // Enable keywords
        $config->set('enable_meta_keywords', true);
        $this->assertTrue($presenter->isEnabled());

        $raw = '<script>alert(1)</script>SEO, WordPress, SEO,  Ranking , Fast Indexing';
        $rendered = $presenter->render($raw);
        $this->assertEquals('SEO, WordPress, Ranking, Fast Indexing', $rendered);

        $tag = $presenter->renderHtmlTag($raw);
        $this->assertStringContainsString('<meta name="keywords" content="SEO, WordPress, Ranking, Fast Indexing" />', $tag);
    }

    /**
     * Test APEX-020 & APEX-021: Custom & Cross-Domain Canonical URL Normalization & Validation.
     */
    public function testCanonicalPresenterCustomAndCrossDomain() {
        $presenter = new CanonicalPresenter();

        // Standard post with tracking query params
        $urlWithParams = 'https://example.com/blog/seo-guide/?utm_source=twitter&utm_medium=social&fbclid=12345&tab=overview';
        $cleaned = $presenter->cleanUrl($urlWithParams);
        $this->assertEquals('https://example.com/blog/seo-guide/?tab=overview', $cleaned);

        // Strip fragments
        $urlWithFragment = 'https://example.com/about/#team';
        $this->assertEquals('https://example.com/about/', $presenter->cleanUrl($urlWithFragment));

        // Cross-domain canonical override (APEX-021)
        $crossDomain = 'https://medium.com/@apexseo/original-article/';
        $this->assertEquals('https://medium.com/@apexseo/original-article/', $presenter->cleanUrl($crossDomain));

        // Security: reject dangerous schemes (XSS prevention)
        $this->assertEmpty($presenter->cleanUrl('javascript:alert(document.cookie)'));
        $this->assertEmpty($presenter->cleanUrl('data:text/html,<script>alert(1)</script>'));
        $this->assertEmpty($presenter->cleanUrl('vbscript:msgbox("hello")'));
        $this->assertEmpty($presenter->cleanUrl('file:///etc/passwd'));

        // 404 Suppression
        $context404 = new SeoContext();
        $context404->page_type = '404';
        $this->assertEmpty($presenter->render($context404));
        $this->assertEmpty($presenter->renderHtmlTag($context404));
    }

    /**
     * Test APEX-024: Paginated Robots Directives.
     */
    public function testRobotsPresenterPagination() {
        $presenter = new RobotsPresenter();

        $context = new SeoContext();
        $context->page_type = 'archive';
        $context->is_paged = true;
        $context->page_number = 3;
        $context->robots_noindex = false;

        $directives = $presenter->getDirectives($context);
        $this->assertFalse($directives['noindex']);
        $this->assertFalse($directives['nofollow']);
    }

    /**
     * Test APEX-025 & APEX-026: Virtual Robots.txt Generator & AI Crawler Directives.
     */
    public function testRobotsTxtManagerAndAiDirectives() {
        $config = new ConfigurationManager();
        $config->set('block_all_ai_crawlers', true);
        $manager = new RobotsTxtManager($config);

        $txt = $manager->generate();

        // Verify standard directives
        $this->assertStringContainsString('User-agent: *', $txt);
        $this->assertStringContainsString('Disallow: /wp-admin/', $txt);

        // Verify AI Crawler directives (APEX-026)
        $this->assertStringContainsString('User-agent: GPTBot', $txt);
        $this->assertStringContainsString('User-agent: CCBot', $txt);
        $this->assertStringContainsString('User-agent: Google-Extended', $txt);
        $this->assertStringContainsString('User-agent: ClaudeBot', $txt);
        $this->assertStringContainsString('Disallow: /', $txt);

        // Verify Sitemap link
        $this->assertStringContainsString('Sitemap:', $txt);

        // Test custom rules sanitization (APEX-025)
        $dirtyRules = "Disallow: /private/\n<script>alert(1)</script>\nAllow: /public/\n# Safe comment";
        $cleanRules = $manager->sanitizeCustomRules($dirtyRules);
        $this->assertStringContainsString('Disallow: /private/', $cleanRules);
        $this->assertStringContainsString('Allow: /public/', $cleanRules);
        $this->assertStringContainsString('# Safe comment', $cleanRules);
        $this->assertStringNotContainsString('<script>', $cleanRules);
    }

    /**
     * Test APEX-027 through APEX-030: HTTP X-Robots-Tag Header Emission.
     */
    public function testRobotsHeaderManager() {
        $config = new ConfigurationManager();
        $headerMgr = new RobotsHeaderManager(null, null, $config);

        // 404 Context
        $ctx404 = new SeoContext();
        $ctx404->page_type = '404';
        $val404 = $headerMgr->determineHeaderValue($ctx404);
        $this->assertEquals('noindex, nofollow', $val404);

        // Search Context
        $ctxSearch = new SeoContext();
        $ctxSearch->page_type = 'search';
        $valSearch = $headerMgr->determineHeaderValue($ctxSearch);
        $this->assertEquals('noindex, follow', $valSearch);

        // Explicit noindex
        $ctxNoindex = new SeoContext();
        $ctxNoindex->page_type = 'single';
        $ctxNoindex->robots_noindex = true;
        $ctxNoindex->robots_nofollow = true;
        $this->assertEquals('noindex, nofollow', $headerMgr->determineHeaderValue($ctxNoindex));

        // Filter headers map
        $headers = $headerMgr->filterHttpHeaders(['Content-Type' => 'text/html']);
        $this->assertArrayHasKey('Content-Type', $headers);
    }

    /**
     * Test APEX-032 through APEX-039: Social Meta, Image Cascade, Dimensions, Handles, and Previews.
     */
    public function testSocialMetaAndPreviewCascade() {
        $config = new ConfigurationManager();
        $config->set('og_default_image', 'https://example.com/default-share.png');
        $config->set('fb_app_id', '1234567890');
        $config->set('fb_admins', '1001, 1002');
        $config->set('fb_publisher', 'https://facebook.com/ApexSEOOfficial');
        $config->set('twitter_site', '@ApexSEO');
        $config->set('pinterest_verify', 'abc123pinverifytoken');

        $ogPresenter = new OpenGraphPresenter(null, $config);
        $twPresenter = new TwitterCardPresenter(null, $config);
        $previewService = new SocialPreviewService(null, $ogPresenter, $twPresenter);

        // Context with no explicit image -> should cascade to site default image
        $ctx = new SeoContext();
        $ctx->page_type = 'single';
        $ctx->object_sub_type = 'post';
        $ctx->title = 'Modern SEO Guide';
        $ctx->excerpt = 'Complete guide to on-page optimization.';
        $ctx->permalink = 'https://example.com/guide/';
        $ctx->author_name = 'Sarah Connor';
        $ctx->date_published = '2026-08-24T12:00:00+00:00';

        $ogHtml = $ogPresenter->render($ctx);
        $this->assertStringContainsString('property="og:title" content="Modern SEO Guide"', $ogHtml);
        $this->assertStringContainsString('property="og:image" content="https://example.com/default-share.png"', $ogHtml);
        $this->assertStringContainsString('property="og:image:width" content="1200"', $ogHtml);
        $this->assertStringContainsString('property="og:image:height" content="630"', $ogHtml);
        $this->assertStringContainsString('property="fb:app_id" content="1234567890"', $ogHtml);
        $this->assertStringContainsString('property="fb:admins" content="1001, 1002"', $ogHtml);
        $this->assertStringContainsString('property="article:publisher" content="https://facebook.com/ApexSEOOfficial"', $ogHtml);
        $this->assertStringContainsString('name="p:domain_verify" content="abc123pinverifytoken"', $ogHtml);

        $twHtml = $twPresenter->render($ctx);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $twHtml);
        $this->assertStringContainsString('name="twitter:site" content="@ApexSEO"', $twHtml);

        // Live Social Preview Service (APEX-038)
        $preview = $previewService->generatePreview([
            'title'       => 'Live Article Preview',
            'description' => 'A live description snippet.',
            'permalink'   => 'https://example.com/live/',
        ]);

        $this->assertIsArray($preview);
        $this->assertArrayHasKey('facebook', $preview);
        $this->assertArrayHasKey('twitter', $preview);
        $this->assertArrayHasKey('google', $preview);
        $this->assertEquals('Live Article Preview', $preview['facebook']['title']);
        $this->assertEquals('@ApexSEO', $preview['twitter']['site_handle']);
        $this->assertGreaterThan(0, $preview['google']['pixel_width']);
    }

    /**
     * Test DI Container wiring for all Phase 5B services.
     */
    public function testDiContainerWiring() {
        $container = new Container();
        $seoModule = new SeoModule();

        // Register configuration manager
        $config = new ConfigurationManager();
        $container->singleton(ConfigurationManager::class, function() use ($config) {
            return $config;
        });

        $seoModule->register($container);

        $this->assertTrue($container->has(CategoryBaseStripper::class));
        $this->assertTrue($container->has(MetaKeywordsPresenter::class));
        $this->assertTrue($container->has(RobotsTxtManager::class));
        $this->assertTrue($container->has(RobotsHeaderManager::class));
        $this->assertTrue($container->has(SocialPreviewService::class));
        $this->assertTrue($container->has(OpenGraphPresenter::class));
        $this->assertTrue($container->has(TwitterCardPresenter::class));
        $this->assertTrue($container->has(CanonicalPresenter::class));

        // Resolve instances cleanly
        $this->assertInstanceOf(CategoryBaseStripper::class, $container->get(CategoryBaseStripper::class));
        $this->assertInstanceOf(MetaKeywordsPresenter::class, $container->get(MetaKeywordsPresenter::class));
        $this->assertInstanceOf(RobotsTxtManager::class, $container->get(RobotsTxtManager::class));
        $this->assertInstanceOf(RobotsHeaderManager::class, $container->get(RobotsHeaderManager::class));
        $this->assertInstanceOf(SocialPreviewService::class, $container->get(SocialPreviewService::class));
    }
}
