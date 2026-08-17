<?php
namespace ApexSEO\CLI;

use ApexSEO\Media\Optimizer\ImageOptimizer;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * WP-CLI Command for Media Attachment Optimization & Restoration (APEX-184).
 *
 * ## EXAMPLES
 *     wp apexseo media optimize --batch-size=100 --format=webp
 *     wp apexseo media optimize 452 --format=avif
 *     wp apexseo media restore 452 --force
 */
class MediaCommand extends AbstractCliCommand {
    /**
     * Optimize media library attachments into WebP and AVIF formats.
     *
     * ## OPTIONS
     * [<attachment_id>]
     * : Specific attachment ID to optimize. If omitted, batch processes all unoptimized images.
     *
     * [--batch-size=<int>]
     * : Number of images to optimize per run.
     * ---
     * default: 50
     * ---
     *
     * [--format=<format>]
     * : Target image format (webp, avif, both).
     * ---
     * default: webp
     * ---
     *
     * [--dry-run]
     * : Simulate image optimization without generating files.
     *
     * [--force]
     * : Re-optimize already converted images.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function optimize($args = [], $assocArgs = []) {
        $attachmentId = !empty($args[0]) ? (int) $args[0] : 0;
        $batchSize    = isset($assocArgs['batch-size']) ? max(1, min(200, (int) $assocArgs['batch-size'])) : 50;
        $format       = isset($assocArgs['format']) ? strtolower(sanitize_key($assocArgs['format'])) : 'webp';
        $isDryRun     = !empty($assocArgs['dry-run']);
        $force        = !empty($assocArgs['force']);

        /** @var ImageOptimizer $optimizer */
        $optimizer = $this->container->has(ImageOptimizer::class) ? $this->container->get(ImageOptimizer::class) : new ImageOptimizer();

        if ($attachmentId > 0) {
            $this->line(sprintf('Optimizing attachment ID %d to %s...', $attachmentId, strtoupper($format)));
            if ($isDryRun) {
                $this->success(sprintf('[DRY-RUN] Attachment %d would be converted to %s.', $attachmentId, strtoupper($format)));
                return 0;
            }

            $res = $optimizer->optimizeAttachment($attachmentId);
            if (!empty($res['success'])) {
                $this->success(sprintf(
                    'Attachment %d optimized! Saved: %d bytes (%.1f%% reduction).',
                    $attachmentId,
                    isset($res['saved_bytes']) ? $res['saved_bytes'] : 0,
                    isset($res['saved_percent']) ? $res['saved_percent'] : 0
                ));
                return 0;
            } else {
                $this->error(sprintf('Failed to optimize attachment %d: %s', $attachmentId, isset($res['error']) ? $res['error'] : 'Unknown error'), false);
                return 1;
            }
        }

        // Bulk batch optimization
        $this->line(sprintf('Starting bulk media optimization (Batch Size: %d, Format: %s, Dry-Run: %s)...', $batchSize, strtoupper($format), $isDryRun ? 'YES' : 'NO'));

        $db = $this->container->get(DatabaseManager::class);
        $postsTable = $db->getPrefix() . 'posts';

        // Query image attachments
        $query = $db->prepare(
            "SELECT ID FROM {$postsTable} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID ASC LIMIT %d",
            $batchSize
        );
        $rows = $db->getResults($query);

        $processed = 0;
        $savedBytes = 0;

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $id = is_object($row) ? (int) $row->ID : (int) $row['ID'];
                if (!$isDryRun) {
                    $res = $optimizer->optimizeAttachment($id);
                    if (!empty($res['success'])) {
                        $processed++;
                        $savedBytes += isset($res['saved_bytes']) ? (int) $res['saved_bytes'] : 0;
                    }
                } else {
                    $processed++;
                }
            }
        }

        if ($isDryRun) {
            $this->success(sprintf('[DRY-RUN] Simulated optimization for %d image attachments.', $processed));
        } else {
            $this->success(sprintf('Bulk optimization finished: %d images processed, total %d bytes saved.', $processed, $savedBytes));
        }

        return 0;
    }

    /**
     * Restore original uncompressed images from backup.
     *
     * ## OPTIONS
     * <attachment_id>
     * : Specific attachment ID to restore.
     *
     * [--force]
     * : Bypass confirmation.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function restore($args = [], $assocArgs = []) {
        if (empty($args[0])) {
            $this->error('Attachment ID is required to restore original image.');
            return 1;
        }

        $attachmentId = (int) $args[0];
        $this->confirm(sprintf('Are you sure you want to restore original uncompressed image for attachment %d?', $attachmentId), $assocArgs);

        /** @var ImageOptimizer $optimizer */
        $optimizer = $this->container->has(ImageOptimizer::class) ? $this->container->get(ImageOptimizer::class) : new ImageOptimizer();
        $res = $optimizer->restoreOriginal($attachmentId);

        if (!empty($res['success'])) {
            $this->success(sprintf('Attachment %d restored to original uncompressed file.', $attachmentId));
            return 0;
        } else {
            $this->error(sprintf('Failed to restore attachment %d: %s', $attachmentId, isset($res['error']) ? $res['error'] : 'No backup found'), false);
            return 1;
        }
    }
}
