<?php
namespace ApexSEO\Core\Database;

interface MigrationInterface {
    public function getVersion(): string;
    public function getDescription(): string;
    public function up(DatabaseManager $db): bool;
    public function down(DatabaseManager $db): bool;
}
