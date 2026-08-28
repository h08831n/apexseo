<?php
namespace ApexSEO\Core\Database;

use ApexSEO\Core\Database\Migrations\Migration_1_0_0_CreateLockedTables;

class MigrationRunner {
    private $db;
    private $migrations = [];

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
        $this->registerMigration(new Migration_1_0_0_CreateLockedTables());
    }

    public function registerMigration(MigrationInterface $migration): void {
        $this->migrations[$migration->getVersion()] = $migration;
    }

    public function migrate(): array {
        $executed = [];
        $installed = SchemaVersion::getInstalledVersion();

        foreach ($this->migrations as $version => $migration) {
            if ($installed === null || version_compare($version, $installed, '>')) {
                $migration->up($this->db);
                SchemaVersion::setInstalledVersion($version);
                $executed[] = $version;
            }
        }

        return $executed;
    }

    public function rollback(string $targetVersion = '0.0.0'): array {
        $rolledBack = [];
        $installed = SchemaVersion::getInstalledVersion();

        $reversed = array_reverse($this->migrations, true);
        foreach ($reversed as $version => $migration) {
            if ($installed !== null && version_compare($version, $targetVersion, '>')) {
                $migration->down($this->db);
                $rolledBack[] = $version;
            }
        }

        if ($targetVersion === '0.0.0') {
            SchemaVersion::removeInstalledVersion();
        } else {
            SchemaVersion::setInstalledVersion($targetVersion);
        }

        return $rolledBack;
    }
}
