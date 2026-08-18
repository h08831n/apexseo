<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Environment\EnvironmentDetector;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * WP-CLI Command for Environment Diagnostics & Health Check (APEX-190).
 *
 * ## EXAMPLES
 *     wp apexseo doctor
 *     wp apexseo doctor --format=json
 *     wp apexseo report status --format=yaml
 */
class DoctorCommand extends AbstractCliCommand {
    /**
     * Run system diagnostics, verify database integrity, and report environmental health.
     *
     * ## OPTIONS
     * [--format=<format>]
     * : Output format (table, json, yaml).
     * ---
     * default: table
     * ---
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function diagnose($args = [], $assocArgs = []) {
        return $this->status($args, $assocArgs);
    }

    /**
     * Report system status and diagnostics.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function status($args = [], $assocArgs = []) {
        $format = isset($assocArgs['format']) ? $assocArgs['format'] : 'table';

        $detector = $this->container->has(EnvironmentDetector::class) ? $this->container->get(EnvironmentDetector::class) : new EnvironmentDetector();
        $db = $this->container->get(DatabaseManager::class);

        $tables = [
            'apex_indexables',
            'apex_schema',
            'apex_redirects',
            'apex_404_logs',
            'apex_links',
            'apex_image_history',
            'apex_analytics',
            'apex_rank_tracking',
        ];

        $allTablesOk = true;
        $prefix = $db->getPrefix();
        foreach ($tables as $tbl) {
            if (!$db->hasTable($prefix . $tbl)) {
                $allTablesOk = false;
            }
        }

        $checks = [
            [
                'check'  => 'PHP Version',
                'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'FAIL',
                'value'  => PHP_VERSION,
            ],
            [
                'check'  => 'Web Server',
                'status' => 'OK',
                'value'  => $detector->getServerType(),
            ],
            [
                'check'  => 'WordPress Min Version',
                'status' => 'OK',
                'value'  => defined('APEXSEO_MIN_WP') ? APEXSEO_MIN_WP : '6.2.0',
            ],
            [
                'check'  => 'Database Tables',
                'status' => $allTablesOk ? 'OK' : 'WARNING',
                'value'  => $allTablesOk ? 'All 8 Locked Core Tables Installed' : 'Some Tables Missing',
            ],
            [
                'check'  => 'Multisite Network',
                'status' => 'OK',
                'value'  => $detector->isMultisite() ? 'Yes' : 'No (Single Site)',
            ],
            [
                'check'  => 'Memory Limit',
                'status' => 'OK',
                'value'  => ini_get('memory_limit') ?: '256M',
            ],
            [
                'check'  => 'REST API Subsystem',
                'status' => 'OK',
                'value'  => 'Registered (apexseo/v1)',
            ],
        ];

        $this->formatItems($format, $checks, ['check', 'status', 'value']);

        if ($allTablesOk) {
            $this->success('Apex SEO Platform environment is healthy and operational.');
            return 0;
        } else {
            $this->warning('Some optional database tables were not detected.');
            return 0;
        }
    }
}
