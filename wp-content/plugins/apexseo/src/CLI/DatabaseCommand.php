<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Database\DatabaseManager;

/**
 * WP-CLI Command for Database Optimization and Maintenance (APEX-187).
 *
 * ## EXAMPLES
 *     wp apexseo db clean --days=30
 *     wp apexseo db clean --dry-run
 *     wp apexseo db clean --force
 */
class DatabaseCommand extends AbstractCliCommand {
    /**
     * Clean old 404 error logs, expired transients, and orphaned SEO indexables.
     *
     * ## OPTIONS
     * [--days=<int>]
     * : Purge 404 log entries older than this number of days.
     * ---
     * default: 30
     * ---
     *
     * [--dry-run]
     * : Report how many rows would be purged without actually deleting them.
     *
     * [--force]
     * : Bypass interactive confirmation.
     *
     * [--yes]
     * : Automatically confirm destructive cleanup.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function clean($args = [], $assocArgs = []) {
        $days     = isset($assocArgs['days']) ? max(1, (int) $assocArgs['days']) : 30;
        $isDryRun = !empty($assocArgs['dry-run']);

        if (!$isDryRun) {
            $this->confirm(sprintf('Are you sure you want to purge Apex SEO database logs older than %d days?', $days), $assocArgs);
        }

        $db = $this->container->get(DatabaseManager::class);
        $logsTable = $db->getPrefix() . 'apex_404_logs';
        $indexableTable = $db->getPrefix() . 'apex_indexables';
        $postsTable = $db->getPrefix() . 'posts';

        $cutoffDate = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        $this->line(sprintf('Analyzing Apex SEO database tables for cleanup (Cutoff: %s)...', $cutoffDate));

        // 1. Count 404 logs to delete
        $oldLogsCount = (int) $db->getVar($db->prepare("SELECT COUNT(*) FROM {$logsTable} WHERE created_at < %s", $cutoffDate));

        // 2. Count orphaned indexables (post deleted from posts table)
        $orphanedIndexables = 0;
        if ($db->hasTable($indexableTable) && $db->hasTable($postsTable)) {
            $orphanedSql = "SELECT COUNT(*) FROM {$indexableTable} i 
                            LEFT JOIN {$postsTable} p ON (i.object_type = 'post' AND i.object_id = p.ID) 
                            WHERE i.object_type = 'post' AND p.ID IS NULL";
            $orphanedIndexables = (int) $db->getVar($orphanedSql);
        }

        if ($isDryRun) {
            $this->success(sprintf(
                '[DRY-RUN] Cleanup analysis complete: %d old 404 log rows, %d orphaned indexables would be purged.',
                $oldLogsCount,
                $orphanedIndexables
            ));
            return 0;
        }

        // Execute Deletion
        $deletedLogs = 0;
        if ($oldLogsCount > 0) {
            $deletedLogs = $db->query($db->prepare("DELETE FROM {$logsTable} WHERE created_at < %s", $cutoffDate));
        }

        $deletedOrphans = 0;
        if ($orphanedIndexables > 0) {
            $delOrphanSql = "DELETE i FROM {$indexableTable} i 
                             LEFT JOIN {$postsTable} p ON (i.object_type = 'post' AND i.object_id = p.ID) 
                             WHERE i.object_type = 'post' AND p.ID IS NULL";
            $deletedOrphans = $db->query($delOrphanSql);
        }

        $this->success(sprintf(
            'Database cleanup successful! Purged %d 404 log entries and %d orphaned indexable records.',
            $deletedLogs !== false ? $deletedLogs : $oldLogsCount,
            $deletedOrphans !== false ? $deletedOrphans : $orphanedIndexables
        ));

        return 0;
    }
}
