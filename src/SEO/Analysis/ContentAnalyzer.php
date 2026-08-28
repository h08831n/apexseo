<?php
namespace ApexSEO\SEO\Analysis;

use ApexSEO\SEO\Repository\IndexableRepository;

class ContentAnalyzer {
    private $kw;
    private $readability;
    private $headings;
    private $links;
    private $passive;
    private $transition;
    private $structure;
    private $repo;

    public function __construct(
        KeywordAnalyzer $kw,
        ReadabilityScorer $readability,
        HeadingAnalyzer $headings,
        LinkGraphScanner $links,
        PassiveVoiceAnalyzer $passive,
        TransitionWordAnalyzer $transition,
        TextStructureAnalyzer $structure,
        IndexableRepository $repo
    ) {
        $this->kw = $kw;
        $this->readability = $readability;
        $this->headings = $headings;
        $this->links = $links;
        $this->passive = $passive;
        $this->transition = $transition;
        $this->structure = $structure;
        $this->repo = $repo;
    }

    public function analyze(string $content, string $keyword = ''): array {
        $kwRes = $this->kw->analyze($content, $keyword);
        $readScore = $this->readability->score($content);
        $headRes = $this->headings->analyze($content);
        $linkRes = $this->links->scanHtmlLinks($content);
        $passRes = $this->passive->analyze($content);
        $transRes = $this->transition->analyze($content);
        $structRes = $this->structure->analyze($content);

        return [
            'keyword_analysis'  => $kwRes,
            'readability_score' => $readScore,
            'headings'          => $headRes,
            'links_count'       => count($linkRes),
            'passive_voice'     => $passRes,
            'transition_words'  => $transRes,
            'text_structure'    => $structRes,
        ];
    }
}
