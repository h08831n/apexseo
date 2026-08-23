<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-054: Paragraph & Sentence Length Analysis.
 *
 * Measures word distributions across paragraphs and sentences, flagging oversized paragraphs
 * (>150 words) and overly complex sentences (>20 words) with actionable position references.
 */
class TextStructureAnalyzer {
    /**
     * Readability scorer instance.
     *
     * @var ReadabilityScorer
     */
    protected $readability;

    /**
     * Maximum recommended words per paragraph.
     *
     * @var int
     */
    protected $maxWordsPerParagraph;

    /**
     * Maximum recommended words per sentence.
     *
     * @var int
     */
    protected $maxWordsPerSentence;

    /**
     * Constructor.
     *
     * @param ReadabilityScorer|null $readability
     * @param int $maxWordsPerParagraph
     * @param int $maxWordsPerSentence
     */
    public function __construct(ReadabilityScorer $readability = null, $maxWordsPerParagraph = 150, $maxWordsPerSentence = 20) {
        $this->readability = $readability ?: new ReadabilityScorer();
        $this->maxWordsPerParagraph = $maxWordsPerParagraph;
        $this->maxWordsPerSentence = $maxWordsPerSentence;
    }

    /**
     * Split HTML/Text content into paragraphs.
     *
     * @param string $content
     * @return array Array of paragraph text blocks
     */
    public function extractParagraphs($content) {
        if (empty($content)) {
            return [];
        }

        // If content has <p> tags, extract them
        if (stripos($content, '<p') !== false) {
            preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
            if (!empty($matches[1])) {
                $paragraphs = [];
                foreach ($matches[1] as $p) {
                    $clean = trim(strip_tags(html_entity_decode($p, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                    if ($clean !== '') {
                        $paragraphs[] = $clean;
                    }
                }
                if (!empty($paragraphs)) {
                    return $paragraphs;
                }
            }
        }

        // Fallback: split by double newlines or breaks
        $clean = strip_tags($content, '<br>');
        $clean = preg_replace('/<br\s*\/?>/i', "\n", $clean);
        $parts = preg_split('/\n\s*\n+/', $clean);

        $paragraphs = [];
        foreach ($parts as $part) {
            $t = trim(html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($t !== '') {
                $paragraphs[] = $t;
            }
        }

        return $paragraphs;
    }

    /**
     * Analyze paragraph and sentence structures.
     *
     * @param string $content
     * @return array
     */
    public function analyze($content) {
        $paragraphs = $this->extractParagraphs($content);
        $sentences = $this->readability->splitSentences($content);

        $paragraphCount = count($paragraphs);
        $sentenceCount = count($sentences);

        if ($paragraphCount === 0 || $sentenceCount === 0) {
            return [
                'total_paragraphs'             => 0,
                'total_sentences'              => 0,
                'total_words'                  => 0,
                'avg_words_per_paragraph'      => 0.0,
                'avg_words_per_sentence'       => 0.0,
                'oversized_paragraphs'         => [],
                'oversized_sentences'          => [],
                'oversized_sentences_ratio'    => 0.0,
                'longest_sentence'             => null,
                'shortest_sentence'            => null,
                'diagnostics'                  => [],
                'is_acceptable'                => true,
            ];
        }

        // 1. Paragraph Analysis
        $paragraphWordCounts = [];
        $oversizedParagraphs = [];
        $totalWords = 0;

        foreach ($paragraphs as $index => $paragraph) {
            $words = $this->readability->extractWords($paragraph);
            $count = count($words);
            $totalWords += $count;
            $paragraphWordCounts[] = $count;

            if ($count > $this->maxWordsPerParagraph) {
                $oversizedParagraphs[] = [
                    'index'      => $index + 1,
                    'word_count' => $count,
                    'preview'    => mb_substr($paragraph, 0, 100, 'UTF-8') . '...',
                    'excess'     => $count - $this->maxWordsPerParagraph,
                ];
            }
        }

        $avgWordsPerParagraph = round($totalWords / $paragraphCount, 1);

        // 2. Sentence Analysis
        $sentenceWordCounts = [];
        $oversizedSentences = [];
        $longestSentence = null;
        $shortestSentence = null;
        $maxWordCount = -1;
        $minWordCount = 999999;

        foreach ($sentences as $index => $sentence) {
            $words = $this->readability->extractWords($sentence);
            $count = count($words);
            $sentenceWordCounts[] = $count;

            if ($count > $maxWordCount) {
                $maxWordCount = $count;
                $longestSentence = [
                    'index'      => $index + 1,
                    'word_count' => $count,
                    'text'       => $sentence,
                ];
            }

            if ($count > 0 && $count < $minWordCount) {
                $minWordCount = $count;
                $shortestSentence = [
                    'index'      => $index + 1,
                    'word_count' => $count,
                    'text'       => $sentence,
                ];
            }

            if ($count > $this->maxWordsPerSentence) {
                $oversizedSentences[] = [
                    'index'      => $index + 1,
                    'word_count' => $count,
                    'text'       => $sentence,
                    'excess'     => $count - $this->maxWordsPerSentence,
                ];
            }
        }

        $avgWordsPerSentence = round($totalWords / $sentenceCount, 1);
        $oversizedSentenceRatio = round((count($oversizedSentences) / $sentenceCount) * 100, 1);

        // 3. Diagnostics
        $diagnostics = [];

        if (!empty($oversizedParagraphs)) {
            $diagnostics[] = [
                'type'     => 'oversized_paragraphs',
                'severity' => 'warning',
                'message'  => sprintf('%d paragraph(s) exceed the %d-word limit. Shorten them to improve scannability.', count($oversizedParagraphs), $this->maxWordsPerParagraph),
                'count'    => count($oversizedParagraphs),
            ];
        }

        if ($oversizedSentenceRatio > 25.0) {
            $diagnostics[] = [
                'type'     => 'oversized_sentences',
                'severity' => 'error',
                'message'  => sprintf('%.1f%% of sentences exceed %d words (recommended <= 25%%). Break complex sentences down.', $oversizedSentenceRatio, $this->maxWordsPerSentence),
                'ratio'    => $oversizedSentenceRatio,
            ];
        } elseif ($oversizedSentenceRatio > 15.0) {
            $diagnostics[] = [
                'type'     => 'oversized_sentences',
                'severity' => 'warning',
                'message'  => sprintf('%.1f%% of sentences exceed %d words. Consider shortening them.', $oversizedSentenceRatio, $this->maxWordsPerSentence),
                'ratio'    => $oversizedSentenceRatio,
            ];
        } else {
            $diagnostics[] = [
                'type'     => 'sentence_length_optimal',
                'severity' => 'good',
                'message'  => sprintf('Sentence length is great: only %.1f%% of sentences exceed %d words.', $oversizedSentenceRatio, $this->maxWordsPerSentence),
                'ratio'    => $oversizedSentenceRatio,
            ];
        }

        $isAcceptable = (empty($oversizedParagraphs) && $oversizedSentenceRatio <= 25.0);

        return [
            'total_paragraphs'          => $paragraphCount,
            'total_sentences'           => $sentenceCount,
            'total_words'               => $totalWords,
            'avg_words_per_paragraph'   => $avgWordsPerParagraph,
            'avg_words_per_sentence'    => $avgWordsPerSentence,
            'oversized_paragraphs'      => $oversizedParagraphs,
            'oversized_sentences'       => $oversizedSentences,
            'oversized_sentences_ratio' => $oversizedSentenceRatio,
            'longest_sentence'          => $longestSentence,
            'shortest_sentence'         => $shortestSentence,
            'diagnostics'               => $diagnostics,
            'is_acceptable'             => $isAcceptable,
        ];
    }
}
