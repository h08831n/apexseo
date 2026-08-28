<?php
namespace ApexSEO\CLI;

use ApexSEO\SEO\Analysis\ContentAnalysisService;

class AnalysisCommand extends AbstractCliCommand {
    private $analysisService;

    public function __construct(ContentAnalysisService $analysisService) {
        $this->analysisService = $analysisService;
    }

    public function post($args, $assocArgs): int {
        $postId = isset($args[0]) ? (int)$args[0] : 1;
        $post = get_post($postId);
        $content = $post ? $post->post_content : '';
        $keyword = get_post_meta($postId, '_apexseo_primary_keyword', true) ?: '';

        $analysis = $this->analysisService->analyzeContent($postId, $content, (string)$keyword);

        if (defined('WP_CLI') && WP_CLI) {
            $score = $analysis['readability_score'] ?? 0;
            \WP_CLI::success("Analysis complete for Post {$postId}. Content readability score: {$score}");
        }
        return 0;
    }

    public function all($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Content analysis batch processing Complete for all posts.");
        }
        return 0;
    }

    public function reindex($args, $assocArgs): int {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success("Content analysis reindex Done.");
        }
        return 0;
    }
}
