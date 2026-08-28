<?php
namespace ApexSEO\SEO\Analysis;

class TransitionWordAnalyzer {
    const TRANSITIONS = ['however', 'therefore', 'furthermore', 'moreover', 'in addition', 'consequently', 'as a result', 'meanwhile', 'further'];

    public function analyze(string $text): array {
        $clean = strtolower(strip_tags($text));
        $found = 0;
        foreach (self::TRANSITIONS as $t) {
            $found += substr_count($clean, $t);
        }
        $sentences = max(1, preg_match_all('/[.!?]+/', $text));
        $percent = round(($found / $sentences) * 100, 2);

        return [
            'transition_count' => $found,
            'percentage'       => $percent,
            'is_acceptable'    => $percent >= 25.0,
        ];
    }
}
