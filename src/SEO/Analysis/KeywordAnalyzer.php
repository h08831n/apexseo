<?php
namespace ApexSEO\SEO\Analysis;

class KeywordAnalyzer {
    public function analyze(string $text, string $keyword): array {
        if (empty($keyword) || empty($text)) {
            return ['density' => 0.0, 'count' => 0, 'word_count' => str_word_count(strip_tags($text))];
        }
        $clean = strtolower(strip_tags($text));
        $kw = strtolower($keyword);
        $totalWords = max(1, str_word_count($clean));
        $occurrences = substr_count($clean, $kw);
        $density = round(($occurrences / $totalWords) * 100, 2);

        return [
            'keyword'    => $keyword,
            'count'      => $occurrences,
            'word_count' => $totalWords,
            'density'    => $density,
        ];
    }
}
