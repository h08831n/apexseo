<?php
namespace ApexSEO\SEO\Analysis;

class PassiveVoiceAnalyzer {
    private $scorer;

    public function __construct(?ReadabilityScorer $scorer = null) {
        $this->scorer = $scorer;
    }

    public function analyze(string $text): array {
        preg_match_all('/\b(is|are|was|were|been|being|be)\s+([a-z]+ed)\b/i', $text, $matches);
        $totalSentences = max(1, preg_match_all('/[.!?]+/', $text));
        $passiveCount = count($matches[0]);
        $percent = round(($passiveCount / $totalSentences) * 100, 2);

        return [
            'passive_count' => $passiveCount,
            'percentage'    => $percent,
            'is_acceptable' => $percent <= 15.0,
        ];
    }
}
