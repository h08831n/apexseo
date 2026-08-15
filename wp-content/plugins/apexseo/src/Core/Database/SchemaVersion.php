<?php
namespace ApexSEO\Core\Database;

/**
 * Database Schema Version constants and state helpers.
 */
class SchemaVersion {
    const CURRENT_VERSION = '1.0.0';
    const OPTION_NAME     = 'apex_db_schema_version';

    /**
     * Get the currently installed schema version from options.
     *
     * @return string
     */
    public static function getInstalledVersion() {
        if (function_exists('get_option')) {
            return (string) get_option(self::OPTION_NAME, '0.0.0');
        }
        return '0.0.0';
    }

    /**
     * Set the recorded schema version in options.
     *
     * @param string $version
     * @return bool
     */
    public static function setInstalledVersion($version) {
        if (function_exists('update_option')) {
            return update_option(self::OPTION_NAME, (string) $version, false);
        }
        return true;
    }

    /**
     * Check if a database schema upgrade is pending.
     *
     * @return bool
     */
    public static function isUpgradeRequired() {
        return version_compare(self::getInstalledVersion(), self::CURRENT_VERSION, '<');
    }
}
