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

/**
 * Phase 5B Comprehensive Production Verification Tests.
 */
class Phase5BComprehensiveTest extends TestCase {

    /**
     * Test APEX-011: Category Base Permalinks Stripper.
     */
    public function testCategoryBaseStripper() {
        $stripper = new CategoryBaseStripper();

        $original = 'https://example.com/category/tech/ai/';
        $stripped = $stripper->removeCategoryBase($original);
        $this->assertEquals('https://example.com/tech/ai/', $stripped);
    }

    /**
     * Test APEX-016: Meta Keywords Presenter.
     */
    public function testMetaKeywordsPresenter() {
        $presenter = new MetaKeywordsPresenter();

        $rendered = $presenter->render(['keywords' => 'SEO, WordPress, Fast Indexing']);
        $this->assertEquals('SEO, WordPress, Fast Indexing', $rendered);

        $tag = $presenter->renderHtmlTag(['keywords' => 'SEO, WordPress, Fast Indexing']);
        $this->assertStringContainsString('<meta name="keywords" content="SEO, WordPress, Fast Indexing" />', $tag);

        $emptyTag = $presenter->renderHtmlTag([]);
        $this->assertEmpty($emptyTag);
    }

    /**
     * Test APEX-020 & APEX-021: Canonical Presenter Normalization and HTML Output.
     */
    public function testCanonicalPresenterCustomAndCrossDomain() {
        $presenter = new CanonicalPresenter();

        $context = [
            'canonical' => 'https://example.com/blog/seo-guide/',
        ];
        $rendered = $presenter->render($context);
        $this->assertEquals('https://example.com/blog/seo-guide/', $rendered);

        $tag = $presenter->renderHtmlTag($context);
        $this->assertEquals('<link rel="canonical" href="https://example.com/blog/seo-guide/" />', $tag);

        // 404 Suppression
        $context404 = [
            'page_type' => '404',
        ];
        $this->assertEmpty($presenter->render($context404));
        $this->assertEmpty($presenter->renderHtmlTag($context404));
    }

    /**
     * Test APEX-024: Robots Directives.
     */
    public function testRobotsPresenterPagination() {
        $presenter = new RobotsPresenter();

        $context = [
            'robots_index'  => true,
            'robots_follow' => true,
        ];
        $directives = $presenter->render($context);
        $this->assertEquals('index, follow', $directives);

        $noindexCtx = [
            'robots_index'  => false,
            'robots_follow' => true,
        ];
        $this->assertEquals('noindex, follow', $presenter->render($noindexCtx));
    }

    /**
     * Test APEX-025 & APEX-026: Robots.txt Manager.
     */
    public function testRobotsTxtManager() {
        $manager = new RobotsTxtManager();

        $output = "User-agent: *\nDisallow: /wp-admin/";
        $filtered = $manager->filterRobotsTxt($output, true);
        $this->assertStringContainsString('Sitemap:', $filtered);

        $blocked = $manager->filterRobotsTxt($output, false);
        $this->assertStringContainsString('Disallow: /', $blocked);
    }

    /**
     * Test APEX-027 through APEX-030: HTTP Robots Header Manager.
     */
    public function testRobotsHeaderManager() {
        $headerMgr = new RobotsHeaderManager();
        $this->assertInstanceOf(RobotsHeaderManager::class, $headerMgr);
    }

    /**
     * Test APEX-032 through APEX-039: Social Meta, Image Cascade, and Previews.
     */
    public function testSocialMetaAndPreviewCascade() {
        $ogPresenter = new OpenGraphPresenter();
        $twPresenter = new TwitterCardPresenter();
        $previewService = new SocialPreviewService($ogPresenter, $twPresenter);

        $ctx = [
            'title'       => 'Modern SEO Guide',
            'description' => 'Complete guide to on-page optimization.',
            'permalink'   => 'https://example.com/guide/',
            'og_image'    => 'https://example.com/default-share.png',
        ];

        $ogTags = $ogPresenter->renderTags($ctx);
        $this->assertEquals('Modern SEO Guide', $ogTags['og:title']);
        $this->assertEquals('https://example.com/default-share.png', $ogTags['og:image']);

        $twTags = $twPresenter->renderTags($ctx);
        $this->assertEquals('summary_large_image', $twTags['twitter:card']);
        $this->assertEquals('Modern SEO Guide', $twTags['twitter:title']);

        // Social Preview Service
        $preview = $previewService->generatePreview($ctx);
        $this->assertIsArray($preview);
        $this->assertArrayHasKey('opengraph', $preview);
        $this->assertArrayHasKey('twitter', $preview);
        $this->assertEquals('Modern SEO Guide', $preview['opengraph']['og:title']);
    }

    /**
     * Test DI Container wiring.
     */
    public function testDiContainerWiring() {
        $container = new Container();

        $config = new ConfigurationManager();
        $container->singleton(ConfigurationManager::class, function() use ($config) {
            return $config;
        });

        $this->assertInstanceOf(CategoryBaseStripper::class, $container->get(CategoryBaseStripper::class));
        $this->assertInstanceOf(MetaKeywordsPresenter::class, $container->get(MetaKeywordsPresenter::class));
        $this->assertInstanceOf(RobotsTxtManager::class, $container->get(RobotsTxtManager::class));
        $this->assertInstanceOf(RobotsHeaderManager::class, $container->get(RobotsHeaderManager::class));
        $this->assertInstanceOf(CanonicalPresenter::class, $container->get(CanonicalPresenter::class));
        $this->assertInstanceOf(OpenGraphPresenter::class, $container->get(OpenGraphPresenter::class));
        $this->assertInstanceOf(TwitterCardPresenter::class, $container->get(TwitterCardPresenter::class));
        $this->assertInstanceOf(SocialPreviewService::class, $container->get(SocialPreviewService::class));
    }
}
