<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\Core\CLI\CliManager;
use ApexSEO\CLI\IndexCommand;
use ApexSEO\CLI\CacheCommand;
use ApexSEO\CLI\MediaCommand;
use ApexSEO\CLI\RedirectCommand;
use ApexSEO\CLI\DatabaseCommand;
use ApexSEO\CLI\MigrateCommand;
use ApexSEO\CLI\SitemapCommand;
use ApexSEO\CLI\DoctorCommand;
use ApexSEO\CLI\SchemaCommand;

class CliSubsystemTest extends TestCase {
    protected $cliManager;
    protected $container;

    protected function setUp(): void {
        parent::setUp();
        Plugin::reset();
        $plugin = Plugin::getInstance();
        $this->container  = $plugin->getContainer();
        $this->cliManager = new CliManager();
    }

    public function testCliManagerCommandRegistration() {
        $commands = $this->cliManager->getCommands();
        $this->assertArrayHasKey('index', $commands);
        $this->assertArrayHasKey('cache', $commands);
        $this->assertArrayHasKey('media', $commands);
        $this->assertArrayHasKey('redirect', $commands);
        $this->assertArrayHasKey('db', $commands);
        $this->assertArrayHasKey('migrate', $commands);
        $this->assertArrayHasKey('sitemap', $commands);
        $this->assertArrayHasKey('doctor', $commands);
        $this->assertArrayHasKey('report', $commands);
        $this->assertArrayHasKey('schema', $commands);
        $this->assertEquals(10, count($commands));
    }

    public function testIndexCommandRebuildAndStatus() {
        $cmd = new IndexCommand($this->container);

        // Test Status
        $code = $cmd->status([], ['format' => 'json']);
        $this->assertEquals(0, $code);

        // Test Rebuild with dry-run
        $code = $cmd->rebuild(['post'], ['batch-size' => 10, 'dry-run' => true]);
        $this->assertEquals(0, $code);

        // Test Rebuild live
        $code = $cmd->rebuild(['post'], ['batch-size' => 10]);
        $this->assertEquals(0, $code);
    }

    public function testCacheCommandPurgeAndWarmup() {
        $cmd = new CacheCommand($this->container);

        // Test purge --all
        $code = $cmd->purge([], ['all' => true]);
        $this->assertEquals(0, $code);

        // Test purge specific URL
        $code = $cmd->purge(['https://example.com/test-article/'], []);
        $this->assertEquals(0, $code);

        // Test purge tag
        $code = $cmd->purge([], ['tag' => 'category_5']);
        $this->assertEquals(0, $code);

        // Test warmup
        $code = $cmd->warmup([], ['sitemap' => 'https://example.com/sitemap_index.xml', 'concurrency' => 3]);
        $this->assertEquals(0, $code);
    }

    public function testMediaCommandOptimizeAndRestore() {
        $cmd = new MediaCommand($this->container);

        // Single optimization with dry-run
        $code = $cmd->optimize([101], ['format' => 'webp', 'dry-run' => true]);
        $this->assertEquals(0, $code);

        // Single optimization live
        $code = $cmd->optimize([101], ['format' => 'webp']);
        $this->assertEquals(0, $code);

        // Bulk optimization
        $code = $cmd->optimize([], ['batch-size' => 20, 'format' => 'avif', 'dry-run' => true]);
        $this->assertEquals(0, $code);

        // Restore original
        $code = $cmd->restore([101], ['force' => true]);
        $this->assertEquals(0, $code);
    }

    public function testRedirectCommandAddAndList() {
        $cmd = new RedirectCommand($this->container);

        // Add 301 redirect
        $code = $cmd->add(['/old-cli-path/', '/new-cli-path/', '301'], []);
        $this->assertEquals(0, $code);

        // Prevent Loop
        $code = $cmd->add(['/same-path/', '/same-path/'], []);
        $this->assertEquals(1, $code);

        // List redirects
        $code = $cmd->list([], ['format' => 'json']);
        $this->assertEquals(0, $code);
    }

    public function testDatabaseCommandClean() {
        $cmd = new DatabaseCommand($this->container);

        // Clean dry-run
        $code = $cmd->clean([], ['days' => 15, 'dry-run' => true]);
        $this->assertEquals(0, $code);

        // Clean live with force
        $code = $cmd->clean([], ['days' => 15, 'force' => true, 'yes' => true]);
        $this->assertEquals(0, $code);
    }

    public function testMigrateCommandRunAndRollback() {
        $cmd = new MigrateCommand($this->container);

        // Run valid migration with dry-run
        $code = $cmd->run(['yoast'], ['batch-size' => 100, 'dry-run' => true]);
        $this->assertEquals(0, $code);

        // Run valid migration live
        $code = $cmd->run(['rankmath'], ['batch-size' => 50]);
        $this->assertEquals(0, $code);

        // Run invalid migration source error
        $code = $cmd->run(['invalid_unsupported_plugin'], []);
        $this->assertEquals(1, $code);

        // Rollback
        $code = $cmd->rollback(['yoast'], ['force' => true]);
        $this->assertEquals(0, $code);
    }

    public function testSitemapCommandRebuild() {
        $cmd = new SitemapCommand($this->container);

        $code = $cmd->rebuild([], ['format' => 'json']);
        $this->assertEquals(0, $code);
    }

    public function testDoctorCommandDiagnose() {
        $cmd = new DoctorCommand($this->container);

        $code = $cmd->diagnose([], ['format' => 'json']);
        $this->assertEquals(0, $code);

        $code = $cmd->status([], ['format' => 'table']);
        $this->assertEquals(0, $code);
    }

    public function testSchemaCommandValidate() {
        $cmd = new SchemaCommand($this->container);

        // Valid site default schema
        $code = $cmd->validate([], ['format' => 'json']);
        $this->assertEquals(0, $code);

        // Valid post schema
        $code = $cmd->validate([42], ['format' => 'json']);
        $this->assertEquals(0, $code);
    }
}
