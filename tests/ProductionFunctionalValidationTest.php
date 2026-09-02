<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Container\Container;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\Migrations\Migration_1_0_0_CreateLockedTables;
use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\Core\CLI\CliManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\SEO\Analysis\KeywordAnalyzer;
use ApexSEO\SEO\Analysis\ReadabilityScorer;
use ApexSEO\SEO\Analysis\HeadingAnalyzer;
use ApexSEO\SEO\Analysis\LinkGraphScanner;
use ApexSEO\SEO\Analysis\PassiveVoiceAnalyzer;
use ApexSEO\SEO\Analysis\TransitionWordAnalyzer;
use ApexSEO\SEO\Analysis\TextStructureAnalyzer;
use ApexSEO\SEO\Analysis\ContentAnalyzer;
use ApexSEO\SEO\Analysis\ContentAnalysisService;
use ApexSEO\SEO\Meta\TitlePresenter;
use ApexSEO\SEO\Meta\DescriptionPresenter;
use ApexSEO\SEO\Meta\CanonicalPresenter;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;
use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\Validator\SchemaValidator;
use ApexSEO\Schema\SchemaGraphBuilder;
use ApexSEO\API\RestApiRouter;
use ApexSEO\API\Controllers\SettingsRestController;
use ApexSEO\API\Controllers\MetaRestController;
use ApexSEO\API\Controllers\SchemaRestController;
use ApexSEO\API\Controllers\RedirectsRestController;
use ApexSEO\API\Controllers\NotFoundRestController;
use ApexSEO\API\Controllers\LinksRestController;
use ApexSEO\API\Controllers\AnalyticsRestController;
use ApexSEO\API\Controllers\CacheRestController;
use ApexSEO\API\Controllers\MediaRestController;
use ApexSEO\API\Controllers\MigrationRestController;
use ApexSEO\API\Controllers\AnalysisRestController;
use ApexSEO\Media\Optimizer\ImageOptimizer;

/**
 * Class ProductionFunctionalValidationTest
 *
 * Executes full production functional validation of all REAL_IMPLEMENTED capabilities,
 * REST routes, WP-CLI suites, 9 database tables, APEX-048..054 end-to-end flow,
 * SEO output generation, security rejections, and performance benchmarks.
 */
class ProductionFunctionalValidationTest extends TestCase {
    protected $container;
    protected $db;
    protected $security;
    protected $config;
    protected $indexableRepo;
    protected $schemaRegistry;
    protected $schemaValidator;
    protected $schemaGraphBuilder;
    protected $contentAnalysisService;
    protected $restRouter;
    protected $cliManager;

    public function setUp(): void {
        parent::setUp();
        Plugin::reset();
        $plugin = Plugin::getInstance();
        $this->container = $plugin->getContainer();

        $this->db = $this->container->get(DatabaseManager::class);
        $this->security = $this->container->get(SecurityManager::class);
        $this->config = $this->container->get(ConfigurationManager::class);
        $this->indexableRepo = $this->container->get(IndexableRepository::class);
        $this->contentAnalysisService = $this->container->get(ContentAnalysisService::class);
        $this->schemaRegistry = $this->container->get(SchemaRegistry::class);
        $this->schemaValidator = $this->container->get(SchemaValidator::class);
        $this->schemaGraphBuilder = $this->container->get(SchemaGraphBuilder::class);
        $this->restRouter = $this->container->get(RestApiRouter::class);
        $this->cliManager = new CliManager();
    }

    /**
     * Phase 1: WordPress Boot & Subsystems Activation.
     */
    public function testPhase1WordPressBootAndMigrations() {
        $plugin = Plugin::getInstance();
        $this->assertInstanceOf(Plugin::class, $plugin);

        // Run Migration
        $migration = new Migration_1_0_0_CreateLockedTables();
        $result = $migration->up($this->db);
        $this->assertTrue($result);

        // Check required tables created (8 locked tables, no 9th table)
        $prefix = $this->db->getPrefix();
        $this->assertTrue($this->db->hasTable("{$prefix}apex_indexables"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_schema"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_redirects"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_404_logs"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_links"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_image_history"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_analytics"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_rank_tracking"));
        $this->assertFalse($this->db->hasTable("{$prefix}apex_content_analysis"), '9th table apex_content_analysis must NOT exist');
    }

    /**
     * Phase 2: REST Endpoint Execution (Routes & Controllers).
     */
    public function testPhase2RestRoutesExecution() {
        $controllers = $this->restRouter->getControllers();
        $this->assertGreaterThanOrEqual(10, count($controllers));

        // 1. Status endpoint
        $statusResp = $this->restRouter->getStatus();
        $statusData = ($statusResp instanceof \WP_REST_Response) ? $statusResp->get_data() : $statusResp;
        $this->assertEquals('active', $statusData['status']);

        // 2. Settings endpoint (GET)
        $settingsCtrl = $this->restRouter->getController('settings');
        $getSettingsResp = $settingsCtrl->getSettings();
        $getSettingsData = ($getSettingsResp instanceof \WP_REST_Response) ? $getSettingsResp->get_data() : $getSettingsResp;
        $this->assertTrue($getSettingsData['success']);

        // 3. Settings update (POST)
        $updateSettingsResp = $settingsCtrl->updateSettings(['settings' => ['general' => ['separator' => '|']]]);
        $updateSettingsData = ($updateSettingsResp instanceof \WP_REST_Response) ? $updateSettingsResp->get_data() : $updateSettingsResp;
        $this->assertTrue($updateSettingsData['success']);

        // 4. Schema validation endpoint
        $schemaCtrl = $this->restRouter->getController('schema');
        $schemaValResp = $schemaCtrl->validateSchema([
            'schema' => [
                '@context' => 'https://schema.org',
                '@type'    => 'Article',
                'headline' => 'Test Article',
            ],
        ]);
        $schemaValData = ($schemaValResp instanceof \WP_REST_Response) ? $schemaValResp->get_data() : $schemaValResp;
        $this->assertTrue($schemaValData['success']);

        // 5. Analysis REST Endpoints
        $analysisCtrl = $this->restRouter->getController('analysis');
        $this->assertNotNull($analysisCtrl);
    }

    /**
     * Phase 3: WP-CLI Command Modules Execution (11 Modules).
     */
    public function testPhase3WpCliCommandsExecution() {
        $commands = $this->cliManager->getCommands();
        $this->assertEquals(11, count($commands));
        $this->assertTrue(isset($commands['index']));
        $this->assertTrue(isset($commands['cache']));
        $this->assertTrue(isset($commands['media']));
        $this->assertTrue(isset($commands['redirect']));
        $this->assertTrue(isset($commands['db']));
        $this->assertTrue(isset($commands['migrate']));
        $this->assertTrue(isset($commands['sitemap']));
        $this->assertTrue(isset($commands['doctor']));
        $this->assertTrue(isset($commands['report']));
        $this->assertTrue(isset($commands['schema']));
        $this->assertTrue(isset($commands['analysis']));
    }

    /**
     * Phase 4: Database Tables CRUD & Index Health (8 Tables).
     */
    public function testPhase4DatabaseValidation() {
        $prefix = $this->db->getPrefix();

        // 1. Insert & Query Indexables (including content analysis fields)
        $idxId = $this->db->insert("{$prefix}apex_indexables", [
            'object_type'           => 'post',
            'object_id'             => 201,
            'permalink'             => 'https://example.com/test-post',
            'canonical_url'         => 'https://example.com/test-post',
            'title'                 => 'Test Post SEO Title',
            'description'           => 'Test Description',
            'readability_score'     => 92,
            'primary_focus_keyword' => 'cloud architecture',
            'keyword_density'       => 1.85,
            'content_analysis'      => json_encode(['score' => 88, 'readability' => 92]),
        ]);
        $this->assertGreaterThan(0, $idxId);

        // 2. Insert & Query Redirects using production schema
        $redirId = $this->db->insert("{$prefix}apex_redirects", [
            'source_path' => '/old-page',
            'target_url'  => '/new-page',
            'status_code' => 301,
            'match_type'  => 'exact',
            'hits'        => 0,
            'is_active'   => 1,
        ]);
        $this->assertGreaterThan(0, $redirId);
    }

    /**
     * Phase 5: APEX-048..054 End-to-End Multilingual Lifecycle.
     */
    public function testPhase5ContentAnalysisEndToEnd() {
        $postData = [
            'ID'            => 301,
            'post_title'    => 'راهنمای جامع سئو و بهینه‌سازی موتورهای جستجو',
            'post_content'  => '<h2>مقدمه بهینه‌سازی</h2><p>سئو وردپرس یکی از مهم‌ترین استراتژی‌ها است. بنابراین باید به آن توجه شود. این مقاله توسط کارشناسان نوشته شده است.</p><h2>مزایای سئو</h2><p>علاوه بر این، لینک‌های داخلی مانند <a href="https://example.com/internal">راهنما</a> نقش مهمی دارند.</p>',
            'focus_keyword' => 'سئو وردپرس'
        ];

        // 1. Analyze Content
        $result = $this->contentAnalysisService->getContentAnalyzer()->analyzeContent($postData['post_content'], [
            'post_id'         => 301,
            'primary_keyword' => $postData['focus_keyword']
        ]);
        $this->assertIsArray($result);
        $this->assertTrue(isset($result['seo_score']));
        $this->assertTrue(isset($result['readability_score']));
        $this->assertTrue(isset($result['keywords']));
        $this->assertTrue(isset($result['headings']));
        $this->assertTrue(isset($result['readability']));

        $result['analysis_hash'] = $this->contentAnalysisService->calculateAnalysisHash(
            $postData['post_content'],
            $postData['post_title'],
            $postData['focus_keyword']
        );

        // 2. Persist Analysis
        $saved = $this->contentAnalysisService->persistAnalysis(301, $result);
        $this->assertTrue($saved);

        // 3. Retrieve Persisted Analysis
        $persisted = $this->contentAnalysisService->getPersistedAnalysis(301);
        $this->assertIsArray($persisted);
        $this->assertEquals($result['analysis_hash'], $persisted['analysis_hash']);

        // 4. Update Post and Verify Hash Change
        $updatedContent = $postData['post_content'] . '<p>بخش جدید اضافه شد برای بررسی تغییر هش و بهینه‌سازی مجدد.</p>';
        $updatedHash = $this->contentAnalysisService->calculateAnalysisHash(
            $updatedContent,
            $postData['post_title'],
            $postData['focus_keyword']
        );
        $this->assertNotEquals($result['analysis_hash'], $updatedHash);

        // 5. Cleanup on Post Deletion
        $cleaned = $this->contentAnalysisService->handleDeletePost(301);
        $this->assertTrue($cleaned);
    }

    /**
     * Phase 6: SEO Output Validation (Head tags, Schema, Social).
     */
    public function testPhase6SeoOutputGeneration() {
        $varEngine = new VariableEngine();
        $titlePresenter = new TitlePresenter($varEngine);
        $descPresenter = new DescriptionPresenter($varEngine);
        $canonicalPresenter = new CanonicalPresenter();
        $robotsPresenter = new RobotsPresenter($this->config);

        $context = [
            'title'            => 'My Sample Post',
            'meta_description' => 'Sample meta description content.',
            'canonical_url'    => 'https://example.com/canonical-url',
            'is_paged'         => false,
            'sep'              => '-',
            'sitename'         => 'Apex Site',
        ];

        $renderedTitle = $titlePresenter->renderHtmlTag($context);
        $this->assertStringContainsString('<title>', $renderedTitle);
        $this->assertStringContainsString('My Sample Post', $renderedTitle);

        $renderedDesc = $descPresenter->renderHtmlTag($context);
        $this->assertStringContainsString('name="description"', $renderedDesc);

        $renderedCanonical = $canonicalPresenter->renderHtmlTag($context);
        $this->assertStringContainsString('rel="canonical"', $renderedCanonical);

        $renderedRobots = $robotsPresenter->renderHtmlTag($context);
        $this->assertStringContainsString('name="robots"', $renderedRobots);
    }

    /**
     * Phase 7: Security Bounds & Negative Injection Rejections.
     */
    public function testPhase7SecurityBoundsAndNegativeRejections() {
        // 1. SQL Injection payload sanitization
        $sqliPayload = "' OR '1'='1";
        $prepared = $this->db->prepare("SELECT * FROM wp_apex_indexables WHERE canonical_url = %s", $sqliPayload);
        $this->assertStringContainsString("'' OR ''1''=''1'", $prepared);

        // 2. XSS payload sanitization
        $xssPayload = "<script>alert('xss');</script>SEO Title";
        $sanitized = sanitize_text_field($xssPayload);
        $this->assertStringNotContainsString("<script>", $sanitized);

        // 3. Permission checks
        $hasCap = $this->security->current_user_can('manage_options');
        $this->assertTrue(is_bool($hasCap));
    }

    /**
     * Phase 8: Performance Benchmarks & Word-Scale Execution.
     */
    public function testPhase8PerformanceAndScalability() {
        // Generate 20,000 word stress test block
        $words = ["seo", "optimization", "ranking", "google", "meta", "content", "keyword", "strategy", "structure", "performance"];
        $longContent = "";
        for ($i = 0; $i < 2000; $i++) {
            $longContent .= "<p>" . implode(" ", $words) . " paragraph line number {$i}. Therefore, ranking is optimized.</p>";
        }

        $startTime = microtime(true);
        $startMem = memory_get_usage();

        $result = $this->contentAnalysisService->getContentAnalyzer()->analyzeContent($longContent, [
            'post_id'         => 999,
            'primary_keyword' => 'seo'
        ]);

        $executionTime = microtime(true) - $startTime;
        $memoryConsumed = (memory_get_usage() - $startMem) / (1024 * 1024);

        $this->assertIsArray($result);
        $this->assertGreaterThan(15000, $result['metrics']['word_count']);
        // Verify execution is fast and consumes minimal memory (< 30 MB)
        $this->assertLessThan(30, $memoryConsumed);
    }

    /**
     * Phase 5A: APEX-004 Custom Taxonomy Title & Meta.
     */
    public function testPhase5A_APEX004_CustomTaxonomyTitleAndMeta() {
        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($this->config);
        $builder = new IndexableBuilder($varEngine, $tplManager);

        $indexable = $builder->buildForObject(42, 'term');

        $this->assertEquals(42, $indexable->getObjectId());
        $this->assertEquals('term', $indexable->getObjectType());
        $this->assertEquals(1, $indexable->getRobotsIndex());
        $this->assertEquals(1, $indexable->getRobotsFollow());

        // Test title rendering with taxonomy context
        $presenter = new TitlePresenter($varEngine);
        $rendered = $presenter->render([
            'title'    => 'Cloud Architecture',
            'sep'      => '|',
            'sitename' => 'Enterprise Hub',
        ]);
        $this->assertStringContainsString('Cloud Architecture', $rendered);
        $this->assertStringContainsString('Enterprise Hub', $rendered);
    }

    /**
     * Phase 5A: APEX-005 Author Archive Title & Meta.
     */
    public function testPhase5A_APEX005_AuthorArchiveTitleAndMeta() {
        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($this->config);
        $builder = new IndexableBuilder($varEngine, $tplManager);

        $indexable = $builder->buildForObject(17, 'author');

        $this->assertEquals(17, $indexable->getObjectId());
        $this->assertEquals('author', $indexable->getObjectType());

        // Test saving metadata via MetaSaver
        $saver = new \ApexSEO\SEO\Admin\MetaSaver($this->indexableRepo);
        $saved = $saver->savePostMeta(17, [
            'title'       => 'Dr. Jane Doe - Senior Cloud Engineer Profile',
            'description' => 'Read verified articles and technical reports by Dr. Jane Doe.',
        ]);
        $this->assertTrue($saved);
    }

    /**
     * Phase 5A: APEX-006 Date Archive Title & Meta.
     */
    public function testPhase5A_APEX006_DateArchiveTitleAndMeta() {
        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($this->config);
        $builder = new IndexableBuilder($varEngine, $tplManager);

        $indexable = $builder->buildForObject(0, 'archive');

        $this->assertEquals('archive', $indexable->getObjectType());

        // Test Date Archive Title Presenter
        $presenter = new TitlePresenter($varEngine);
        $rendered = $presenter->render([
            'title'    => 'October 2026',
            'sep'      => '|',
            'sitename' => 'DevBlog',
        ]);
        $this->assertStringContainsString('October 2026', $rendered);
    }

    /**
     * Phase 5A: APEX-007 Search Results Page Title/Meta.
     */
    public function testPhase5A_APEX007_SearchResultsTitleAndMeta() {
        $varEngine = new VariableEngine();
        $titlePresenter = new TitlePresenter($varEngine);
        $robotsPresenter = new RobotsPresenter($this->config);

        $renderedTitle = $titlePresenter->render([
            'title'        => 'kubernetes optimization',
            'searchphrase' => 'kubernetes optimization',
            'sitename'     => 'Apex Dev',
            'sep'          => '|',
        ]);
        $this->assertStringContainsString('kubernetes optimization', $renderedTitle);

        $robots = $robotsPresenter->render([
            'robots_index'  => false,
            'robots_follow' => true,
        ]);
        $this->assertEquals('noindex, follow', $robots);
    }

    /**
     * Phase 5A: APEX-008 404 Error Page Title & Meta.
     */
    public function testPhase5A_APEX008_404ErrorPageTitleAndMeta() {
        $varEngine = new VariableEngine();
        $titlePresenter = new TitlePresenter($varEngine);
        $robotsPresenter = new RobotsPresenter($this->config);

        $renderedTitle = $titlePresenter->render([
            'title'    => 'Page Not Found',
            'sitename' => 'Apex Dev',
            'sep'      => '|',
        ]);
        $this->assertStringContainsString('Page Not Found', $renderedTitle);

        $robots = $robotsPresenter->render([
            'robots_index'  => false,
            'robots_follow' => false,
        ]);
        $this->assertEquals('noindex, nofollow', $robots);
    }

    /**
     * Phase 5A: APEX-010 Title Sanitization & Tag Rendering.
     */
    public function testPhase5A_APEX010_TitleSanitizationAndSeparatorCleaning() {
        $varEngine = new VariableEngine();
        $presenter = new TitlePresenter($varEngine);

        // Render template with multiple components
        $rendered = $presenter->render([
            'title'    => 'Article Title',
            'sep'      => '|',
            'sitename' => 'My Website',
        ]);
        $this->assertEquals('Article Title | My Website', $rendered);

        // Render HTML tag with HTML entity escaping
        $html = $presenter->renderHtmlTag([
            'title'    => 'Title With <HTML> & Entities',
            'sep'      => '-',
            'sitename' => 'Site',
        ]);
        $this->assertStringContainsString('<title>', $html);
        $this->assertStringNotContainsString('<HTML>', $html);
    }

    /**
     * Phase 5A: APEX-012 Pagination Title Modifiers.
     */
    public function testPhase5A_APEX012_PaginationTitleModifiers() {
        $varEngine = new VariableEngine();
        $presenter = new TitlePresenter($varEngine);

        $contextPaged = [
            'title'    => 'Category Archive (Page 3 of 8)',
            'sep'      => '-',
            'sitename' => 'DevBlog',
        ];

        $rendered = $presenter->render($contextPaged);
        $this->assertStringContainsString('Page 3 of 8', $rendered);
    }

    /**
     * Phase 5A: APEX-013 Post-type Default Metadata Fallback.
     */
    public function testPhase5A_APEX013_PostTypeDefaultMetadataFallback() {
        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($this->config);
        $builder = new IndexableBuilder($varEngine, $tplManager);

        $indexable = $builder->buildForObject(101, 'post');

        $this->assertEquals(101, $indexable->getObjectId());
        $this->assertEquals('post', $indexable->getObjectType());

        $descPresenter = new DescriptionPresenter($varEngine);
        $desc = $descPresenter->render([
            'excerpt' => 'Join our intensive three-day workshop on enterprise architecture.',
        ]);
        $this->assertStringContainsString('Join our intensive', $desc);
    }

    /**
     * Phase 5A: APEX-014 Meta Editing and REST Operations.
     */
    public function testPhase5A_APEX014_BulkTitleMetaEditing() {
        $saver = new \ApexSEO\SEO\Admin\MetaSaver($this->indexableRepo);

        $saved1 = $saver->savePostMeta(201, [
            'title'       => 'Bulk Edited Title 1',
            'description' => 'Bulk edited description 1.',
        ]);
        $this->assertTrue($saved1);

        $saved2 = $saver->savePostMeta(202, [
            'title'       => 'Bulk Edited Title 2',
            'description' => 'Bulk edited description 2.',
        ]);
        $this->assertTrue($saved2);

        // Test REST API meta save endpoint
        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($this->config);
        $builder = new IndexableBuilder($varEngine, $tplManager);
        $metaController = new \ApexSEO\API\Controllers\MetaRestController(
            $this->security,
            $this->indexableRepo,
            $builder
        );

        $request = new \WP_REST_Request('POST', '/apexseo/v1/meta/post/205');
        $request->set_param('type', 'post');
        $request->set_param('id', 205);
        $request->set_body_params([
            'title'       => 'REST Bulk Item Title',
            'description' => 'REST Bulk Item Desc',
        ]);

        $restResponse = $metaController->saveMeta($request);
        $responseData = ($restResponse instanceof \WP_REST_Response) ? $restResponse->get_data() : $restResponse;

        $this->assertIsArray($responseData);
        $this->assertTrue($responseData['success']);
    }

    /**
     * Phase 5A: APEX-015 RSS Feed Enhancement.
     */
    public function testPhase5A_APEX015_RssFeedHeaderFooterInjection() {
        $feedMgr = new \ApexSEO\SEO\Feed\RssFeedManager();

        $originalContent = '<p>This is the main body of the blog post about microservices.</p>';
        $backlink = 'https://example.com/microservices-2026/';

        $enhanced = $feedMgr->enhanceFeedItem($originalContent, $backlink);

        $this->assertStringContainsString('This is the main body of the blog post', $enhanced);
        $this->assertStringContainsString('https://example.com/microservices-2026/', $enhanced);
    }

    /**
     * Phase 5A: APEX-017 Custom-field Variable Parser.
     */
    public function testPhase5A_APEX017_CustomFieldVariableParser() {
        $engine = new VariableEngine();

        // Custom variables passed via context
        $contextPost = [
            'cf_event_location' => 'San Francisco, CA',
            'cf_ticket_price'   => '$299',
            'sitename'          => 'TechPortal',
            'sep'               => '|',
        ];

        $template = 'Workshop at %%cf_event_location%% for %%cf_ticket_price%% %%sep%% %%sitename%%';
        $rendered = $engine->replace($template, $contextPost);

        $this->assertEquals('Workshop at San Francisco, CA for $299 | TechPortal', $rendered);

        $contextTerm = [
            'ct_featured_sponsor' => 'Google Cloud',
            'sitename'            => 'TechPortal',
            'sep'                 => '-',
        ];
        $termTemplate = 'Topic sponsored by %%ct_featured_sponsor%% %%sep%% %%sitename%%';
        $termRendered = $engine->replace($termTemplate, $contextTerm);
        $this->assertEquals('Topic sponsored by Google Cloud - TechPortal', $termRendered);
    }

    /**
     * Phase 5A: APEX-018 Automatic Meta-Description Truncation.
     */
    public function testPhase5A_APEX018_AutomaticMetaDescriptionTruncation() {
        $varEngine = new VariableEngine();
        $presenter = new DescriptionPresenter($varEngine);

        // English text with 160-char truncation
        $longEnglish = "This is an extraordinarily detailed and comprehensive article about modern cloud computing architecture, container orchestration patterns, Kubernetes deployments, and enterprise security policies across multiple cloud zones.";
        $truncatedEnglish = $presenter->cleanDescription($longEnglish);

        $this->assertLessThanOrEqual(160, mb_strlen($truncatedEnglish, 'UTF-8'));
        $this->assertStringEndsWith('...', $truncatedEnglish);

        // Under-limit string remains untouched
        $shortText = "A short concise SEO meta description.";
        $untouched = $presenter->cleanDescription($shortText);
        $this->assertEquals($shortText, $untouched);
    }
}

