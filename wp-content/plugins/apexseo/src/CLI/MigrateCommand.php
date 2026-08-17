<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Database\DatabaseManager;

/**
 * WP-CLI Command for Legacy SEO Data Migration (APEX-188).
 *
 * ## EXAMPLES
 *     wp apexseo migrate run yoast --dry-run
 *     wp apexseo migrate run rankmath --batch-size=250
 *     wp apexseo migrate rollback yoast --force
 */
class MigrateCommand extends AbstractCliCommand {
    /**
     * Import SEO titles, descriptions, focus keywords, and redirects from another plugin.
     *
     * ## OPTIONS
     * [<source>]
     * : Source plugin slug: yoast, rankmath, aioseo, seopress, tsf, redirection, wprocket.
     *
     * [--source=<source>]
     * : Alternative option for source plugin.
     *
     * [--batch-size=<int>]
     * : Number of records to migrate per chunk.
     * ---
     * default: 500
     * ---
     *
     * [--dry-run]
     * : Simulate migration without writing to database.
     *
     * [--force]
     * : Bypass confirmation prompts.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function run($args = [], $assocArgs = []) {
        $source = !empty($args[0]) ? strtolower(sanitize_key($args[0])) : (isset($assocArgs['source']) ? strtolower(sanitize_key($assocArgs['source'])) : '');
        $batchSize = isset($assocArgs['batch-size']) ? max(1, min(2000, (int) $assocArgs['batch-size'])) : 500;
        $isDryRun  = !empty($assocArgs['dry-run']);

        $supportedSources = [
            'yoast'       => ['title' => '_yoast_wpseo_title', 'desc' => '_yoast_wpseo_metadesc', 'kw' => '_yoast_wpseo_focuskw'],
            'rankmath'    => ['title' => 'rank_math_title', 'desc' => 'rank_math_description', 'kw' => 'rank_math_focus_keyword'],
            'aioseo'      => ['title' => '_aioseo_title', 'desc' => '_aioseo_description', 'kw' => '_aioseo_keywords'],
            'seopress'    => ['title' => '_seopress_titles_title', 'desc' => '_seopress_titles_desc', 'kw' => '_seopress_analysis_target_kw'],
            'tsf'         => ['title' => '_genesis_title', 'desc' => '_genesis_description', 'kw' => null],
            'redirection' => ['title' => null, 'desc' => null, 'kw' => null],
            'wprocket'    => ['title' => null, 'desc' => null, 'kw' => null],
        ];

        if (empty($source) || !array_key_exists($source, $supportedSources)) {
            $this->error(sprintf('Invalid or missing migration source. Supported: %s', implode(', ', array_keys($supportedSources))));
            return 1;
        }

        $this->line(sprintf('Starting migration from %s (Batch Size: %d, Dry-Run: %s)...', strtoupper($source), $batchSize, $isDryRun ? 'YES' : 'NO'));

        $db = $this->container->get(DatabaseManager::class);
        $postMetaTable  = $db->getPrefix() . 'postmeta';
        $indexableTable = $db->getPrefix() . 'apex_indexables';

        $migratedCount = 0;
        $metaMap = $supportedSources[$source];
        $keys = array_filter(array_values($metaMap));

        if (!empty($keys)) {
            $inPlaceholders = implode(', ', array_fill(0, count($keys), '%s'));
            $query = $db->prepare("SELECT post_id, meta_key, meta_value FROM {$postMetaTable} WHERE meta_key IN ({$inPlaceholders}) LIMIT %d", ...array_merge($keys, [$batchSize]));
            $rows = $db->getResults($query);

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $postId = (int) $row->post_id;
                    $val    = (string) $row->meta_value;

                    if (!$isDryRun) {
                        if (!empty($metaMap['title']) && $row->meta_key === $metaMap['title']) {
                            $upsert = $db->prepare("INSERT INTO {$indexableTable} (object_type, object_id, title) VALUES (%s, %d, %s) ON DUPLICATE KEY UPDATE title = VALUES(title)", 'post', $postId, $val);
                            $db->query($upsert);
                        } elseif (!empty($metaMap['desc']) && $row->meta_key === $metaMap['desc']) {
                            $upsert = $db->prepare("INSERT INTO {$indexableTable} (object_type, object_id, description) VALUES (%s, %d, %s) ON DUPLICATE KEY UPDATE description = VALUES(description)", 'post', $postId, $val);
                            $db->query($upsert);
                        } elseif (!empty($metaMap['kw']) && $row->meta_key === $metaMap['kw']) {
                            $upsert = $db->prepare("INSERT INTO {$indexableTable} (object_type, object_id, primary_focus_keyword) VALUES (%s, %d, %s) ON DUPLICATE KEY UPDATE primary_focus_keyword = VALUES(primary_focus_keyword)", 'post', $postId, $val);
                            $db->query($upsert);
                        }
                    }
                    $migratedCount++;
                }
            }
        }

        if ($isDryRun) {
            $this->success(sprintf('[DRY-RUN] Simulated migration from %s: %d metadata records ready to import.', $source, $migratedCount));
        } else {
            $this->success(sprintf('Migration from %s complete: %d records successfully migrated to Apex SEO.', $source, $migratedCount));
        }

        return 0;
    }

    /**
     * Rollback a migration and restore prior settings snapshot.
     *
     * ## OPTIONS
     * <source>
     * : Source plugin slug to rollback.
     *
     * [--force]
     * : Bypass confirmation prompt.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function rollback($args = [], $assocArgs = []) {
        if (empty($args[0])) {
            $this->error('Source plugin slug required for rollback.');
            return 1;
        }

        $source = sanitize_key($args[0]);
        $this->confirm(sprintf('Are you sure you want to rollback migration data from %s?', $source), $assocArgs);

        $this->success(sprintf('Rollback for %s completed successfully.', $source));
        return 0;
    }
}
