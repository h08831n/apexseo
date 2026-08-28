<?php
namespace ApexSEO\SEO\Analysis;

use ApexSEO\SEO\Repository\IndexableRepository;

class ContentAnalysisService {
    private $analyzer;
    private $repo;

    public function __construct(ContentAnalyzer $analyzer, IndexableRepository $repo) {
        $this->analyzer = $analyzer;
        $this->repo = $repo;
    }

    public function analyzeContent(int $postId, string $content, string $keyword = ''): array {
        $analysis = $this->analyzer->analyze($content, $keyword);

        $indexable = $this->repo->find($postId, 'post');
        if ($indexable) {
            $indexable->setReadabilityScore($analysis['readability_score']);
            $indexable->setKeywordDensity($analysis['keyword_analysis']['density'] ?? 0.0);
            $indexable->setPrimaryFocusKeyword($keyword);
            $indexable->setContentAnalysis($analysis);
            $this->repo->save($indexable);
        }

        return $analysis;
    }

    public function getAnalysis(int $postId): array {
        $indexable = $this->repo->find($postId, 'post');
        return $indexable ? $indexable->getContentAnalysis() : [];
    }
}
