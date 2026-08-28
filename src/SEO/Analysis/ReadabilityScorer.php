<?php
namespace ApexSEO\SEO\Analysis;

class ReadabilityScorer {
    public function score(string $text): int {
        $clean = strip_tags($text);
        $words = max(1, str_word_count($clean));
        $sentences = max(1, preg_match_all('/[.!?]+/', $clean));
        $syllables = max(1, (int)($words * 1.4));

        // Flesch Reading Ease Formula
        $score = 206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words));
        return (int) max(0, min(100, round($score)));
    }
}
