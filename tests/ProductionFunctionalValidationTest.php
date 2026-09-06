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
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\SEO\Analysis\ContentAnalysisService;
use ApexSEO\SEO\Meta\TitlePresenter;
use ApexSEO\SEO\Meta\DescriptionPresenter;
use ApexSEO\SEO\Meta\CanonicalPresenter;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\SEO\Feed\RssFeedManager;
use ApexSEO\API\RestApiRouter;

/**
 * Class ProductionFunctionalValidationTest
 *
 * Production-contract smoke suite validating core initialized components,
 * 8 locked database tables, REST router status and controller registry,
 * 11 WP-CLI commands, content analysis shape, Indexable builder,
 * presenters, security helpers, RSS enhancement, and variable replacement.
 */
class ProductionFunctionalValidationTest extends TestCase {
    protected $container;
    protected $db;
    protected $security;
    protected $config;
    protected $indexableRepo;
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
        $this->restRouter = $this->container->get(RestApiRouter::class);
        $this->cliManager = new CliManager();
    }

    /**
     * 1. Plugin and container initialization contract.
     */
    public function testPluginAndContainerInitialization(): void {
        $plugin = Plugin::getInstance();
        $this->assertInstanceOf(Plugin::class, $plugin);

        $container = $plugin->getContainer();
        $this->assertInstanceOf(Container::class, $container);

        $this->assertInstanceOf(DatabaseManager::class, $container->get(DatabaseManager::class));
        $this->assertInstanceOf(SecurityManager::class, $container->get(SecurityManager::class));
        $this->assertInstanceOf(ConfigurationManager::class, $container->get(ConfigurationManager::class));
        $this->assertInstanceOf(IndexableRepository::class, $container->get(IndexableRepository::class));
        $this->assertInstanceOf(ContentAnalysisService::class, $container->get(ContentAnalysisService::class));
        $this->assertInstanceOf(RestApiRouter::class, $container->get(RestApiRouter::class));
    }

    /**
     * 2. Eight-table database schema migration contract.
     */
    public function testEightTableMigration(): void {
        $migration = new Migration_1_0_0_CreateLockedTables();
        $result = $migration->up($this->db);
        $this->assertTrue($result);

        $prefix = $this->db->getPrefix();
        $this->assertTrue($this->db->hasTable("{$prefix}apex_indexables"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_schema"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_redirects"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_404_logs"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_links"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_image_history"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_analytics"));
        $this->assertTrue($this->db->hasTable("{$prefix}apex_rank_tracking"));

        // Confirm 9th table does NOT exist
        $this->assertFalse($this->db->hasTable("{$prefix}apex_content_analysis"), '9th table apex_content_analysis must not exist');
    }

    /**
     * 3. REST router status and controller registry contract.
     */
    public function testRestRouterStatusAndControllerRegistry(): void {
        $controllers = $this->restRouter->getControllers();
        $this->assertCount(11, $controllers);

        // Production contract: RestApiRouter::getStatus(WP_REST_Request)
        $statusReq = new \WP_REST_Request('GET', '/apexseo/v1/status');
        $statusResp = $this->restRouter->getStatus($statusReq);
        $statusData = ($statusResp instanceof \WP_REST_Response) ? $statusResp->get_data() : $statusResp;

        $this->assertTrue($statusData['success']);
        $this->assertSame('operational', $statusData['status']);
        $this->assertCount(11, $statusData['controllers']);

        // Verify key controllers are registered and retrievable
        $expectedControllers = [
            'settings', 'meta', 'schema', 'redirects', 'not_found',
            'links', 'analytics', 'cache', 'media', 'migration', 'analysis',
        ];
        foreach ($expectedControllers as $name) {
            $this->assertNotNull($this->restRouter->getController($name), "Controller {$name} must exist");
        }
    }

    /**
     * 4. Eleven CLI command registrations contract.
     */
    public function testWpCliCommandRegistrations(): void {
        $commands = $this->cliManager->getCommands();
        $this->assertCount(11, $commands);

        $expectedCommands = [
            'index', 'cache', 'media', 'redirect', 'db',
            'migrate', 'sitemap', 'doctor', 'report', 'schema', 'analysis',
        ];
        foreach ($expectedCommands as $cmd) {
            $this->assertArrayHasKey($cmd, $commands, "CLI command {$cmd} must be registered");
        }
    }

    /**
     * 5. Real content-analysis result shape contract.
     */
    public function testRealContentAnalysisResultShape(): void {
        $postId = 301;
        $content = '<h2>مقدمه بهینه‌سازی</h2><p>سئو وردپرس یکی از مهم‌ترین استراتژی‌ها است. بنابراین باید به آن توجه شود.</p><h2>مزایای سئو</h2><p>علاوه بر این، لینک‌های داخلی مانند <a href="https://example.com/internal">راهنما</a> نقش مهمی دارند.</p>';
        $keyword = 'سئو وردپرس';

        $analysis = $this->contentAnalysisService->analyzeContent($postId, $content, $keyword);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('keyword_analysis', $analysis);
        $this->assertArrayHasKey('readability_score', $analysis);
        $this->assertArrayHasKey('headings', $analysis);
        $this->assertArrayHasKey('links_count', $analysis);
        $this->assertArrayHasKey('passive_voice', $analysis);
        $this->assertArrayHasKey('transition_words', $analysis);
        $this->assertArrayHasKey('text_structure', $analysis);

        $this->assertIsArray($analysis['keyword_analysis']);
        $this->assertIsInt($analysis['readability_score']);
        $this->assertIsArray($analysis['headings']);
        $this->assertIsInt($analysis['links_count']);
        $this->assertGreaterThanOrEqual(1, $analysis['links_count']);
        $this->assertIsArray($analysis['passive_voice']);
        $this->assertIsArray($analysis['transition_words']);
        $this->assertIsArray($analysis['text_structure']);

        // Word-scale execution: elapsed time and memory bounds without fictional output fields
        $words = ["seo", "optimization", "ranking", "google", "meta", "content", "keyword", "strategy", "structure", "performance"];
        $longContent = "";
        for ($i = 0; $i < 200; $i++) {
            $longContent .= "<p>" . implode(" ", $words) . " paragraph line number {$i}. Therefore, ranking is optimized.</p>";
        }

        $startTime = microtime(true);
        $startMem = memory_get_usage();
        $largeAnalysis = $this->contentAnalysisService->analyzeContent(999, $longContent, 'seo');
        $elapsed = microtime(true) - $startTime;
        $memoryConsumed = (memory_get_usage() - $startMem) / (1024 * 1024);

        $this->assertIsArray($largeAnalysis);
        $this->assertArrayHasKey('readability_score', $largeAnalysis);
        $this->assertLessThan(10.0, $elapsed);
        $this->assertLessThan(30, $memoryConsumed);
    }

    /**
     * 6. Indexable builder contract using buildForObject().
     */
    public function testIndexableBuilderBuildForObject(): void {
        $varEngine = new VariableEngine();
        $tplManager = new TemplateManager($this->config);
        $builder = new IndexableBuilder($varEngine, $tplManager);

        $indexable = $builder->buildForObject(101, 'post');

        $this->assertInstanceOf(Indexable::class, $indexable);
        $this->assertSame(101, $indexable->getObjectId());
        $this->assertSame('post', $indexable->getObjectType());
        $this->assertTrue($indexable->getRobotsIndex());
        $this->assertTrue($indexable->getRobotsFollow());
        $this->assertNotEmpty($indexable->getPermalink());
        $this->assertNotEmpty($indexable->getCanonicalUrl());
    }

    /**
     * 7. Indexable model getters and repository payload contract.
     */
    public function testIndexableGettersAndRepositoryPayload(): void {
        $data = [
            'object_id'             => 201,
            'object_type'           => 'post',
            'object_sub_type'       => 'post',
            'permalink'             => 'https://example.com/test-post',
            'canonical_url'         => 'https://example.com/test-post',
            'title'                 => 'Test Post Title',
            'description'           => 'Test Description',
            'robots_index'          => true,
            'robots_follow'         => true,
            'primary_focus_keyword' => 'cloud architecture',
            'keyword_density'       => 1.85,
            'readability_score'     => 92,
            'content_analysis'      => ['score' => 88, 'readability' => 92],
            'is_cornerstone'        => false,
        ];

        $indexable = new Indexable($data);

        $this->assertSame(201, $indexable->getObjectId());
        $this->assertSame('post', $indexable->getObjectType());
        $this->assertSame('post', $indexable->getObjectSubType());
        $this->assertSame('https://example.com/test-post', $indexable->getPermalink());
        $this->assertSame('https://example.com/test-post', $indexable->getCanonicalUrl());
        $this->assertSame('Test Post Title', $indexable->getTitle());
        $this->assertSame('Test Description', $indexable->getDescription());
        $this->assertTrue($indexable->getRobotsIndex());
        $this->assertTrue($indexable->getRobotsFollow());
        $this->assertSame('cloud architecture', $indexable->getPrimaryFocusKeyword());
        $this->assertSame(1.85, $indexable->getKeywordDensity());
        $this->assertSame(92, $indexable->getReadabilityScore());
        $this->assertSame(['score' => 88, 'readability' => 92], $indexable->getContentAnalysis());
        $this->assertFalse($indexable->isCornerstone());

        $payload = $indexable->toArray();
        $this->assertIsArray($payload);
        $this->assertSame(201, $payload['object_id']);
        $this->assertSame('post', $payload['object_type']);
        $this->assertSame(1, $payload['robots_index']);
        $this->assertSame(1, $payload['robots_follow']);
        $this->assertSame(0, $payload['is_cornerstone']);

        // Save through repository
        $saved = $this->indexableRepo->save($indexable);
        $this->assertTrue($saved);
    }

    /**
     * 8. Presenters: Title, Description, Canonical, Robots contracts.
     */
    public function testMetaPresenters(): void {
        $varEngine = new VariableEngine();
        $titlePresenter = new TitlePresenter($varEngine);
        $descPresenter = new DescriptionPresenter($varEngine);
        $canonicalPresenter = new CanonicalPresenter();
        $robotsPresenter = new RobotsPresenter($this->config);

        $context = [
            'title'         => 'Sample Article',
            'description'   => 'Sample description content.',
            'canonical_url' => 'https://example.com/sample-article',
            'sep'           => '-',
            'sitename'      => 'Apex Site',
            'robots_index'  => true,
            'robots_follow' => true,
        ];

        // Title Presenter
        $titleHtml = $titlePresenter->renderHtmlTag($context);
        $this->assertStringContainsString('<title>', $titleHtml);
        $this->assertStringContainsString('Sample Article', $titleHtml);

        // Description Presenter
        $descHtml = $descPresenter->renderHtmlTag($context);
        $this->assertStringContainsString('name="description"', $descHtml);
        $this->assertStringContainsString('Sample description content.', $descHtml);

        // Truncation check
        $longText = str_repeat('A very long sentence about SEO optimization. ', 10);
        $truncated = $descPresenter->cleanDescription($longText);
        $this->assertLessThanOrEqual(160, mb_strlen($truncated, 'UTF-8'));
        $this->assertStringEndsWith('...', $truncated);

        // Canonical Presenter
        $canonicalHtml = $canonicalPresenter->renderHtmlTag($context);
        $this->assertStringContainsString('rel="canonical"', $canonicalHtml);
        $this->assertStringContainsString('https://example.com/sample-article', $canonicalHtml);

        // Robots Presenter
        $robotsHtml = $robotsPresenter->renderHtmlTag($context);
        $this->assertStringContainsString('name="robots"', $robotsHtml);
        $this->assertStringContainsString('index, follow', $robotsHtml);

        $noindexRobots = $robotsPresenter->render(['robots_index' => false, 'robots_follow' => true]);
        $this->assertSame('noindex, follow', $noindexRobots);
    }

    /**
     * 9. Security Manager helpers contract.
     */
    public function testSecurityHelpers(): void {
        // String sanitization
        $xss = "<script>alert('xss');</script>Sanitized Title";
        $cleanString = $this->security->sanitizeString($xss);
        $this->assertStringNotContainsString('<script>', $cleanString);
        $this->assertStringContainsString('Sanitized Title', $cleanString);

        // Array sanitization
        $arrayInput = [
            'title'  => "<script>bad();</script>Good Title",
            'nested' => ['content' => "<b>bold</b><img src=x onerror=alert(1)>"],
        ];
        $cleanArray = $this->security->sanitizeArray($arrayInput);
        $this->assertStringNotContainsString('<script>', $cleanArray['title']);
        $this->assertStringNotContainsString('onerror', $cleanArray['nested']['content']);

        // Redirect validation
        $validRedirect = $this->security->validateRedirect('https://example.com/dashboard');
        $this->assertNotEmpty($validRedirect);

        // Permission checks
        $this->assertIsBool($this->security->checkAdminPermission());
        $this->assertIsBool($this->security->checkEditorPermission());
        $this->assertIsBool($this->security->checkUploadPermission());
    }

    /**
     * 10. RSS Feed enhancement contract.
     */
    public function testRssFeedEnhancement(): void {
        $feedManager = new RssFeedManager();
        $originalContent = '<p>This is the original body of the post.</p>';
        $backlink = 'https://example.com/post-permalink/';

        $enhanced = $feedManager->enhanceFeedItem($originalContent, $backlink);

        $this->assertStringContainsString('This is the original body of the post.', $enhanced);
        $this->assertStringContainsString('https://example.com/post-permalink/', $enhanced);
    }

    /**
     * 11. Variable Engine template replacement contract.
     */
    public function testVariableEngineReplacement(): void {
        $engine = new VariableEngine();

        $context = [
            'title'           => 'My Post',
            'sep'             => '|',
            'sitename'        => 'Apex SEO',
            'cf_custom_field' => 'CustomFieldValue',
        ];

        $rendered = $engine->replace('%%title%% %%sep%% %%sitename%%', $context);
        $this->assertSame('My Post | Apex SEO', $rendered);

        $customRendered = $engine->replace('Value: %%cf_custom_field%%', $context);
        $this->assertSame('Value: CustomFieldValue', $customRendered);
    }
}
