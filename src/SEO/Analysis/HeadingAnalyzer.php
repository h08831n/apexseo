<?php
namespace ApexSEO\SEO\Analysis;

class HeadingAnalyzer {
    public function analyze(string $html): array {
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $html, $h1);
        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/is', $html, $h2);
        preg_match_all('/<h3[^>]*>(.*?)<\/h3>/is', $html, $h3);

        return [
            'h1_count' => count($h1[0]),
            'h2_count' => count($h2[0]),
            'h3_count' => count($h3[0]),
            'has_h1'   => count($h1[0]) === 1,
        ];
    }
}
