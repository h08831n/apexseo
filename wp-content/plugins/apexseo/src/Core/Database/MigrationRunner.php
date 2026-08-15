<?php
namespace ApexSEO\Core\Database;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Logging\LoggerInterface;
use ApexSEO\Core\Database\Migrations\Migration_1_0_0_CreateLockedTables;

/**
 * Deterministic Migration Runner for Apex SEO Platform.
 */
class MigrationRunner implements ServiceContractInterface {
    /**
     * Database manager instance.
     *
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Registered migration class list.
     *
     * @var MigrationInterface[]
     */
    protected $migrations = [];

    /**
     * Constructor.
     *
     * @param DatabaseManager $db
     * @param LoggerInterface|null $logger
     */
    public function __construct(DatabaseManager $db, $logger = null) {
        $this->db = $db;
        $this->logger = $logger;
        $this->registerDefaultMigrations();
    }

    /**
     * Register a migration instance.
     *
     * @param MigrationInterface $migration
     * @return self
     */
    public function registerMigration(MigrationInterface $migration) {
        $this->migrations[$migration->getVersion()] = $migration;
        uksort($this->migrations, 'version_compare');
        return $this;
    }

    /**
     * Run all pending migrations.
     *
     * @return array List of executed migration versions.
     */
    public function migrate() {
        $currentVersion = SchemaVersion::getInstalledVersion();
        $executed = [];

        foreach ($this->migrations as $version => $migration) {
            if (version_compare($currentVersion, $version, '<')) {
                if ($this->logger !== null) {
                    $this->logger->info(sprintf('Executing migration [%s]: %s', $version, $migration->getDescription()));
                }

                $success = $migration->up($this->db);
                if ($success) {
                    SchemaVersion::setInstalledVersion($version);
                    $executed[] = $version;
                } else {
                    if ($this->logger !== null) {
                        $this->logger->error(sprintf('Migration [%s] failed.', $version));
                    }
                    break;
                }
            }
        }

        return $executed;
    }

    /**
     * Rollback the latest or all migrations.
     *
     * @param string|null $targetVersion Rollback down to target version.
     * @return array List of rolled back versions.
     */
    public function rollback($targetVersion = '0.0.0') {
        $currentVersion = SchemaVersion::getInstalledVersion();
        $rolledBack = [];

        // Traverse in reverse order
        $reverseMigrations = array_reverse($this->migrations, true);

        foreach ($reverseMigrations as $version => $migration) {
            if (version_compare($currentVersion, $version, '>=') && version_compare($version, $targetVersion, '>')) {
                if ($this->logger !== null) {
                    $this->logger->info(sprintf('Rolling back migration [%s]', $version));
                }

                $success = $migration->down($this->db);
                if ($success) {
                    $rolledBack[] = $version;
                    SchemaVersion::setInstalledVersion($targetVersion);
                }
            }
        }

        return $rolledBack;
    }

    /**
     * Get list of all registered migration instances.
     *
     * @return MigrationInterface[]
     */
    public function getMigrations() {
        return $this->migrations;
    }

    /**
     * Register core default migrations.
     *
     * @return void
     */
    protected function registerDefaultMigrations() {
        $this->registerMigration(new Migration_1_0_0_CreateLockedTables());
    }
}
