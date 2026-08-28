<?php
namespace ApexSEO\SEO\Analysis;

class TextStructureAnalyzer {
    public function analyze(string $text): array {
        $paragraphs = preg_split('/\n\s*\n/', trim($text));
        $longParagraphs = 0;
        foreach ($paragraphs as $p) {
            if (str_word_count($p) > 150) {
                $longParagraphs++;
            }
        }

        return [
            'paragraph_count'      => count($paragraphs),
            'long_paragraph_count' => $longParagraphs,
            'is_acceptable'        => $longParagraphs === 0,
        ];
    }
}
