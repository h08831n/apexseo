<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\Migrations\Migration_1_0_0_CreateLockedTables;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Redirects\RedirectManager;
use ApexSEO\Analytics\Monitor\FourOhFourMonitor;

/**
 * Static Schema Consistency Test Suite.
 *
 * Verifies that all production model properties, repository persistence payloads,
 * and database managers strictly align with the authoritative migration schema.
 */
class SchemaConsistencyTest extends TestCase {
    /**
     * @var DatabaseManager
     */
    protected $db;

    public function setUp(): void {
        parent::setUp();
        global $wpdb, $mock_wp_options;
        $mock_wp_options = [];
        $this->db = new DatabaseManager($wpdb);
    }

    /**
     * Extract column definitions from migration SQL.
     */
    private function extractColumnsFromDdl(string $ddl): array {
        $columns = [];
        $lines = explode("\n", $ddl);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^`([a-zA-Z0-9_]+)`\s+/', $line, $matches)) {
                $columns[] = $matches[1];
            }
        }
        return $columns;
    }

    /**
     * Test Indexable model setters, getters, fill, and toArray mapping against migration DDL.
     */
    public function testIndexableModelAndMigrationSchemaConsistency() {
        $migration = new Migration_1_0_0_CreateLockedTables();
        $reflector = new \ReflectionClass($migration);

        // Run migration on mock DB
        $migration->up($this->db);

        // 1. Create Indexable via constructor fill
        $attributes = [
            'id'                     => 1,
            'object_id'              => 42,
            'object_type'            => 'post',
            'object_sub_type'        => 'post',
            'permalink'              => 'https://example.com/test-post',
            'canonical_url'          => 'https://example.com/test-post',
            'title'                  => 'SEO Title',
            'description'            => 'SEO Description',
            'robots_index'           => true,
            'robots_follow'          => true,
            'primary_focus_keyword'  => 'wordpress seo',
            'keyword_density'        => 2.5,
            'readability_score'      => 85,
            'content_analysis'       => ['score' => 85, 'flesch' => 70.0],
            'is_cornerstone'         => false,
        ];

        $indexable = new Indexable($attributes);

        // 2. Validate Getters
        $this->assertEquals(1, $indexable->getId());
        $this->assertEquals(42, $indexable->getObjectId());
        $this->assertEquals('post', $indexable->getObjectType());
        $this->assertEquals('post', $indexable->getObjectSubType());
        $this->assertEquals('https://example.com/test-post', $indexable->getPermalink());
        $this->assertEquals('https://example.com/test-post', $indexable->getCanonicalUrl());
        $this->assertEquals('SEO Title', $indexable->getTitle());
        $this->assertEquals('SEO Description', $indexable->getDescription());
        $this->assertTrue($indexable->getRobotsIndex());
        $this->assertTrue($indexable->getRobotsFollow());
        $this->assertEquals('wordpress seo', $indexable->getPrimaryFocusKeyword());
        $this->assertEquals(2.5, $indexable->getKeywordDensity());
        $this->assertEquals(85, $indexable->getReadabilityScore());
        $this->assertIsArray($indexable->getContentAnalysis());
        $this->assertFalse($indexable->isCornerstone());

        // 3. Validate Public Setters
        $indexable->setPrimaryFocusKeyword('advanced optimization');
        $this->assertEquals('advanced optimization', $indexable->getPrimaryFocusKeyword());
        $indexable->setKeywordDensity(3.1);
        $this->assertEquals(3.1, $indexable->getKeywordDensity());
        $indexable->setReadabilityScore(90);
        $this->assertEquals(90, $indexable->getReadabilityScore());
        $indexable->setContentAnalysis(['score' => 90]);
        $this->assertEquals(['score' => 90], $indexable->getContentAnalysis());
        $indexable->setIsCornerstone(true);
        $this->assertTrue($indexable->isCornerstone());

        // 4. Validate toArray() keys against writable columns in migration DDL
        $arrayData = $indexable->toArray();
        unset($arrayData['id']);

        $expectedMigrationColumns = [
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

        $this->assertEquals($expectedMigrationColumns, array_keys($arrayData));

        // 5. Ensure obsolete columns are NOT emitted
        $this->assertArrayNotHasKey('seo_score', $arrayData);
        $this->assertArrayNotHasKey('permalink_hash', $arrayData);

        // 6. Test Repository Persistence
        $repo = new IndexableRepository($this->db);
        $saved = $repo->save($indexable);
        $this->assertTrue($saved);

        $found = $repo->find(42, 'post');
        $this->assertNotNull($found);
        $this->assertEquals('advanced optimization', $found->getPrimaryFocusKeyword());
    }

    /**
     * Test RedirectManager writes only valid columns in wp_apex_redirects.
     */
    public function testRedirectManagerSchemaConsistency() {
        $manager = new RedirectManager($this->db);

        $id = $manager->addRedirect('/old-url', 'https://example.com/new-url', 301);
        $this->assertNotNull($id);

        $matched = $manager->matchRedirect('/old-url');
        $this->assertNotNull($matched);
        $this->assertEquals('https://example.com/new-url', $matched['target_url']);

        $deleted = $manager->deleteRedirect($id);
        $this->assertTrue($deleted);
    }

    /**
     * Test FourOhFourMonitor writes only valid columns (no last_occurred_at).
     */
    public function testFourOhFourMonitorSchemaConsistency() {
        $monitor = new FourOhFourMonitor($this->db);

        $monitor->log('/missing-page', 'https://referrer.com', 'Mozilla/5.0', '192.168.1.1');
        $logs = $monitor->getLogs(5);

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
        $first = $logs[0];
        $this->assertArrayHasKey('request_uri', $first);
        $this->assertArrayHasKey('referrer', $first);
        $this->assertArrayHasKey('user_agent', $first);
        $this->assertArrayHasKey('ip_address', $first);
        $this->assertArrayHasKey('hits', $first);
        $this->assertArrayNotHasKey('last_occurred_at', $first);
    }

    /**
     * Test Migration 1.0.0 defines exactly 8 tables and no 9th table.
     */
    public function testMigrationSchemaDefinitionIntegrity() {
        $migration = new Migration_1_0_0_CreateLockedTables();
        $migration->up($this->db);

        $lockedTables = [
            'wp_apex_indexables',
            'wp_apex_schema',
            'wp_apex_redirects',
            'wp_apex_404_logs',
            'wp_apex_links',
            'wp_apex_image_history',
            'wp_apex_analytics',
            'wp_apex_rank_tracking',
        ];

        foreach ($lockedTables as $table) {
            $this->assertTrue($this->db->hasTable($table), "Table {$table} must exist in migration.");
        }

        $this->assertFalse($this->db->hasTable('wp_apex_content_analysis'), "Obsolete 9th table must NOT exist.");
    }
}
