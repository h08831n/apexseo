<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real Database Integration Test.
 *
 * Verifies all 8 custom APEX SEO tables against live MySQL database.
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

    public function testAllEightApexTablesExistInDatabase() {
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
        ];

        foreach ($expectedTables as $table) {
            $found = $this->db->get_var("SHOW TABLES LIKE '{$table}';");
            $this->assertEquals($table, $found, "Table {$table} must exist in live database.");
        }

        $ninth = $this->db->get_var("SHOW TABLES LIKE 'wp_apex_content_analysis';");
        $this->assertNull($ninth, 'Table wp_apex_content_analysis must NOT exist in live database.');
    }

    public function testIndexablesTableSchemaAndIndexes() {
        if (!$this->db) {
            $this->markTestSkipped('Real WordPress database runtime not available.');
        }

        $columns = $this->db->get_results("DESCRIBE wp_apex_indexables;", ARRAY_A);
        $columnNames = array_column($columns, 'Field');

        $this->assertContains('id', $columnNames);
        $this->assertContains('object_id', $columnNames);
        $this->assertContains('object_type', $columnNames);
        $this->assertContains('primary_focus_keyword', $columnNames);
        $this->assertContains('keyword_density', $columnNames);
        $this->assertContains('readability_score', $columnNames);
        $this->assertContains('content_analysis', $columnNames);
        $this->assertContains('is_cornerstone', $columnNames);

        $indexes = $this->db->get_results("SHOW INDEX FROM wp_apex_indexables;", ARRAY_A);
        $keyNames = array_column($indexes, 'Key_name');
        $this->assertContains('PRIMARY', $keyNames);
        $this->assertContains('uk_object', $keyNames);
        $this->assertContains('idx_object_type_subtype', $keyNames);
    }

    public function testRedirectsTableSchemaAndIndexes() {
        if (!$this->db) {
            $this->markTestSkipped('Real WordPress database runtime not available.');
        }

        $columns = $this->db->get_results("DESCRIBE wp_apex_redirects;", ARRAY_A);
        $columnNames = array_column($columns, 'Field');

        $this->assertContains('id', $columnNames);
        $this->assertContains('source_path', $columnNames);
        $this->assertContains('target_url', $columnNames);
        $this->assertContains('status_code', $columnNames);
        $this->assertContains('match_type', $columnNames);
        $this->assertContains('hits', $columnNames);
        $this->assertContains('is_active', $columnNames);

        $indexes = $this->db->get_results("SHOW INDEX FROM wp_apex_redirects;", ARRAY_A);
        $keyNames = array_column($indexes, 'Key_name');
        $this->assertContains('PRIMARY', $keyNames);
        $this->assertContains('uk_source_path', $keyNames);
        $this->assertContains('idx_active', $keyNames);
    }

    public function testRealDatabaseCrudOperations() {
        if (!$this->db) {
            $this->markTestSkipped('Real WordPress database runtime not available.');
        }

        $testSource = '/test-ci-redirect-' . time();
        $testTarget = '/test-ci-target-' . time();

        // 1. INSERT using production schema
        $inserted = $this->db->insert('wp_apex_redirects', [
            'source_path' => $testSource,
            'target_url'  => $testTarget,
            'status_code' => 301,
            'match_type'  => 'exact',
            'hits'        => 0,
            'is_active'   => 1,
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
        $this->assertEquals($testSource, $row['source_path']);

        // 3. UPDATE hits
        $updated = $this->db->update(
            'wp_apex_redirects',
            ['hits' => 5],
            ['id' => $insertId]
        );
        $this->assertEquals(1, $updated);

        // 4. DELETE
        $deleted = $this->db->delete('wp_apex_redirects', ['id' => $insertId]);
        $this->assertEquals(1, $deleted);
    }
}
