<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\MigrationRunner;
use ApexSEO\Core\Database\SchemaVersion;

/**
 * Database Manager, DDL Creation, and Migration Runner Test.
 */
class DatabaseMigrationTest extends TestCase {
    /**
     * @var DatabaseManager
     */
    protected $db;

    public function setUp() {
        global $wpdb, $mock_wp_options;
        $mock_wp_options = [];
        $this->db = new DatabaseManager($wpdb);
    }

    public function testTablePrefixResolution() {
        $this->assertEquals('wp_apex_indexables', $this->db->getTableName(DatabaseManager::TABLE_INDEXABLES));
        $this->assertEquals('wp_apex_schema', $this->db->getTableName(DatabaseManager::TABLE_SCHEMA));
        $this->assertEquals('wp_apex_redirects', $this->db->getTableName(DatabaseManager::TABLE_REDIRECTS));
        $this->assertEquals('wp_apex_404_logs', $this->db->getTableName(DatabaseManager::TABLE_404_LOGS));
        $this->assertEquals('wp_apex_links', $this->db->getTableName(DatabaseManager::TABLE_LINKS));
        $this->assertEquals('wp_apex_image_history', $this->db->getTableName(DatabaseManager::TABLE_IMAGE_HISTORY));
        $this->assertEquals('wp_apex_analytics', $this->db->getTableName(DatabaseManager::TABLE_ANALYTICS));
        $this->assertEquals('wp_apex_rank_tracking', $this->db->getTableName(DatabaseManager::TABLE_RANK_TRACKING));
    }

    public function testCustomPrefixOverride() {
        $this->db->setPrefix('custom_');
        $this->assertEquals('custom_apex_indexables', $this->db->getTableName(DatabaseManager::TABLE_INDEXABLES));
        $this->db->setPrefix(null);
    }

    public function testMigrationExecutionCreates8LockedTables() {
        $runner = new MigrationRunner($this->db);
        $executed = $runner->migrate();

        $this->assertCount(1, $executed);
        $this->assertEquals('1.0.0', $executed[0]);
        $this->assertEquals('1.0.0', SchemaVersion::getInstalledVersion());

        // Verify all 8 locked tables exist
        $this->assertTrue($this->db->hasTable('wp_apex_indexables'));
        $this->assertTrue($this->db->hasTable('wp_apex_schema'));
        $this->assertTrue($this->db->hasTable('wp_apex_redirects'));
        $this->assertTrue($this->db->hasTable('wp_apex_404_logs'));
        $this->assertTrue($this->db->hasTable('wp_apex_links'));
        $this->assertTrue($this->db->hasTable('wp_apex_image_history'));
        $this->assertTrue($this->db->hasTable('wp_apex_analytics'));
        $this->assertTrue($this->db->hasTable('wp_apex_rank_tracking'));
    }

    public function testMigrationRollbackDropsTables() {
        $runner = new MigrationRunner($this->db);
        $runner->migrate();
        $this->assertTrue($this->db->hasTable('wp_apex_indexables'));

        $rolledBack = $runner->rollback('0.0.0');
        $this->assertCount(1, $rolledBack);
        $this->assertFalse($this->db->hasTable('wp_apex_indexables'));
    }
}
