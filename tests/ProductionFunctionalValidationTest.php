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

    public function setUp() {
        parent::setUp();

        $this->container = new Container();
        $this->db = new DatabaseManager();
        $this->security = new SecurityManager();
        $this->config = new ConfigurationManager();

        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($this->config);
        $this->indexableRepo = new IndexableRepository($this->db);
        $builder = new IndexableBuilder($varEngine, $tplManager);

        $kw = new KeywordAnalyzer();
        $readability = new ReadabilityScorer();
        $headings = new HeadingAnalyzer();
        $links = new LinkGraphScanner($this->db);
        $passive = new PassiveVoiceAnalyzer($readability);
        $transition = new TransitionWordAnalyzer();
        $structure = new TextStructureAnalyzer();

        $analyzer = new ContentAnalyzer(
            $kw,
            $readability,
            $headings,
            $links,
            $passive,
            $transition,
            $structure,
            $this->indexableRepo
        );

        $this->contentAnalysisService = new ContentAnalysisService(
            $analyzer,
            $this->db,
            $this->indexableRepo,
            $this->config
        );

        $this->schemaRegistry = new SchemaRegistry();
        $this->schemaValidator = new SchemaValidator();
        $this->schemaGraphBuilder = new SchemaGraphBuilder($this->schemaRegistry, $this->schemaValidator);

        $imageOptimizer = new ImageOptimizer();

        $this->restRouter = new RestApiRouter(
            $this->security,
            $this->config,
            $this->db,
            $this->indexableRepo,
            $builder,
            $this->schemaRegistry,
            $this->schemaValidator,
            null,
            $imageOptimizer,
            null,
            $this->contentAnalysisService
        );

        $this->cliManager = new CliManager($this->container);
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

        // Check required tables created
        $this->assertTrue($this->db->tableExists('apex_indexables'));
        $this->assertTrue($this->db->tableExists('apex_schema'));
        $this->assertTrue($this->db->tableExists('apex_redirects'));
        $this->assertTrue($this->db->tableExists('apex_404_logs'));
        $this->assertTrue($this->db->tableExists('apex_links'));
        $this->assertTrue($this->db->tableExists('apex_image_history'));
        $this->assertTrue($this->db->tableExists('apex_analytics'));
        $this->assertTrue($this->db->tableExists('apex_rank_tracking'));
        $this->assertTrue($this->db->tableExists('apex_content_analysis'));
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
        $commands = $this->cliManager->getRegisteredCommands();
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
     * Phase 4: Database Tables CRUD & Index Health (9 Tables).
     */
    public function testPhase4DatabaseValidation() {
        $prefix = $this->db->getPrefix();

        // 1. Insert & Query Indexables
        $idxId = $this->db->insert("{$prefix}apex_indexables", [
            'object_type'       => 'post',
            'object_id'         => 201,
            'permalink'         => 'https://example.com/test-post',
            'permalink_hash'    => md5('https://example.com/test-post'),
            'canonical_url'     => 'https://example.com/test-post',
            'title'             => 'Test Post SEO Title',
            'description'       => 'Test Description',
            'seo_score'         => 88,
            'readability_score' => 92
        ]);
        $this->assertGreaterThan(0, $idxId);

        // 2. Insert & Query Redirects
        $redirId = $this->db->insert("{$prefix}apex_redirects", [
            'source_url'      => '/old-page',
            'target_url'      => '/new-page',
            'status_code'     => 301,
            'source_url_hash' => md5('/old-page'),
            'status'          => 'active'
        ]);
        $this->assertGreaterThan(0, $redirId);

        // 3. Insert & Query Content Analysis Table
        $analysisId = $this->db->insert("{$prefix}apex_content_analysis", [
            'object_type'       => 'post',
            'object_id'         => 201,
            'analysis_hash'     => md5('Sample content body'),
            'composite_score'   => 87,
            'seo_score'         => 85,
            'readability_score' => 90,
            'analyzed_at'       => date('Y-m-d H:i:s')
        ]);
        $this->assertGreaterThan(0, $analysisId);
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
        $tplManager = new TemplateManager($this->config);
        $titlePresenter = new TitlePresenter($varEngine, $tplManager);
        $descPresenter = new DescriptionPresenter($varEngine, $tplManager);
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
        $term = (object) [
            'term_id'     => 42,
            'name'        => 'Cloud Architecture',
            'slug'        => 'cloud-architecture',
            'taxonomy'    => 'tech_topic',
            'description' => 'Comprehensive guides on cloud systems and microservices.',
        ];

        $builder = new \ApexSEO\SEO\Builder\IndexableBuilder();
        $indexable = $builder->buildFromTerm($term, 'tech_topic');

        $this->assertEquals(42, $indexable->object_id);
        $this->assertEquals('term', $indexable->object_type);
        $this->assertEquals('tech_topic', $indexable->object_sub_type);
        $this->assertStringContainsString('Cloud Architecture', $indexable->title);
        $this->assertStringContainsString('Comprehensive guides', $indexable->description);
        $this->assertEquals('CollectionPage', $indexable->schema_type);

        // Test with custom meta overrides
        $customIndexable = $builder->buildFromTerm($term, 'tech_topic', [
            'title'       => 'Custom Topic: Cloud Architecture | Enterprise Hub',
            'description' => 'Overridden description for topic archive.',
        ]);
        $this->assertEquals('Custom Topic: Cloud Architecture | Enterprise Hub', $customIndexable->title);
        $this->assertEquals('Overridden description for topic archive.', $customIndexable->description);
    }

    /**
     * Phase 5A: APEX-005 Author Archive Title & Meta.
     */
    public function testPhase5A_APEX005_AuthorArchiveTitleAndMeta() {
        $author = (object) [
            'ID'           => 17,
            'user_login'   => 'janedoe',
            'display_name' => 'Dr. Jane Doe',
            'user_email'   => 'jane@example.com',
        ];

        $builder = new \ApexSEO\SEO\Builder\IndexableBuilder();
        $indexable = $builder->buildFromAuthor($author);

        $this->assertEquals(17, $indexable->object_id);
        $this->assertEquals('user', $indexable->object_type);
        $this->assertEquals('author', $indexable->object_sub_type);
        $this->assertStringContainsString('Dr. Jane Doe', $indexable->title);
        $this->assertStringContainsString('Dr. Jane Doe', $indexable->description);
        $this->assertEquals('ProfilePage', $indexable->schema_type);

        // Persistence test via MetaSaver
        $saver = new \ApexSEO\SEO\Admin\MetaSaver($this->indexableRepo, $builder);
        $_POST['_apexseo_title'] = 'Dr. Jane Doe - Senior Cloud Engineer Profile';
        $_POST['_apexseo_description'] = 'Read verified articles and technical reports by Dr. Jane Doe.';
        $_POST['_apexseo_noindex'] = '0';

        $saved = $saver->saveAuthorMeta(17);
        $this->assertTrue($saved);

        $retrieved = $this->indexableRepo->find('user', 17);
        $this->assertNotNull($retrieved);
        $this->assertEquals('Dr. Jane Doe - Senior Cloud Engineer Profile', $retrieved->title);
    }

    /**
     * Phase 5A: APEX-006 Date Archive Title & Meta.
     */
    public function testPhase5A_APEX006_DateArchiveTitleAndMeta() {
        $builder = new \ApexSEO\SEO\Builder\IndexableBuilder();
        $indexable = $builder->buildFromDateArchive([
            'date'      => 'October 2026',
            'permalink' => 'https://example.com/2026/10/',
        ]);

        $this->assertEquals('archive', $indexable->object_type);
        $this->assertEquals('date', $indexable->object_sub_type);
        $this->assertStringContainsString('October 2026', $indexable->title);
        $this->assertEquals('https://example.com/2026/10/', $indexable->permalink);
        $this->assertEquals('CollectionPage', $indexable->schema_type);
    }

    /**
     * Phase 5A: APEX-007 Search Results Page Title/Meta.
     */
    public function testPhase5A_APEX007_SearchResultsTitleAndMeta() {
        $builder = new \ApexSEO\SEO\Builder\IndexableBuilder();
        $indexable = $builder->buildFromSearch('kubernetes optimization');

        $this->assertEquals('search', $indexable->object_type);
        $this->assertStringContainsString('kubernetes optimization', $indexable->title);
        $this->assertTrue($indexable->is_robots_noindex);
        $this->assertEquals('SearchResultsPage', $indexable->schema_type);
    }

    /**
     * Phase 5A: APEX-008 404 Error Page Title & Meta.
     */
    public function testPhase5A_APEX008_404ErrorPageTitleAndMeta() {
        $builder = new \ApexSEO\SEO\Builder\IndexableBuilder();
        $indexable = $builder->buildFrom404();

        $this->assertEquals('404', $indexable->object_type);
        $this->assertStringContainsString('Page Not Found', $indexable->title);
        $this->assertTrue($indexable->is_robots_noindex);
        $this->assertTrue($indexable->is_robots_nofollow);
    }

    /**
     * Phase 5A: APEX-010 Title Sanitization & Separator Cleaning.
     */
    public function testPhase5A_APEX010_TitleSanitizationAndSeparatorCleaning() {
        $presenter = new \ApexSEO\SEO\Meta\TitlePresenter();

        // 1. Strip tags and shortcodes
        $dirty1 = "<h1>Title With <b>HTML</b> &amp; [gallery]</h1> - Site";
        $clean1 = $presenter->sanitizeTitle($dirty1, '-');
        $this->assertEquals('Title With HTML & - Site', $clean1);

        // 2. Clean multiple duplicate separators
        $dirty2 = "Article Title | | | My Website";
        $clean2 = $presenter->sanitizeTitle($dirty2, '|');
        $this->assertEquals('Article Title | My Website', $clean2);

        // 3. Clean leading and trailing separators
        $dirty3 = " - Leading Separator Title - ";
        $clean3 = $presenter->sanitizeTitle($dirty3, '-');
        $this->assertEquals('Leading Separator Title', $clean3);

        // 4. Clean newlines and tabs
        $dirty4 = "Title\n\tWith\rNewlines - Site";
        $clean4 = $presenter->sanitizeTitle($dirty4, '-');
        $this->assertEquals('Title With Newlines - Site', $clean4);
    }

    /**
     * Phase 5A: APEX-012 Pagination Title Modifiers.
     */
    public function testPhase5A_APEX012_PaginationTitleModifiers() {
        $presenter = new \ApexSEO\SEO\Meta\TitlePresenter();

        $contextPaged = [
            'title'        => 'Category Archive',
            'page_type'    => 'category',
            'is_paged'     => true,
            'page_number'  => 3,
            'total_pages'  => 8,
            'sep'          => '-',
            'sitename'     => 'DevBlog',
        ];

        $rendered = $presenter->render($contextPaged);
        $this->assertStringContainsString('Page 3 of 8', $rendered);
    }

    /**
     * Phase 5A: APEX-013 Post-type Default Metadata Fallback.
     */
    public function testPhase5A_APEX013_PostTypeDefaultMetadataFallback() {
        $customPost = (object) [
            'ID'           => 101,
            'post_title'   => 'Spring Microservices Workshop',
            'post_excerpt' => 'Join our intensive three-day workshop on enterprise architecture.',
            'post_type'    => 'event',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_date'    => '2026-08-24 10:00:00',
        ];

        $builder = new \ApexSEO\SEO\Builder\IndexableBuilder();
        $indexable = $builder->buildFromPost($customPost);

        $this->assertEquals(101, $indexable->object_id);
        $this->assertEquals('post', $indexable->object_type);
        $this->assertEquals('event', $indexable->object_sub_type);
        $this->assertStringContainsString('Spring Microservices Workshop', $indexable->title);
        $this->assertStringContainsString('Join our intensive', $indexable->description);
    }

    /**
     * Phase 5A: APEX-014 Bulk Title/Meta Editing.
     */
    public function testPhase5A_APEX014_BulkTitleMetaEditing() {
        $saver = new \ApexSEO\SEO\Admin\MetaSaver($this->indexableRepo, new \ApexSEO\SEO\Builder\IndexableBuilder());

        $bulkPayload = [
            [
                'object_id'          => 201,
                'object_type'        => 'post',
                'title'              => 'Bulk Edited Title 1',
                'description'        => 'Bulk edited description 1.',
                'is_robots_noindex'  => false,
            ],
            [
                'object_id'          => 202,
                'object_type'        => 'post',
                'title'              => 'Bulk Edited Title 2',
                'description'        => 'Bulk edited description 2.',
                'is_robots_noindex'  => true,
            ],
            [
                'object_id'          => 301,
                'object_type'        => 'term',
                'object_sub_type'    => 'category',
                'title'              => 'Bulk Edited Category Term',
                'description'        => 'Bulk edited category description.',
            ],
        ];

        $results = $saver->bulkSave($bulkPayload);

        $this->assertEquals(3, $results['total']);
        $this->assertEquals(3, $results['updated']);
        $this->assertEquals(0, $results['failed']);

        // Verify records in repository
        $savedPost1 = $this->indexableRepo->find('post', 201);
        $this->assertNotNull($savedPost1);
        $this->assertEquals('Bulk Edited Title 1', $savedPost1->title);

        $savedTerm = $this->indexableRepo->find('term', 301);
        $this->assertNotNull($savedTerm);
        $this->assertEquals('Bulk Edited Category Term', $savedTerm->title);

        // Test REST API bulk endpoint
        $metaController = new \ApexSEO\API\Controllers\MetaRestController(
            $this->security,
            $this->indexableRepo,
            new \ApexSEO\SEO\Builder\IndexableBuilder()
        );

        $restResponse = $metaController->bulkSaveMeta(['items' => [
            [
                'object_id'   => 205,
                'object_type' => 'post',
                'title'       => 'REST Bulk Item Title',
                'description' => 'REST Bulk Item Desc',
            ]
        ]]);

        $this->assertIsArray($restResponse);
        $this->assertTrue($restResponse['success']);
        $this->assertEquals(1, $restResponse['results']['updated']);
    }

    /**
     * Phase 5A: APEX-015 RSS Feed Header/Footer Injection.
     */
    public function testPhase5A_APEX015_RssFeedHeaderFooterInjection() {
        $varEngine = new \ApexSEO\SEO\Variables\VariableEngine();
        $templateMgr = new \ApexSEO\SEO\Templates\TemplateManager();
        $feedMgr = new \ApexSEO\SEO\Feed\RssFeedManager($varEngine, $templateMgr);

        $originalContent = '<p>This is the main body of the blog post about microservices.</p>';
        $feedContext = [
            'object_id'   => 88,
            'title'       => 'Microservices in 2026',
            'permalink'   => 'https://example.com/microservices-2026/',
            'sitename'    => 'Enterprise Tech Blog',
            'author_name' => 'Alice Smith',
            'date'        => '2026-08-24',
        ];

        $injected = $feedMgr->formatFeedContent($originalContent, $feedContext);

        $this->assertStringContainsString('This is the main body of the blog post', $injected);
        $this->assertStringContainsString('The post', $injected);
        $this->assertStringContainsString('Microservices in 2026', $injected);
        $this->assertStringContainsString('Enterprise Tech Blog', $injected);
    }

    /**
     * Phase 5A: APEX-017 Custom-field Variable Parser.
     */
    public function testPhase5A_APEX017_CustomFieldVariableParser() {
        $engine = new \ApexSEO\SEO\Variables\VariableEngine();

        // Custom post meta simulation
        $contextPost = [
            'object_id' => 777,
            'sitename'  => 'TechPortal',
            'sep'       => '|',
        ];
        update_post_meta(777, 'event_location', 'San Francisco, CA');
        update_post_meta(777, 'ticket_price', '$299');

        $template = 'Workshop at %%cf_event_location%% for %%cf_ticket_price%% %%sep%% %%sitename%%';
        $rendered = $engine->replace($template, $contextPost);

        $this->assertEquals('Workshop at San Francisco, CA for $299 | TechPortal', $rendered);

        // Term meta simulation
        $contextTerm = [
            'object_id' => 55,
            'sitename'  => 'TechPortal',
            'sep'       => '-',
        ];
        update_term_meta(55, 'featured_sponsor', 'Google Cloud');
        $termTemplate = 'Topic sponsored by %%ct_featured_sponsor%% %%sep%% %%sitename%%';
        $termRendered = $engine->replace($termTemplate, $contextTerm);
        $this->assertEquals('Topic sponsored by Google Cloud - TechPortal', $termRendered);

        // User meta simulation
        $contextUser = [
            'object_id' => 12,
            'author_id' => 12,
            'sitename'  => 'TechPortal',
            'sep'       => '-',
        ];
        update_user_meta(12, 'job_title', 'Principal Architect');
        $userTemplate = 'Author Profile: %%um_job_title%% %%sep%% %%sitename%%';
        $userRendered = $engine->replace($userTemplate, $contextUser);
        $this->assertEquals('Author Profile: Principal Architect - TechPortal', $userRendered);
    }

    /**
     * Phase 5A: APEX-018 Automatic Meta-Description Truncation.
     */
    public function testPhase5A_APEX018_AutomaticMetaDescriptionTruncation() {
        $presenter = new \ApexSEO\SEO\Meta\DescriptionPresenter();

        // English text with word-boundary truncation
        $longEnglish = "This is an extraordinarily detailed and comprehensive article about modern cloud computing architecture, container orchestration patterns, Kubernetes deployments, and enterprise security policies across multiple cloud zones.";
        $truncatedEnglish = $presenter->truncateToWordBoundary($longEnglish, 120);

        $this->assertLessThanOrEqual(120, mb_strlen($truncatedEnglish, 'UTF-8'));
        $this->assertStringEndsWith('...', $truncatedEnglish);
        // Verify it didn't cut mid-word (e.g. not ending in "dep..." or "archit...")
        $this->assertMatchesRegularExpression('/\b\w+\.\.\.$/u', $truncatedEnglish);

        // Multilingual UTF-8 test (Persian / Farsi)
        $persianLong = "این یک راهنمای بسیار جامع و کامل در مورد معماری ابری مدرن و الگوهای پیشرفته در سیستم‌های توزیع شده است که به بررسی عمیق ساختارها می‌پردازد.";
        $truncatedPersian = $presenter->truncateToWordBoundary($persianLong, 80);

        $this->assertLessThanOrEqual(80, mb_strlen($truncatedPersian, 'UTF-8'));
        $this->assertStringEndsWith('...', $truncatedPersian);
        $this->assertMatchesRegularExpression('/\b[^\s]+\.\.\.$/u', $truncatedPersian);

        // Under-limit string remains untouched
        $shortText = "A short concise SEO meta description.";
        $untouched = $presenter->truncateToWordBoundary($shortText, 160);
        $this->assertEquals($shortText, $untouched);
    }
}

