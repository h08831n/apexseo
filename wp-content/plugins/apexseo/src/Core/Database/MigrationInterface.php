<?php
namespace ApexSEO\Core\Database;

/**
 * Migration Contract for Apex SEO Platform.
 */
interface MigrationInterface {
    /**
     * Get semantic version string for this migration (e.g., '1.0.0').
     *
     * @return string
     */
    public function getVersion();

    /**
     * Get human-readable description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Execute forward migration schema changes.
     *
     * @param DatabaseManager $db
     * @return bool True on success.
     */
    public function up(DatabaseManager $db);

    /**
     * Execute rollback schema changes.
     *
     * @param DatabaseManager $db
     * @return bool True on success.
     */
    public function down(DatabaseManager $db);
}
