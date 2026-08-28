<?php
namespace ApexSEO\Core\Database;

class SchemaVersion {
    const OPTION_NAME = 'apexseo_schema_version';

    public static function getInstalledVersion(): ?string {
        return get_option(self::OPTION_NAME, null);
    }

    public static function setInstalledVersion(string $version): void {
        update_option(self::OPTION_NAME, $version);
    }

    public static function removeInstalledVersion(): void {
        delete_option(self::OPTION_NAME);
    }
}
