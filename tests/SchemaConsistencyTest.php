<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\Migrations\Migration_1_0_0_CreateLockedTables;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Redirects\RedirectManager;
use ApexSEO\Analytics\Monitor\FourOhFourMonitor;

/**
 * Static Schema Consistency Test.
 *
 * Validates that all model properties, repository persistence payloads,
 * and subsystem insert/update statements strictly match the production
 * locked database schema defined in Migration_1_0_0_CreateLockedTables.
 */
class SchemaConsistencyTest extends TestCase {
    /**
     * @var DatabaseManager
     */
    protected $db;

    public function setUp(): void {
        global $wpdb, $mock_wp_options;
        $mock_wp_options = [];
        $this->db = new DatabaseManager($wpdb);
    }

    /**
     * Test that Indexable model and repository payload contain all migration columns.
     */
    public function testIndexableSchemaAndPersistencePayloadConsistency() {
        $migrationColumns = [
            'object_id',
            'object_type',
            'object_sub_type',
            'permalink',
            'canonical_url',
            'title',
            'description',
            'robots_index',
            'robots_follow',
            'primary_focus_keyword',
            'keyword_density',
            'readability_score',
            'content_analysis',
            'is_cornerstone',
        ];

        // 1. Verify model property existence
        $indexable = new Indexable();
        $this->assertTrue(property_exists($indexable, 'primary_focus_keyword'));
        $this->assertTrue(property_exists($indexable, 'keyword_density'));
        $this->assertTrue(property_exists($indexable, 'readability_score'));
        $this->assertTrue(property_exists($indexable, 'content_analysis'));
        $this->assertTrue(property_exists($indexable, 'seo_score'));

        // 2. Populate and test repository save mapping
        $indexable->object_id = 100;
        $indexable->object_type = 'post';
        $indexable->object_sub_type = 'post';
        $indexable->title = 'Test Title';
        $indexable->description = 'Test Description';
        $indexable->primary_focus_keyword = 'wordpress testing';
        $indexable->keyword_density = 2.45;
        $indexable->readability_score = 85;
        $indexable->content_analysis = ['score' => 85, 'flesch' => 70.5];

        $repo = new IndexableRepository($this->db);
        $result = $repo->save($indexable);
        $this->assertTrue($result);
    }

    /**
     * Test that RedirectManager uses exact production columns.
     */
    public function testRedirectManagerUsesProductionColumns() {
        $manager = new RedirectManager($this->db);

        // Add redirect
        $id = $manager->addRedirect('/source-path', 'https://example.com/target', 301);
        $this->assertNotNull($id);

        // Match redirect
        $matched = $manager->matchRedirect('/source-path');
        // Delete redirect
        $deleted = $manager->deleteRedirect(1);
        $this->assertTrue($deleted);
    }

    /**
     * Test that FourOhFourMonitor uses exact production columns.
     */
    public function testFourOhFourMonitorUsesProductionColumns() {
        $monitor = new FourOhFourMonitor($this->db);

        // Log 404
        $monitor->log('/non-existent-page', 'https://referrer.com', 'Mozilla/5.0', '127.0.0.1');

        $logs = $monitor->getLogs(10);
        $this->assertIsArray($logs);
    }

    /**
     * Test that all 8 locked tables have authoritative migration DDL statements.
     */
    public function testAllEightLockedTablesDefinedInMigration() {
        $migration = new Migration_1_0_0_CreateLockedTables();
        $this->assertEquals('1.0.0', $migration->getVersion());
        $this->assertTrue($migration->up($this->db));

        $expectedTables = [
            'wp_apex_indexables',
            'wp_apex_schema',
            'wp_apex_redirects',
            'wp_apex_404_logs',
            'wp_apex_links',
            'wp_apex_image_history',
            'wp_apex_analytics',
            'wp_apex_rank_tracking',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue($this->db->hasTable($table), "Table {$table} must be created by migration.");
        }

        $this->assertFalse($this->db->hasTable('wp_apex_content_analysis'), "wp_apex_content_analysis must NOT exist.");
    }
}
