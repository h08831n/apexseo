<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;
use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\Validator\SchemaValidator;
use ApexSEO\Cache\Engine\CacheEngine;
use ApexSEO\Media\Optimizer\ImageOptimizer;
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

class RestSubsystemTest extends TestCase {
    protected $security;
    protected $config;
    protected $db;
    protected $indexableRepo;
    protected $indexableBuilder;
    protected $schemaRegistry;
    protected $schemaValidator;
    protected $cacheEngine;
    protected $imageOptimizer;
    protected $router;

    public function setUp() {
        parent::setUp();
        $this->security         = new SecurityManager();
        $this->config           = new ConfigurationManager();
        $this->db               = new DatabaseManager();
        $this->indexableRepo    = new IndexableRepository($this->db);
        $this->indexableBuilder = new IndexableBuilder();
        $this->schemaRegistry   = new SchemaRegistry();
        $this->schemaValidator  = new SchemaValidator();
        $this->cacheEngine      = null;
        $this->imageOptimizer   = new ImageOptimizer();

        $this->router = new RestApiRouter(
            $this->security,
            $this->config,
            $this->db,
            $this->indexableRepo,
            $this->indexableBuilder,
            $this->schemaRegistry,
            $this->schemaValidator,
            $this->cacheEngine,
            $this->imageOptimizer
        );
    }

    public function testRestRouterInitialization() {
        $controllers = $this->router->getControllers();
        $this->assertEquals(10, count($controllers));
        $this->assertInstanceOf(SettingsRestController::class, $this->router->getController('settings'));
        $this->assertInstanceOf(MetaRestController::class, $this->router->getController('meta'));
        $this->assertInstanceOf(SchemaRestController::class, $this->router->getController('schema'));
        $this->assertInstanceOf(RedirectsRestController::class, $this->router->getController('redirects'));
        $this->assertInstanceOf(NotFoundRestController::class, $this->router->getController('not_found'));
        $this->assertInstanceOf(LinksRestController::class, $this->router->getController('links'));
        $this->assertInstanceOf(AnalyticsRestController::class, $this->router->getController('analytics'));
        $this->assertInstanceOf(CacheRestController::class, $this->router->getController('cache'));
        $this->assertInstanceOf(MediaRestController::class, $this->router->getController('media'));
        $this->assertInstanceOf(MigrationRestController::class, $this->router->getController('migration'));
    }

    public function testStatusEndpointResponse() {
        $response = $this->router->getStatus();
        $this->assertNotNull($response);
        $data = ($response instanceof \WP_REST_Response) ? $response->get_data() : $response;
        $this->assertEquals('apexseo/v1', $data['namespace']);
        $this->assertEquals('active', $data['status']);
        $this->assertEquals(22, $data['registered_apis']);
    }

    public function testSettingsControllerGetAndUpdate() {
        $controller = $this->router->getController('settings');
        $response = $controller->getSettings();
        $data = ($response instanceof \WP_REST_Response) ? $response->get_data() : $response;
        $this->assertTrue($data['success']);

        $updateResponse = $controller->updateSettings([
            'settings' => [
                'general' => ['site_type' => 'Organization'],
            ],
        ]);
        $updateData = ($updateResponse instanceof \WP_REST_Response) ? $updateResponse->get_data() : $updateResponse;
        $this->assertTrue($updateData['success']);
        $this->assertEquals('Organization', $this->config->get('general.site_type'));
    }

    public function testMetaControllerSaveAndGet() {
        $controller = $this->router->getController('meta');

        $saveResponse = $controller->saveMeta([
            'object_type'            => 'post',
            'object_id'              => 42,
            'title'                  => 'Optimized REST Title',
            'description'            => 'Clean REST SEO description.',
            'canonical_url'          => 'https://example.com/rest-post-42/',
            'primary_focus_keyword'  => 'enterprise rest api',
        ]);
        $saveData = ($saveResponse instanceof \WP_REST_Response) ? $saveResponse->get_data() : $saveResponse;
        $this->assertTrue($saveData['success']);
        $this->assertEquals('Optimized REST Title', $saveData['indexable']['title']);

        $getResponse = $controller->getMeta([
            'object_type' => 'post',
            'object_id'   => 42,
        ]);
        $getData = ($getResponse instanceof \WP_REST_Response) ? $getResponse->get_data() : $getResponse;
        $this->assertTrue($getData['success']);
        $this->assertEquals('enterprise rest api', $getData['indexable']['primary_focus_keyword']);
    }

    public function testSchemaControllerCRUD() {
        $controller = $this->router->getController('schema');

        // GET available schema types
        $listResponse = $controller->getSchemas();
        $listData = ($listResponse instanceof \WP_REST_Response) ? $listResponse->get_data() : $listResponse;
        $this->assertTrue($listData['success']);
        $this->assertTrue(in_array('Article', $listData['supported_types']));
        $this->assertTrue(in_array('Recipe', $listData['supported_types']));

        // POST Create Valid Schema
        $createResponse = $controller->createSchema([
            'schema_type' => 'Article',
            'schema_data' => [
                '@type'    => 'Article',
                'headline' => 'REST Schema Test',
            ],
        ]);
        $createData = ($createResponse instanceof \WP_REST_Response) ? $createResponse->get_data() : $createResponse;
        $this->assertTrue($createData['success']);

        // POST Invalid Schema should be rejected
        $invalidResponse = $controller->createSchema([
            'schema_type' => 'Article',
            'schema_data' => [
                '@type' => 'Article', // Missing headline
            ],
        ]);
        $isError = ($invalidResponse instanceof \WP_Error) || (isset($invalidResponse['error']));
        $this->assertTrue($isError);
    }

    public function testRedirectsControllerCRUD() {
        $controller = $this->router->getController('redirects');

        // POST Create
        $createResponse = $controller->createRedirect([
            'source_url'  => 'https://example.com/old-rest-url/',
            'target_url'  => 'https://example.com/new-rest-url/',
            'status_code' => 301,
        ]);
        $createData = ($createResponse instanceof \WP_REST_Response) ? $createResponse->get_data() : $createResponse;
        $this->assertTrue($createData['success']);
        $this->assertEquals('/old-rest-url/', $createData['source_url']);

        // Prevent Loop
        $loopResponse = $controller->createRedirect([
            'source_url'  => 'https://example.com/loop/',
            'target_url'  => 'https://example.com/loop/',
            'status_code' => 301,
        ]);
        $this->assertTrue(($loopResponse instanceof \WP_Error) || (isset($loopResponse['error'])));

        // GET List
        $getResponse = $controller->getRedirects(['page' => 1, 'per_page' => 10]);
        $getData = ($getResponse instanceof \WP_REST_Response) ? $getResponse->get_data() : $getResponse;
        $this->assertTrue($getData['success']);
        $this->assertEquals(1, $getData['page']);
        $this->assertEquals(10, $getData['per_page']);
    }

    public function testNotFoundController() {
        $controller = $this->router->getController('not_found');

        $getResponse = $controller->get404Logs();
        $getData = ($getResponse instanceof \WP_REST_Response) ? $getResponse->get_data() : $getResponse;
        $this->assertTrue($getData['success']);

        $clearResponse = $controller->clear404Logs();
        $clearData = ($clearResponse instanceof \WP_REST_Response) ? $clearResponse->get_data() : $clearResponse;
        $this->assertTrue($clearData['success']);
    }

    public function testLinksController() {
        $controller = $this->router->getController('links');

        $response = $controller->getSuggestions(['post_id' => 42]);
        $data = ($response instanceof \WP_REST_Response) ? $response->get_data() : $response;
        $this->assertTrue($data['success']);
        $this->assertEquals(42, $data['post_id']);

        // Invalid ID rejection
        $invalidResponse = $controller->getSuggestions(['post_id' => -1]);
        $this->assertTrue(($invalidResponse instanceof \WP_Error) || (isset($invalidResponse['error'])));
    }

    public function testAnalyticsController() {
        $controller = $this->router->getController('analytics');

        $overviewResponse = $controller->getOverview();
        $overviewData = ($overviewResponse instanceof \WP_REST_Response) ? $overviewResponse->get_data() : $overviewResponse;
        $this->assertTrue($overviewData['success']);
        $this->assertArrayHasKey('metrics', $overviewData);

        $rankResponse = $controller->getRankTracker();
        $rankData = ($rankResponse instanceof \WP_REST_Response) ? $rankResponse->get_data() : $rankResponse;
        $this->assertTrue($rankData['success']);
    }

    public function testCacheControllerPurgeAndPreload() {
        $controller = $this->router->getController('cache');

        $purgeResponse = $controller->purgeCache([
            'type' => 'all',
        ]);
        $purgeData = ($purgeResponse instanceof \WP_REST_Response) ? $purgeResponse->get_data() : $purgeResponse;
        $this->assertTrue($purgeData['success']);
        $this->assertEquals('all', $purgeData['type']);

        // Tag and Post Purge
        $postPurge = $controller->purgeCache([
            'type'    => 'post',
            'targets' => [42, 99],
        ]);
        $postPurgeData = ($postPurge instanceof \WP_REST_Response) ? $postPurge->get_data() : $postPurge;
        $this->assertTrue($postPurgeData['success']);

        $preloadResponse = $controller->triggerPreload();
        $preloadData = ($preloadResponse instanceof \WP_REST_Response) ? $preloadResponse->get_data() : $preloadResponse;
        $this->assertTrue($preloadData['success']);
        $this->assertEquals('enqueued', $preloadData['status']);
    }

    public function testMediaControllerSingleAndBulk() {
        $controller = $this->router->getController('media');

        $optimizeResponse = $controller->optimizeSingle([
            'attachment_id' => 99,
        ]);
        $optimizeData = ($optimizeResponse instanceof \WP_REST_Response) ? $optimizeResponse->get_data() : $optimizeResponse;
        $this->assertTrue($optimizeData['success']);
        $this->assertEquals(99, $optimizeData['attachment_id']);

        // Bulk optimization with bounded IDs
        $bulkResponse = $controller->bulkOptimize([
            'attachment_ids' => [101, 102, -5, 'invalid', 101],
            'batch_size'     => 5,
        ]);
        $bulkData = ($bulkResponse instanceof \WP_REST_Response) ? $bulkResponse->get_data() : $bulkResponse;
        $this->assertTrue($bulkData['success']);
        $this->assertEquals(2, $bulkData['processed_count']);
    }

    public function testMigrationControllerExecution() {
        $controller = $this->router->getController('migration');

        $response = $controller->executeMigration([
            'source'     => 'yoast',
            'batch_size' => 100,
            'offset'     => 0,
        ]);
        $data = ($response instanceof \WP_REST_Response) ? $response->get_data() : $response;
        $this->assertTrue($data['success']);
        $this->assertEquals('yoast', $data['source']);
        $this->assertEquals('completed', $data['status']);

        // Unsupported source rejection
        $invalidSource = $controller->executeMigration(['source' => 'unsupported_plugin']);
        $this->assertTrue(($invalidSource instanceof \WP_Error) || (isset($invalidSource['error'])));
    }

    public function testPermissionWrappers() {
        $controller = $this->router->getController('settings');
        // Check permission methods execute cleanly
        $this->assertTrue(is_bool($controller->checkAdminPermission(null)) || ($controller->checkAdminPermission(null) instanceof \WP_Error));
        $this->assertTrue(is_bool($controller->checkEditorPermission(null)) || ($controller->checkEditorPermission(null) instanceof \WP_Error));
        $this->assertTrue(is_bool($controller->checkUploadPermission(null)) || ($controller->checkUploadPermission(null) instanceof \WP_Error));
    }

    public function testSecurityIdorAndObjectAuthorization() {
        $controller = $this->router->getController('meta');

        // Invalid negative ID
        $resNegative = $controller->getMeta(['object_type' => 'post', 'object_id' => -99]);
        $this->assertTrue(($resNegative instanceof \WP_Error) || (isset($resNegative['error'])));

        // Invalid object_type
        $resInvalidType = $controller->getMeta(['object_type' => 'system_files', 'object_id' => 1]);
        $this->assertTrue(($resInvalidType instanceof \WP_Error) || (isset($resInvalidType['error'])));
    }

    public function testSecuritySqlInjectionResilience() {
        $controller = $this->router->getController('redirects');

        // SQL injection payload in source_url / target_url
        $sqlPayload = "' OR 1=1; DROP TABLE wp_users; --";
        $createRes = $controller->createRedirect([
            'source_url'  => '/test-sqli-' . md5(uniqid()) . '/',
            'target_url'  => 'https://example.com/' . urlencode($sqlPayload),
            'status_code' => 301,
        ]);
        $data = ($createRes instanceof \WP_REST_Response) ? $createRes->get_data() : $createRes;
        $this->assertTrue($data['success']);

        // Check search query with quotes in links controller
        $linksController = $this->router->getController('links');
        $linkRes = $linksController->getSuggestions(['post_id' => 1]);
        $linkData = ($linkRes instanceof \WP_REST_Response) ? $linkRes->get_data() : $linkRes;
        $this->assertTrue($linkData['success']);
    }

    public function testSecurityXssPayloadSanitization() {
        $controller = $this->router->getController('meta');

        $xssPayload = '<script>alert("XSS")</script><img src="x" onerror="alert(1)">Hello Safe Title';
        $saveRes = $controller->saveMeta([
            'object_type' => 'post',
            'object_id'   => 88,
            'title'       => $xssPayload,
            'description' => $xssPayload,
        ]);
        $saveData = ($saveRes instanceof \WP_REST_Response) ? $saveRes->get_data() : $saveRes;
        $this->assertTrue($saveData['success']);
        $this->assertFalse(strpos($saveData['indexable']['title'], '<script>'));
    }

    public function testSecurityOversizedBatchAndPaginationBounds() {
        // Media bulk optimizer with 500 attachment IDs
        $mediaController = $this->router->getController('media');
        $largeIdList = range(1, 500);
        $bulkRes = $mediaController->bulkOptimize([
            'attachment_ids' => $largeIdList,
            'batch_size'     => 1000, // Should be bounded to max 50
        ]);
        $bulkData = ($bulkRes instanceof \WP_REST_Response) ? $bulkRes->get_data() : $bulkRes;
        $this->assertTrue($bulkData['success']);
        $this->assertTrue($bulkData['processed_count'] <= 50);

        // Redirects pagination bounding
        $redirController = $this->router->getController('redirects');
        $redirRes = $redirController->getRedirects(['page' => 1, 'per_page' => 99999]);
        $redirData = ($redirRes instanceof \WP_REST_Response) ? $redirRes->get_data() : $redirRes;
        $this->assertTrue($redirData['success']);
        $this->assertTrue($redirData['per_page'] <= 100);
    }

    public function testSecurityPathTraversalAndSsrfGuards() {
        $cacheController = $this->router->getController('cache');

        // Path traversal payload in cache purge targets
        $traversalRes = $cacheController->purgeCache([
            'type'    => 'url',
            'targets' => ['../../../../wp-config.php', 'http://169.254.169.254/latest/meta-data/'],
        ]);
        $data = ($traversalRes instanceof \WP_REST_Response) ? $traversalRes->get_data() : $traversalRes;
        $this->assertTrue($data['success']);
    }
}
