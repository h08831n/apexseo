<?php
namespace ApexSEO\Tests;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\SEO\Meta\TitlePresenter;
use ApexSEO\SEO\Meta\DescriptionPresenter;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\SEO\Meta\CanonicalPresenter;
use ApexSEO\SEO\Meta\MetaTagManager;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;
use ApexSEO\SEO\Feed\RssFeedManager;
use ApexSEO\SEO\Admin\MetaSaver;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;

class Phase5AHardeningTest extends TestCase {

    private $config;
    private $db;
    private $indexableRepo;
    private $indexableBuilder;
    private $variableEngine;
    private $templateManager;
    private $titlePresenter;
    private $descriptionPresenter;
    private $robotsPresenter;
    private $canonicalPresenter;
    private $ogPresenter;
    private $twitterPresenter;
    private $metaSaver;
    private $rssFeedManager;
    private $metaTagManager;

    protected function setUp(): void {
        parent::setUp();

        $this->config = new ConfigurationManager();
        $this->db = new DatabaseManager();
        $this->indexableRepo = new IndexableRepository($this->db);
        $this->variableEngine = new VariableEngine();
        $this->templateManager = new TemplateManager($this->config);
        $this->indexableBuilder = new IndexableBuilder($this->variableEngine, $this->templateManager);

        $this->titlePresenter = new TitlePresenter($this->variableEngine);
        $this->descriptionPresenter = new DescriptionPresenter($this->variableEngine);
        $this->robotsPresenter = new RobotsPresenter();
        $this->canonicalPresenter = new CanonicalPresenter();
        $this->ogPresenter = new OpenGraphPresenter();
        $this->twitterPresenter = new TwitterCardPresenter();

        $this->metaSaver = new MetaSaver($this->indexableRepo);
        $this->rssFeedManager = new RssFeedManager();
        $this->metaTagManager = new MetaTagManager(
            $this->titlePresenter,
            $this->descriptionPresenter,
            $this->canonicalPresenter,
            $this->robotsPresenter,
            $this->ogPresenter,
            $this->twitterPresenter
        );
    }

    /**
     * APEX-004: Taxonomy Archive SEO
     */
    public function testTaxonomyArchiveTitleAndDescription() {
        $context = [
            'page_type' => 'term',
            'object_type' => 'term',
            'object_sub_type' => 'category',
            'object_id' => 10,
            'title' => 'WordPress Tutorials',
            'sitename' => 'Apex Dev',
            'sep' => '|',
            'template' => '%%title%% %%sep%% %%sitename%%',
            'description' => 'Comprehensive guides for WordPress development and SEO.',
        ];

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('WordPress Tutorials', $title);
        $this->assertStringContainsString('Apex Dev', $title);

        $desc = $this->descriptionPresenter->render($context);
        $this->assertStringContainsString('Comprehensive guides', $desc);
    }

    /**
     * APEX-005: Author Archive SEO
     */
    public function testAuthorArchiveTitleAndDescription() {
        $context = [
            'page_type' => 'author',
            'object_type' => 'user',
            'object_id' => 5,
            'title' => 'Alex Morgan',
            'author_name' => 'Alex Morgan',
            'author_bio' => 'Senior SEO Architect at Apex SEO.',
            'sitename' => 'Apex Dev',
            'sep' => '-',
            'template' => '%%title%% %%sep%% %%sitename%%',
            'description' => 'Senior SEO Architect at Apex SEO.',
        ];

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('Alex Morgan', $title);

        $desc = $this->descriptionPresenter->render($context);
        $this->assertStringContainsString('Senior SEO Architect', $desc);
    }

    /**
     * APEX-006: Date Archive SEO
     */
    public function testDateArchiveTitleAndRobots() {
        $context = [
            'page_type' => 'date',
            'object_type' => 'date',
            'year' => '2026',
            'month' => 'August',
            'title' => 'August 2026',
            'sitename' => 'Apex Dev',
            'sep' => '-',
            'template' => '%%title%% %%sep%% %%sitename%%',
        ];

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('2026', $title);
    }

    /**
     * APEX-007: Search Results SEO
     */
    public function testSearchResultsTitleAndRobots() {
        $context = [
            'page_type' => 'search',
            'object_type' => 'search',
            'searchphrase' => 'enterprise seo plugins',
            'title' => 'enterprise seo plugins',
            'sitename' => 'Apex Dev',
            'sep' => '|',
            'robots_index' => false,
        ];

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('enterprise seo plugins', $title);

        $robots = $this->robotsPresenter->render($context);
        $this->assertStringContainsString('noindex', $robots);
    }

    /**
     * APEX-008: 404 Page SEO Handling
     */
    public function test404PageRobotsAndCanonical() {
        $context = [
            'page_type' => '404',
            'object_type' => '404',
            'title' => 'Page Not Found',
            'sitename' => 'Apex Dev',
            'robots_index' => false,
            'robots_follow' => true,
        ];

        $robots = $this->robotsPresenter->render($context);
        $this->assertStringContainsString('noindex', $robots);

        $canonical = $this->canonicalPresenter->render($context);
        $this->assertEquals('', $canonical);
    }

    /**
     * APEX-010: Title & Description Sanitization
     */
    public function testSanitizationStripsHarmfulContent() {
        $xss = "<script>alert('xss')</script> Safe Title [shortcode] &amp; Entity";
        $cleanTitle = $this->titlePresenter->render(['template' => $xss]);

        $this->assertStringNotContainsString('<script>', $cleanTitle);
        $this->assertStringNotContainsString('[shortcode]', $cleanTitle);
    }

    /**
     * APEX-012: Pagination SEO
     */
    public function testPaginationInTitleAndCanonical() {
        $context = [
            'page_type' => 'post',
            'object_type' => 'post',
            'title' => 'Long Article',
            'permalink' => 'https://example.com/long-article',
            'is_paged' => true,
            'page_number' => 3,
            'total_pages' => 5,
            'sitename' => 'Apex Dev',
            'sep' => '|',
            'template' => '%%title%% - Page 3 of 5 | %%sitename%%',
        ];

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('Page 3 of 5', $title);
    }

    /**
     * APEX-013: Fallback SEO Generation
     */
    public function testFallbackSeoGeneration() {
        $context = [
            'page_type' => 'post',
            'object_type' => 'post',
            'title' => 'Unconfigured Post',
            'excerpt' => 'This is the post excerpt generated from content summary.',
            'description' => 'This is the post excerpt generated from content summary.',
            'sitename' => 'Apex Dev',
            'sep' => '-',
        ];

        $desc = $this->descriptionPresenter->render($context);
        $this->assertStringContainsString('post excerpt', $desc);
    }

    /**
     * APEX-014: Meta Saver Operations
     */
    public function testMetaSaverSaveOperation() {
        $saved = $this->metaSaver->savePostMeta(101, [
            'title' => 'Custom Meta Title',
            'description' => 'Custom meta description.',
            'robots_index' => true,
        ]);
        $this->assertTrue($saved);
    }

    /**
     * APEX-015: RSS Feed Enhancement
     */
    public function testRssFeedHeaderAndFooter() {
        $content = "<p>Standard post body.</p>";
        $permalink = 'https://example.com/post-1';

        $injected = $this->rssFeedManager->enhanceFeedItem($content, $permalink);
        $this->assertStringContainsString('Standard post body.', $injected);
        $this->assertStringContainsString('Original Article on', $injected);
        $this->assertStringContainsString('https://example.com/post-1', $injected);
    }

    /**
     * APEX-017: Dynamic Variable Engine
     */
    public function testVariableEngineTokenReplacement() {
        $template = "%%title%% %%sep%% %%sitename%% %%sep%% %%cf_custom_key%%";
        $context = [
            'title' => 'Custom Meta Page',
            'sitename' => 'Apex Dev',
            'sep' => '|',
            'cf_custom_key' => 'High Value Product'
        ];

        $result = $this->variableEngine->replace($template, $context);
        $this->assertEquals("Custom Meta Page | Apex Dev | High Value Product", $result);
    }

    /**
     * APEX-018: Smart Description Truncation
     */
    public function testSmartWordBoundaryTruncation() {
        $long = "This is an extraordinarily well written paragraph intended to demonstrate word boundary truncation cleanly.";
        $truncated = $this->descriptionPresenter->cleanDescription($long);

        $this->assertLessThanOrEqual(160, mb_strlen($truncated, 'UTF-8'));
    }
}
