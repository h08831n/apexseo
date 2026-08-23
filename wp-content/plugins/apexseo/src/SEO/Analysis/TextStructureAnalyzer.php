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
    protected $maxWordsPerParagraph = 150;

    /**
     * Maximum recommended words per sentence.
     *
     * @var int
     */
    protected $maxWordsPerSentence = 20;

    /**
     * Maximum acceptable ratio of oversized sentences (percentage).
     *
     * @var float
     */
    protected $maxOversizedSentenceRatio = 25.0;

    /**
     * Constructor.
     *
     * @param ReadabilityScorer|null $readability
     * @param int $maxWordsPerParagraph
     * @param int $maxWordsPerSentence
     * @param float $maxOversizedSentenceRatio
     */
    public function __construct(ReadabilityScorer $readability = null, $maxWordsPerParagraph = 150, $maxWordsPerSentence = 20, $maxOversizedSentenceRatio = 25.0) {
        $this->readability = $readability ?: new ReadabilityScorer();
        $this->maxWordsPerParagraph = max(10, (int) $maxWordsPerParagraph);
        $this->maxWordsPerSentence = max(5, (int) $maxWordsPerSentence);
        $this->maxOversizedSentenceRatio = max(5.0, (float) $maxOversizedSentenceRatio);
    }

    /**
     * Set max words per paragraph.
     *
     * @param int $words
     * @return self
     */
    public function setMaxWordsPerParagraph($words) {
        $this->maxWordsPerParagraph = max(10, (int) $words);
        return $this;
    }

    /**
     * Get max words per paragraph.
     *
     * @return int
     */
    public function getMaxWordsPerParagraph() {
        return $this->maxWordsPerParagraph;
    }

    /**
     * Set max words per sentence.
     *
     * @param int $words
     * @return self
     */
    public function setMaxWordsPerSentence($words) {
        $this->maxWordsPerSentence = max(5, (int) $words);
        return $this;
    }

    /**
     * Get max words per sentence.
     *
     * @return int
     */
    public function getMaxWordsPerSentence() {
        return $this->maxWordsPerSentence;
    }

    /**
     * Set max oversized sentence ratio percentage.
     *
     * @param float $ratio
     * @return self
     */
    public function setMaxOversizedSentenceRatio($ratio) {
        $this->maxOversizedSentenceRatio = max(5.0, (float) $ratio);
        return $this;
    }

    /**
     * Get max oversized sentence ratio percentage.
     *
     * @return float
     */
    public function getMaxOversizedSentenceRatio() {
        return $this->maxOversizedSentenceRatio;
    }

    /**
     * Split HTML/Text content into logical paragraphs.
     *
     * @param string $content
     * @return array Array of paragraph text blocks
     */
    public function extractParagraphs($content) {
        if (empty($content) || !is_string($content)) {
            return [];
        }

        // If content has <p> or <blockquote> tags, extract them
        if (preg_match('/<(p|blockquote)[^>]*>/i', $content)) {
            preg_match_all('/<(p|blockquote)[^>]*>(.*?)<\/\1>/is', $content, $matches);
            if (!empty($matches[2])) {
                $paragraphs = [];
                foreach ($matches[2] as $p) {
                    $clean = trim(strip_tags(html_entity_decode($p, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                    $clean = preg_replace('/\s+/u', ' ', $clean);
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
        $parts = preg_split('/\n\s*\n+/u', $clean);

        $paragraphs = [];
        foreach ($parts as $part) {
            $t = trim(html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $t = preg_replace('/\s+/u', ' ', $t);
            if ($t !== '' && preg_match('/[\p{L}\p{N}]/u', $t)) {
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
                'max_words_per_paragraph_limit'=> $this->maxWordsPerParagraph,
                'max_words_per_sentence_limit' => $this->maxWordsPerSentence,
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

            if ($count < $minWordCount && $count > 0) {
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

        $avgWordsPerSentence = ($sentenceCount > 0) ? round($totalWords / $sentenceCount, 1) : 0.0;
        $oversizedRatio = ($sentenceCount > 0) ? round((count($oversizedSentences) / $sentenceCount) * 100, 1) : 0.0;

        $diagnostics = [];

        if (!empty($oversizedParagraphs)) {
            $diagnostics[] = [
                'type'     => 'oversized_paragraph',
                'severity' => 'warning',
                'message'  => sprintf('%d paragraph(s) contain more than %d words. Consider breaking them into shorter sections.', count($oversizedParagraphs), $this->maxWordsPerParagraph),
            ];
        }

        if ($oversizedRatio > $this->maxOversizedSentenceRatio) {
            $diagnostics[] = [
                'type'     => 'oversized_sentences',
                'severity' => 'warning',
                'message'  => sprintf('%.1f%% of sentences contain more than %d words (recommended limit: %.1f%%). Shorten lengthy sentences to improve scannability.', $oversizedRatio, $this->maxWordsPerSentence, $this->maxOversizedSentenceRatio),
            ];
        }

        $isAcceptable = (empty($oversizedParagraphs) && $oversizedRatio <= $this->maxOversizedSentenceRatio);

        return [
            'total_paragraphs'             => $paragraphCount,
            'total_sentences'              => $sentenceCount,
            'total_words'                  => $totalWords,
            'avg_words_per_paragraph'      => $avgWordsPerParagraph,
            'avg_words_per_sentence'       => $avgWordsPerSentence,
            'max_words_per_paragraph_limit'=> $this->maxWordsPerParagraph,
            'max_words_per_sentence_limit' => $this->maxWordsPerSentence,
            'oversized_paragraphs'         => $oversizedParagraphs,
            'oversized_sentences'          => $oversizedSentences,
            'oversized_sentences_ratio'    => $oversizedRatio,
            'longest_sentence'             => $longestSentence,
            'shortest_sentence'            => $shortestSentence,
            'diagnostics'                  => $diagnostics,
            'is_acceptable'                => $isAcceptable,
        ];
    }
}
