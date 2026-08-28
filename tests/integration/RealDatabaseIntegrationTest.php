<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real Database Integration Test.
 *
 * Verifies all 9 custom APEX SEO tables against live MySQL database.
 */
class RealDatabaseIntegrationTest extends TestCase {
    /**
     * @var \wpdb
     */
    protected $db;

    protected function setUp(): void {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function testAllNineApexTablesExistInDatabase() {
        if (!$this->db) {
            $this->markTestSkipped('Real WordPress database runtime not available.');
        }

        $expectedTables = [
            'wp_apex_indexables',
            'wp_apex_schema',
            'wp_apex_redirects',
            'wp_apex_404_logs',
            'wp_apex_links',
            'wp_apex_image_history',
            'wp_apex_analytics',
            'wp_apex_rank_tracking',
            'wp_apex_content_analysis',
        ];

        foreach ($expectedTables as $table) {
            $found = $this->db->get_var("SHOW TABLES LIKE '{$table}';");
            $this->assertEquals($table, $found, "Table {$table} must exist in live database.");
        }
    }

    public function testIndexablesTableSchemaAndIndexes() {
        if (!$this->db) {
            $this->markTestSkipped('Real WordPress database runtime not available.');
        }

        $columns = $this->db->get_results("DESCRIBE wp_apex_indexables;", ARRAY_A);
        $columnNames = array_column($columns, 'Field');

        $this->assertContains('id', $columnNames);
        $this->assertContains('object_id', $columnNames);
        $this->assertContains('primary_focus_keyword', $columnNames);
        $this->assertContains('seo_score', $columnNames);
        $this->assertContains('readability_score', $columnNames);
        $this->assertContains('is_cornerstone', $columnNames);

        $indexes = $this->db->get_results("SHOW INDEX FROM wp_apex_indexables;", ARRAY_A);
        $keyNames = array_column($indexes, 'Key_name');
        $this->assertContains('PRIMARY', $keyNames);
        $this->assertContains('uk_object_lookup', $keyNames);
    }

    public function testContentAnalysisTableSchema() {
        if (!$this->db) {
            $this->markTestSkipped('Real WordPress database runtime not available.');
        }

        $columns = $this->db->get_results("DESCRIBE wp_apex_content_analysis;", ARRAY_A);
        $columnNames = array_column($columns, 'Field');

        $this->assertContains('composite_score', $columnNames);
        $this->assertContains('keyword_metrics', $columnNames);
        $this->assertContains('heading_metrics', $columnNames);
        $this->assertContains('passive_voice_metrics', $columnNames);
        $this->assertContains('transition_metrics', $columnNames);
    }

    public function testRealDatabaseCrudOperations() {
        if (!$this->db) {
            $this->markTestSkipped('Real WordPress database runtime not available.');
        }

        $testSource = '/test-ci-redirect-' . time();
        $testTarget = '/test-ci-target-' . time();

        // 1. INSERT
        $inserted = $this->db->insert('wp_apex_redirects', [
            'source_url'      => $testSource,
            'source_url_hash' => md5($testSource),
            'target_url'      => $testTarget,
            'status_code'     => 301,
            'match_type'      => 'exact',
            'is_regex'        => 0,
            'hits_count'      => 0,
            'status'          => 'active',
        ]);
        $this->assertGreaterThan(0, $inserted, 'Insert into wp_apex_redirects must succeed.');
        $insertId = $this->db->insert_id;

        // 2. SELECT
        $row = $this->db->get_row($this->db->prepare(
            "SELECT * FROM wp_apex_redirects WHERE id = %d",
            $insertId
        ), ARRAY_A);
        $this->assertNotNull($row);
        $this->assertEquals($testTarget, $row['target_url']);

        // 3. UPDATE
        $updated = $this->db->update(
            'wp_apex_redirects',
            ['hits_count' => 5],
            ['id' => $insertId]
        );
        $this->assertEquals(1, $updated);

        // 4. DELETE
        $deleted = $this->db->delete('wp_apex_redirects', ['id' => $insertId]);
        $this->assertEquals(1, $deleted);
    }
}
