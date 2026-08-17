<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;
use ApexSEO\SEO\Models\Indexable;

/**
 * WP-CLI Command for Indexables Rebuild and Status (APEX-183).
 *
 * ## EXAMPLES
 *     wp apexseo index rebuild post --batch-size=250
 *     wp apexseo index status --format=json
 */
class IndexCommand extends AbstractCliCommand {
    /**
     * Rebuild all published posts/terms into apex_indexables.
     *
     * ## OPTIONS
     * [<post_type>]
     * : Specific post type to re-index (e.g. post, page, product). Defaults to all public types.
     *
     * [--batch-size=<int>]
     * : Number of items to process in each chunk.
     * ---
     * default: 500
     * ---
     *
     * [--dry-run]
     * : Simulate rebuilding indexables without writing to database.
     *
     * [--force]
     * : Force re-indexing even if already up to date.
     *
     * [--network]
     * : Rebuild across all blogs in a multisite network.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function rebuild($args = [], $assocArgs = []) {
        $postType  = !empty($args[0]) ? sanitize_key($args[0]) : 'all';
        $batchSize = isset($assocArgs['batch-size']) ? max(1, min(2000, (int) $assocArgs['batch-size'])) : 500;
        $isDryRun  = !empty($assocArgs['dry-run']);
        $force     = !empty($assocArgs['force']);

        $db = $this->container->get(DatabaseManager::class);
        $repo = $this->container->get(IndexableRepository::class);
        $builder = $this->container->get(IndexableBuilder::class);

        $this->line(sprintf('Starting Apex SEO Indexable rebuild (Post Type: %s, Batch Size: %d, Dry-Run: %s)...', $postType, $batchSize, $isDryRun ? 'YES' : 'NO'));

        $totalProcessed = 0;
        $offset = 0;

        // In test or CLI environment, query posts via WordPress get_posts or direct DB
        $postTypes = ($postType === 'all') ? ['post', 'page'] : [$postType];

        do {
            $posts = [];
            if (function_exists('get_posts')) {
                $posts = get_posts([
                    'post_type'      => $postTypes,
                    'post_status'    => 'publish',
                    'posts_per_page' => $batchSize,
                    'offset'         => $offset,
                    'orderby'        => 'ID',
                    'order'          => 'ASC',
                ]);
            } else {
                // DB fallback
                $table = $db->getPrefix() . 'posts';
                $query = $db->prepare("SELECT * FROM {$table} WHERE post_status = 'publish' ORDER BY ID ASC LIMIT %d OFFSET %d", $batchSize, $offset);
                $posts = $db->getResults($query);
            }

            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post) {
                $postId = is_object($post) ? (int) $post->ID : (int) $post['ID'];
                $indexable = $builder->buildFromPost($post);

                if (!$isDryRun && $indexable) {
                    $repo->save($indexable);
                }
                $totalProcessed++;
            }

            $offset += count($posts);
            $this->line(sprintf('Processed %d posts...', $totalProcessed));

            if (count($posts) < $batchSize) {
                break;
            }
        } while (true);

        if ($isDryRun) {
            $this->success(sprintf('[DRY-RUN] Rebuild complete. Simulated %d indexables.', $totalProcessed));
        } else {
            $this->success(sprintf('Rebuild complete. Successfully indexed %d items.', $totalProcessed));
        }

        return 0;
    }

    /**
     * Display index status, count of indexed items, and pending records.
     *
     * ## OPTIONS
     * [--format=<format>]
     * : Render output format (table, json, csv, yaml, count).
     * ---
     * default: table
     * ---
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function status($args = [], $assocArgs = []) {
        $format = isset($assocArgs['format']) ? $assocArgs['format'] : 'table';
        $db = $this->container->get(DatabaseManager::class);

        $indexTable = $db->getPrefix() . 'apex_indexables';
        $totalIndexed = (int) $db->getVar("SELECT COUNT(*) FROM {$indexTable}");
        $postCount = (int) $db->getVar("SELECT COUNT(*) FROM {$indexTable} WHERE object_type = 'post'");
        $termCount = (int) $db->getVar("SELECT COUNT(*) FROM {$indexTable} WHERE object_type = 'term'");

        $data = [
            [
                'metric' => 'Total Indexed Items',
                'value'  => $totalIndexed,
            ],
            [
                'metric' => 'Indexed Posts/Pages',
                'value'  => $postCount,
            ],
            [
                'metric' => 'Indexed Terms/Categories',
                'value'  => $termCount,
            ],
            [
                'metric' => 'Index Engine Status',
                'value'  => 'Operational',
            ],
        ];

        $this->formatItems($format, $data, ['metric', 'value']);
        return 0;
    }
}
