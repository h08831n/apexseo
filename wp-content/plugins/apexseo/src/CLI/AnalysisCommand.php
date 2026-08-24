<?php
namespace ApexSEO\CLI;

use ApexSEO\SEO\Analysis\ContentAnalysisService;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * WP-CLI Command for On-Page SEO & Content Intelligence Analysis (APEX-048 through APEX-054).
 *
 * ## EXAMPLES
 *     wp apexseo analysis post 123
 *     wp apexseo analysis post 123 --format=json
 *     wp apexseo analysis all --dry-run
 *     wp apexseo analysis reindex
 */
class AnalysisCommand extends AbstractCliCommand {
    /**
     * Analyze a single post by ID.
     *
     * ## OPTIONS
     * <id>
     * : The ID of the post to analyze.
     *
     * [--dry-run]
     * : Run analysis and output results without saving to database.
     *
     * [--force]
     * : Force re-analysis even if content hash is unchanged.
     *
     * [--format=<format>]
     * : Render output in a particular format (table, json, count).
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - count
     * ---
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function post($args = [], $assocArgs = []) {
        $postId = !empty($args[0]) ? (int) $args[0] : 0;
        if ($postId <= 0) {
            $this->error('Please specify a valid post ID.');
            return 1;
        }

        $isDryRun = !empty($assocArgs['dry-run']);
        $force = !empty($assocArgs['force']);
        $format = isset($assocArgs['format']) ? $assocArgs['format'] : 'table';

        $service = $this->container->get(ContentAnalysisService::class);
        $post = function_exists('get_post') ? get_post($postId) : null;

        if (!$post) {
            $this->error(sprintf('Post #%d not found.', $postId));
            return 1;
        }

        if ($isDryRun) {
            $content = $post->post_content ?? '';
            $primaryKeyword = function_exists('get_post_meta') ? get_post_meta($postId, '_apexseo_focus_keyword', true) : '';
            $analysis = $service->getContentAnalyzer()->analyzeContent($content, [
                'post_id'         => $postId,
                'primary_keyword' => $primaryKeyword,
            ]);
            $analysis['post_id'] = $postId;
            $analysis['dry_run'] = true;
        } else {
            $analysis = $service->analyzePost($postId, $force);
        }

        if (!$analysis) {
            $this->error(sprintf('Failed to analyze post #%d.', $postId));
            return 1;
        }

        if ($format === 'json') {
            $this->formatItems('json', $analysis, []);
            return 0;
        }

        $summary = [
            ['Metric' => 'Post ID', 'Value' => (string) $postId],
            ['Metric' => 'Post Title', 'Value' => (string) ($post->post_title ?? '')],
            ['Metric' => 'SEO Score', 'Value' => ($analysis['seo_score'] ?? 0) . '/100'],
            ['Metric' => 'Readability Score', 'Value' => ($analysis['readability_score'] ?? 0) . '/100'],
            ['Metric' => 'Word Count', 'Value' => (string) ($analysis['readability']['words_count'] ?? 0)],
            ['Metric' => 'Language', 'Value' => (string) ($analysis['readability']['language'] ?? 'en')],
            ['Metric' => 'Internal Links', 'Value' => (string) ($analysis['links']['internal_links'] ?? 0)],
            ['Metric' => 'External Links', 'Value' => (string) ($analysis['links']['external_links'] ?? 0)],
            ['Metric' => 'Passive Voice %', 'Value' => ($analysis['passive_voice']['passive_percentage'] ?? 0) . '%'],
            ['Metric' => 'Transition Words %', 'Value' => ($analysis['transition_words']['transition_percentage'] ?? 0) . '%'],
        ];

        $this->formatItems('table', $summary, ['Metric', 'Value']);
        $this->success(sprintf('Analysis completed for post #%d (SEO: %d, Readability: %d)', $postId, $analysis['seo_score'] ?? 0, $analysis['readability_score'] ?? 0));
        return 0;
    }

    /**
     * Analyze all published posts.
     *
     * ## OPTIONS
     * [--post-type=<type>]
     * : Specific post type (post, page, all).
     * ---
     * default: all
     * ---
     *
     * [--dry-run]
     * : Simulate analysis without writing to database.
     *
     * [--force]
     * : Force re-analysis of all posts.
     *
     * [--format=<format>]
     * : Output format (table, json, count).
     * ---
     * default: table
     * ---
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function all($args = [], $assocArgs = []) {
        $postType = isset($assocArgs['post-type']) ? sanitize_key($assocArgs['post-type']) : 'all';
        $isDryRun = !empty($assocArgs['dry-run']);
        $force = !empty($assocArgs['force']);
        $format = isset($assocArgs['format']) ? $assocArgs['format'] : 'table';

        $service = $this->container->get(ContentAnalysisService::class);
        $postTypes = ($postType === 'all') ? ['post', 'page'] : [$postType];

        $posts = [];
        if (function_exists('get_posts')) {
            $posts = get_posts([
                'post_type'      => $postTypes,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
            ]);
        }

        $this->line(sprintf('Starting batch content analysis for %d posts (Dry-Run: %s)...', count($posts), $isDryRun ? 'YES' : 'NO'));

        $results = [];
        $totalAnalyzed = 0;

        foreach ($posts as $post) {
            $postId = is_object($post) ? (int) $post->ID : (int) ($post['ID'] ?? 0);
            if ($postId <= 0) {
                continue;
            }

            if ($isDryRun) {
                $content = is_object($post) ? ($post->post_content ?? '') : ($post['post_content'] ?? '');
                $analysis = $service->getContentAnalyzer()->analyzeContent($content, ['post_id' => $postId]);
            } else {
                $analysis = $service->analyzePost($postId, $force);
            }

            if ($analysis) {
                $totalAnalyzed++;
                $results[] = [
                    'id'          => $postId,
                    'title'       => is_object($post) ? ($post->post_title ?? '') : ($post['post_title'] ?? ''),
                    'seo'         => $analysis['seo_score'] ?? 0,
                    'readability' => $analysis['readability_score'] ?? 0,
                    'words'       => $analysis['readability']['words_count'] ?? 0,
                ];
            }
        }

        if ($format === 'json') {
            $this->formatItems('json', $results, []);
        } else {
            $this->formatItems('table', $results, ['id', 'title', 'seo', 'readability', 'words']);
        }

        $this->success(sprintf('Successfully analyzed %d posts.', $totalAnalyzed));
        return 0;
    }

    /**
     * Reindex and update link graph and content analysis across the site.
     *
     * ## OPTIONS
     * [--dry-run]
     * : Simulate reindexing without database persistence.
     *
     * [--format=<format>]
     * : Output format (table, json).
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function reindex($args = [], $assocArgs = []) {
        $assocArgs['force'] = true;
        return $this->all($args, $assocArgs);
    }
}
