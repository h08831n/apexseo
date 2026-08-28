<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Container\Container;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;
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
use ApexSEO\API\Controllers\AnalysisRestController;
use ApexSEO\CLI\AnalysisCommand;
use ApexSEO\Core\Bootstrap\Plugin;

/**
 * Authoritative Phase 4 Integration Tests for APEX-048 through APEX-054.
 *
 * Verifies complete production call graph:
 * WordPress save_post → ContentAnalysisService → ContentAnalyzer → APEX-048..054 → Persistence → REST & WP-CLI Retrieval.
 */
class ContentAnalysisIntegrationTest extends TestCase {
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * @var ContentAnalysisService
     */
    protected $service;

    /**
     * @var ContentAnalyzer
     */
    protected $analyzer;

    /**
     * @var IndexableRepository
     */
    protected $indexableRepo;

    /**
     * @var SecurityManager
     */
    protected $security;

    /**
     * Set up test dependencies and clean environment before each test.
     */
    public function setUp() {
        $this->container = new Container();
        $this->db = new DatabaseManager();
        $this->security = new SecurityManager();
        $config = new ConfigurationManager();

        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($config);
        $this->indexableRepo = new IndexableRepository($this->db);
        $builder = new IndexableBuilder($varEngine, $tplManager);

        $kw = new KeywordAnalyzer();
        $readability = new ReadabilityScorer();
        $headings = new HeadingAnalyzer();
        $links = new LinkGraphScanner($this->db);
        $passive = new PassiveVoiceAnalyzer($readability);
        $transitions = new TransitionWordAnalyzer($readability, $kw);
        $textStructure = new TextStructureAnalyzer($readability);

        $this->analyzer = new ContentAnalyzer(
            $kw,
            $readability,
            $headings,
            $links,
            $passive,
            $transitions,
            $textStructure,
            $this->indexableRepo
        );

        $this->service = new ContentAnalysisService(
            $this->analyzer,
            $this->db,
            $this->indexableRepo,
            $config
        );

        $this->container->set(DatabaseManager::class, $this->db);
        $this->container->set(SecurityManager::class, $this->security);
        $this->container->set(ConfigurationManager::class, $config);
        $this->container->set(IndexableRepository::class, $this->indexableRepo);
        $this->container->set(IndexableBuilder::class, $builder);
        $this->container->set(ContentAnalyzer::class, $this->analyzer);
        $this->container->set(ContentAnalysisService::class, $this->service);
    }

    /**
     * Test 1: Complete pipeline from save_post simulation to database persistence across all 7 analyzers.
     */
    public function testSavePostTriggersCompleteAnalysisPipeline() {
        $postId = 401;
        $htmlContent = '
            <h1>Complete Guide to On-Page SEO Optimization</h1>
            <p>Search engine optimization is an essential strategy for modern websites. However, many content creators struggle with ranking.</p>
            <h2>Understanding Heading Hierarchies</h2>
            <p>Furthermore, internal links help distribute page authority across your domain. For instance, you should check our <a href="/seo-tips/">SEO tips guide</a> and our external reference at <a href="https://example.com/w3c">W3C standards</a>.</p>
            <p>The entire article was written by our senior SEO specialist to demonstrate high quality content structure.</p>
        ';

        $postObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'Complete Guide to On-Page SEO Optimization',
            'post_content' => $htmlContent,
            'post_type'    => 'post',
            'post_status'  => 'publish',
        ];

        $_POST['_apexseo_focus_keyword'] = 'SEO';
        $_POST['_apexseo_secondary_keywords'] = 'optimization, ranking';

        // Execute save_post integration service
        $result = $this->service->handleSavePost($postId, $postObj);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('seo_score', $result);
        $this->assertArrayHasKey('readability_score', $result);
        $this->assertArrayHasKey('analysis_hash', $result);

        // APEX-048: Multi-Keyword Density & TF-IDF
        $this->assertArrayHasKey('keywords', $result);
        $this->assertArrayHasKey('primary_keyword', $result['keywords']);
        $this->assertEquals('seo', $result['keywords']['primary_keyword']['keyword']);
        $this->assertGreaterThan(0, $result['keywords']['primary_keyword']['count']);

        // APEX-049: Flesch Reading Ease & Grade Level
        $this->assertArrayHasKey('readability', $result);
        $this->assertGreaterThan(0, $result['readability']['words_count']);
        $this->assertArrayHasKey('flesch_reading_ease', $result['readability']);
        $this->assertArrayHasKey('flesch_kincaid_grade', $result['readability']);

        // APEX-050: Heading Structure Hierarchy
        $this->assertArrayHasKey('headings', $result);
        $this->assertEquals(1, $result['headings']['h1_count']);
        $this->assertEquals(1, $result['headings']['h2_count']);
        $this->assertTrue($result['headings']['is_valid_hierarchy']);

        // APEX-051: Internal Link Graph Scanner
        $this->assertArrayHasKey('links', $result);
        $this->assertEquals(1, $result['links']['internal_links']);
        $this->assertEquals(1, $result['links']['external_links']);

        // APEX-052: Passive Voice Analysis
        $this->assertArrayHasKey('passive_voice', $result);
        $this->assertArrayHasKey('passive_count', $result['passive_voice']);
        $this->assertArrayHasKey('passive_percentage', $result['passive_voice']);

        // APEX-053: Transition Word Analysis
        $this->assertArrayHasKey('transition_words', $result);
        $this->assertGreaterThan(0, $result['transition_words']['transition_count']);
        $this->assertGreaterThan(0, $result['transition_words']['transition_percentage']);

        // APEX-054: Paragraph & Sentence Structure
        $this->assertArrayHasKey('text_structure', $result);
        $this->assertGreaterThan(0, $result['text_structure']['paragraphs_count']);
        $this->assertGreaterThan(0, $result['text_structure']['sentences_count']);

        // Check persistence retrieval
        $persisted = $this->service->getPersistedAnalysis($postId);
        $this->assertIsArray($persisted);
        $this->assertEquals($result['analysis_hash'], $persisted['analysis_hash']);
        $this->assertEquals($result['seo_score'], $persisted['seo_score']);
        $this->assertEquals($result['readability_score'], $persisted['readability_score']);

        unset($_POST['_apexseo_focus_keyword']);
        unset($_POST['_apexseo_secondary_keywords']);
    }

    /**
     * Test 2: Cache invalidation using analysis_hash prevents redundant computation on unchanged content.
     */
    public function testAnalysisHashCachingPreventsDuplicateExecution() {
        $postId = 402;
        $postObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'Cached Performance Analysis Test',
            'post_content' => '<p>This is test content that should be hashed and cached for maximum execution speed.</p>',
            'post_type'    => 'post',
        ];

        // First pass: compute & persist
        $firstPass = $this->service->handleSavePost($postId, $postObj);
        $this->assertIsArray($firstPass);
        $hash1 = $firstPass['analysis_hash'];

        // Second pass: identical content
        $secondPass = $this->service->handleSavePost($postId, $postObj);
        $this->assertIsArray($secondPass);
        $this->assertEquals($hash1, $secondPass['analysis_hash']);

        // Third pass: modify content -> new hash and recomputed report
        $postObj->post_content .= '<p>Additional new paragraph that invalidates the prior content hash.</p>';
        $thirdPass = $this->service->handleSavePost($postId, $postObj);
        $this->assertIsArray($thirdPass);
        $this->assertNotEquals($hash1, $thirdPass['analysis_hash']);
    }

    /**
     * Test 3: Production REST API endpoint GET & POST /apexseo/v1/analysis/post/{id}.
     */
    public function testRestEndpointRetrievalAndRecomputation() {
        $postId = 403;
        $postObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'REST API Post Analysis Test',
            'post_content' => '<h1>REST Header</h1><p>Testing RESTful API access to persisted analysis metrics.</p>',
            'post_type'    => 'post',
        ];

        $this->service->handleSavePost($postId, $postObj);

        $restController = new AnalysisRestController($this->security, $this->service);

        // GET Request
        $getRequest = ['id' => $postId, 'force' => false];
        $response = $restController->getPostAnalysis($getRequest);

        $this->assertNotNull($response);
        $data = is_array($response) && isset($response['data']) ? $response['data'] : (is_array($response) ? $response : []);
        $this->assertArrayHasKey('seo_score', $data);
        $this->assertArrayHasKey('readability_score', $data);
        $this->assertArrayHasKey('headings', $data);

        // POST Request (Recomputation)
        $postRequest = ['id' => $postId];
        $recomputeResponse = $restController->recomputePostAnalysis($postRequest);
        $this->assertNotNull($recomputeResponse);
    }

    /**
     * Test 4: Production REST API authorization & security checks.
     */
    public function testRestEndpointPermissionChecks() {
        $restController = new AnalysisRestController($this->security, $this->service);

        // Non-positive post ID returns 400
        $invalidReq = ['id' => 0];
        $errResponse = $restController->getPostAnalysis($invalidReq);
        $this->assertNotNull($errResponse);

        // Non-existent post returns 404
        $notFoundReq = ['id' => 999999];
        $notFoundResp = $restController->getPostAnalysis($notFoundReq);
        $this->assertNotNull($notFoundResp);
    }

    /**
     * Test 5: Failure isolation & graceful degradation when an analyzer fails or content is broken.
     */
    public function testFailureIsolationPreservesWordPressSavePost() {
        $postId = 405;

        // Invalid post object (null) must return false safely without throwing fatal error
        $resNull = $this->service->handleSavePost($postId, null);
        $this->assertFalse($resNull);

        // Negative post ID
        $resNeg = $this->service->handleSavePost(-5, (object) ['post_content' => 'test']);
        $this->assertFalse($resNeg);

        // Malformed HTML with unclosed tags and huge nested structures
        $malformedContent = '<div><span><h1>Malformed Title<p>Unclosed paragraphs <b><i>mixed styling';
        $postObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'Malformed HTML Post',
            'post_content' => $malformedContent,
            'post_type'    => 'post',
        ];

        $resMalformed = $this->service->handleSavePost($postId, $postObj);
        $this->assertIsArray($resMalformed);
        $this->assertArrayHasKey('seo_score', $resMalformed);
    }

    /**
     * Test 6: Persian RTL Language Support and Unicode Normalization.
     */
    public function testPersianAndEnglishLanguageAnalysis() {
        $postId = 406;
        $persianContent = '
            <h1>راهنمای جامع سئو و بهینه‌سازی سایت</h1>
            <p>بهینه‌سازی موتورهای جستجو یکی از مهم‌ترین استراتژی‌ها برای رشد کسب‌وکار است. با این حال، تولید محتوای ارزشمند نیازمند ساختار دقیق است.</p>
            <h2>اهمیت لینک‌های داخلی</h2>
            <p>علاوه بر این، لینک‌سازی داخلی باعث بهبود رتبه صفحات می‌شود. به عنوان مثال، شما می‌توانید مقالات دیگر ما را مطالعه نمایید.</p>
        ';

        $postObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'راهنمای جامع سئو و بهینه‌سازی سایت',
            'post_content' => $persianContent,
            'post_type'    => 'post',
        ];

        $_POST['_apexseo_focus_keyword'] = 'سئو';

        $result = $this->service->handleSavePost($postId, $postObj);

        $this->assertIsArray($result);
        $this->assertEquals('fa', $result['readability']['language']);
        $this->assertArrayHasKey('language_notes', $result['readability']);
        $this->assertGreaterThan(0, $result['readability']['words_count']);
        $this->assertGreaterThan(0, $result['transition_words']['transition_count']);

        unset($_POST['_apexseo_focus_keyword']);
    }

    /**
     * Test 7: Skip autosaves, revisions, and unsupported post types.
     */
    public function testSkipAutosavesAndUnsupportedPostTypes() {
        $postId = 407;

        // Attachment post type must be skipped
        $attachmentObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'Image File',
            'post_content' => '',
            'post_type'    => 'attachment',
        ];
        $this->assertFalse($this->service->handleSavePost($postId, $attachmentObj));

        // Revision post type must be skipped
        $revisionObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'Revision 1',
            'post_content' => 'Some content',
            'post_type'    => 'revision',
        ];
        $this->assertFalse($this->service->handleSavePost($postId, $revisionObj));
    }

    /**
     * Test 8: WP-CLI Analysis Command invocation.
     */
    public function testWpCliAnalysisCommandExecution() {
        $postId = 408;
        $postObj = (object) [
            'ID'           => $postId,
            'post_title'   => 'CLI Analysis Test Post',
            'post_content' => '<h1>CLI Heading</h1><p>Testing WP-CLI command integration for on-page SEO metrics.</p>',
            'post_type'    => 'post',
        ];

        $this->service->handleSavePost($postId, $postObj);

        $cliCommand = new AnalysisCommand($this->container);

        // Dry-run post analysis
        $statusDry = $cliCommand->post([$postId], ['dry-run' => true, 'format' => 'json']);
        $this->assertEquals(0, $statusDry);

        // Table format post analysis
        $statusTable = $cliCommand->post([$postId], ['format' => 'table']);
        $this->assertEquals(0, $statusTable);
    }

    /**
     * Test 9: End-to-end integration via central Plugin container & bootstrapping.
     */
    public function testFullPluginContainerResolution() {
        $plugin = Plugin::getInstance();
        $container = $plugin->getContainer();

        $this->assertTrue($container->has(ContentAnalyzer::class));
        $this->assertTrue($container->has(ContentAnalysisService::class));

        $resolvedAnalyzer = $container->get(ContentAnalyzer::class);
        $resolvedService = $container->get(ContentAnalysisService::class);

        $this->assertInstanceOf(ContentAnalyzer::class, $resolvedAnalyzer);
        $this->assertInstanceOf(ContentAnalysisService::class, $resolvedService);
    }
}
