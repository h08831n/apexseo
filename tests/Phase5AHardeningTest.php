<?php
namespace ApexSEO\Tests;

use ApexSEO\SEO\Context\SeoContext;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\SEO\Meta\TitlePresenter;
use ApexSEO\SEO\Meta\DescriptionPresenter;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\SEO\Meta\CanonicalPresenter;
use ApexSEO\SEO\Meta\MetaTagManager;
use ApexSEO\SEO\Feed\RssFeedManager;
use ApexSEO\SEO\Admin\MetaSaver;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Database\Repositories\IndexableRepository;
use ApexSEO\Database\Repositories\IndexableHierarchyRepository;
use ApexSEO\Database\Repositories\PrimaryTermRepository;
use ApexSEO\SEO\Indexable\IndexableBuilder;

class Phase5AHardeningTest extends TestCase {

    private $variableEngine;
    private $templateManager;
    private $titlePresenter;
    private $descriptionPresenter;
    private $robotsPresenter;
    private $canonicalPresenter;
    private $metaSaver;
    private $rssFeedManager;
    private $metaTagManager;

    protected function setUp(): void {
        parent::setUp();

        $this->variableEngine = new VariableEngine();
        $this->templateManager = new TemplateManager();
        $this->titlePresenter = new TitlePresenter($this->variableEngine, $this->templateManager);
        $this->descriptionPresenter = new DescriptionPresenter($this->variableEngine, $this->templateManager);
        $this->robotsPresenter = new RobotsPresenter();
        $this->canonicalPresenter = new CanonicalPresenter();

        $indexableRepo = new IndexableRepository();
        $hierarchyRepo = new IndexableHierarchyRepository();
        $primaryTermRepo = new PrimaryTermRepository();
        $indexableBuilder = new IndexableBuilder($indexableRepo, $hierarchyRepo, $primaryTermRepo);

        $this->metaSaver = new MetaSaver($indexableRepo, $indexableBuilder);
        $this->rssFeedManager = new RssFeedManager($this->variableEngine, $this->templateManager);
        $this->metaTagManager = new MetaTagManager(
            $this->titlePresenter,
            $this->descriptionPresenter,
            $this->robotsPresenter,
            $this->canonicalPresenter
        );
    }

    /**
     * APEX-004: Taxonomy Archive SEO
     */
    public function testTaxonomyArchiveTitleAndDescription() {
        $context = new SeoContext([
            'page_type' => 'term',
            'object_type' => 'term',
            'object_sub_type' => 'category',
            'object_id' => 10,
            'title' => 'WordPress Tutorials',
            'term' => 'WordPress Tutorials',
            'term_description' => 'Comprehensive guides for WordPress development and SEO.',
            'sitename' => 'Apex Dev',
            'sep' => '|'
        ]);

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
        $context = new SeoContext([
            'page_type' => 'author',
            'object_type' => 'user',
            'object_id' => 5,
            'author_name' => 'Alex Morgan',
            'author' => 'Alex Morgan',
            'author_bio' => 'Senior SEO Architect at Apex SEO.',
            'sitename' => 'Apex Dev',
            'sep' => '-'
        ]);

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('Alex Morgan', $title);

        $desc = $this->descriptionPresenter->render($context);
        $this->assertStringContainsString('Senior SEO Architect', $desc);
    }

    /**
     * APEX-006: Date Archive SEO
     */
    public function testDateArchiveTitleAndRobots() {
        $context = new SeoContext([
            'page_type' => 'date',
            'object_type' => 'date',
            'year' => '2026',
            'month' => 'August',
            'archive_date' => 'August 2026',
            'sitename' => 'Apex Dev',
            'sep' => '-'
        ]);

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('2026', $title);
    }

    /**
     * APEX-007: Search Results SEO
     */
    public function testSearchResultsTitleAndRobots() {
        $context = new SeoContext([
            'page_type' => 'search',
            'object_type' => 'search',
            'searchphrase' => 'enterprise seo plugins',
            'sitename' => 'Apex Dev',
            'sep' => '|'
        ]);

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('enterprise seo plugins', $title);

        $robots = $this->robotsPresenter->render($context);
        $this->assertStringContainsString('noindex', $robots);
    }

    /**
     * APEX-008: 404 Page SEO Handling
     */
    public function test404PageRobotsAndCanonical() {
        $context = new SeoContext([
            'page_type' => '404',
            'object_type' => '404',
            'title' => 'Page Not Found',
            'sitename' => 'Apex Dev'
        ]);

        $robots = $this->robotsPresenter->render($context);
        $this->assertEquals('noindex, follow', $robots);

        $canonical = $this->canonicalPresenter->render($context);
        $this->assertEquals('', $canonical);
    }

    /**
     * APEX-010: Title & Description Sanitization
     */
    public function testSanitizationStripsHarmfulContent() {
        $xss = "<script>alert('xss')</script> Safe Title [shortcode] &amp; Entity";
        $cleanTitle = $this->titlePresenter->cleanTitle($xss);

        $this->assertStringNotContainsString('<script>', $cleanTitle);
        $this->assertStringNotContainsString('[shortcode]', $cleanTitle);
        $this->assertEquals("alert('xss') Safe Title & Entity", $cleanTitle);
    }

    /**
     * APEX-012: Pagination SEO
     */
    public function testPaginationInTitleAndCanonical() {
        $context = new SeoContext([
            'page_type' => 'post',
            'object_type' => 'post',
            'title' => 'Long Article',
            'permalink' => 'https://example.com/long-article',
            'is_paged' => true,
            'page_number' => 3,
            'total_pages' => 5,
            'sitename' => 'Apex Dev',
            'sep' => '|'
        ]);

        $title = $this->titlePresenter->render($context);
        $this->assertStringContainsString('Page 3 of 5', $title);
    }

    /**
     * APEX-013: Fallback SEO Generation
     */
    public function testFallbackSeoGeneration() {
        $context = new SeoContext([
            'page_type' => 'post',
            'object_type' => 'post',
            'title' => 'Unconfigured Post',
            'excerpt' => 'This is the post excerpt generated from content summary.',
            'sitename' => 'Apex Dev',
            'sep' => '-'
        ]);

        $desc = $this->descriptionPresenter->render($context);
        $this->assertStringContainsString('post excerpt', $desc);
    }

    /**
     * APEX-014: Bulk Meta Operations Security & Batch Limits
     */
    public function testBulkMetaOperationsBatchLimit() {
        $oversizedBatch = [];
        for ($i = 1; $i <= 105; $i++) {
            $oversizedBatch[] = [
                'object_id' => $i,
                'object_type' => 'post',
                'meta' => ['title' => "Post $i"]
            ];
        }

        $res = $this->metaSaver->bulkSave($oversizedBatch, 'valid_nonce');
        $this->assertFalse($res['success']);
        $this->assertEquals('batch_limit_exceeded', $res['error']);
    }

    /**
     * APEX-014: Bulk Meta Operations Fail-Closed Nonce
     */
    public function testBulkMetaOperationsFailsClosedOnMissingNonce() {
        $items = [
            ['object_id' => 1, 'object_type' => 'post', 'meta' => ['title' => 'Test']]
        ];

        $res = $this->metaSaver->bulkSave($items, '');
        $this->assertFalse($res['success']);
        $this->assertEquals('invalid_nonce', $res['error']);
    }

    /**
     * APEX-015: RSS Feed Enhancement
     */
    public function testRssFeedHeaderAndFooter() {
        $content = "<p>Standard post body.</p>";
        $context = [
            'sitename' => 'Apex SEO Blog',
            'post_link' => '<a href="https://example.com/post-1">Original Post</a>'
        ];

        $injected = $this->rssFeedManager->formatFeedContent($content, $context);
        $this->assertStringContainsString('Apex SEO Blog', $injected);
        $this->assertStringContainsString('Original Post', $injected);
        $this->assertStringContainsString('<!-- apexseo-rss-injected -->', $injected);

        // Test duplicate prevention
        $injectedAgain = $this->rssFeedManager->formatFeedContent($injected, $context);
        $this->assertEquals($injected, $injectedAgain);
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
        $truncated = $this->descriptionPresenter->truncateToWordBoundary($long, 45);

        $this->assertLessThanOrEqual(45, mb_strlen($truncated, 'UTF-8'));
        $this->assertStringEndsWith('...', $truncated);
        $this->assertStringNotContainsString(' ...', $truncated);
    }
}
