<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-049: Flesch Reading Ease & Grade Level Formula Scorer.
 *
 * Computes exact Flesch Reading Ease, Flesch-Kincaid Grade Level, average sentence length,
 * average syllables per word, reading time, and language-aware readability classifications.
 */
class ReadabilityScorer {
    /**
     * Compute syllables in an English word using phonetic heuristic rules.
     *
     * @param string $word
     * @return int
     */
    public function countSyllables($word) {
        $word = mb_strtolower(trim($word), 'UTF-8');

        // Remove non-alphabetic characters
        $word = preg_replace('/[^a-z]/i', '', $word);
        $len = strlen($word);

        if ($len <= 3) {
            return $len > 0 ? 1 : 0;
        }

        // Subtractions for silent suffixes
        $subtractions = 0;
        if (substr($word, -1) === 'e' && substr($word, -2) !== 'le' && substr($word, -2) !== 'ee') {
            $subtractions++;
        }
        if (substr($word, -2) === 'ed' && !in_array(substr($word, -3, 1), ['t', 'd'])) {
            $subtractions++;
        }
        if (substr($word, -2) === 'es' && !in_array(substr($word, -3, 1), ['s', 'x', 'z', 'c', 'h'])) {
            $subtractions++;
        }

        // Count vowel groups / diphthongs
        preg_match_all('/[aeiouy]{1,2}/i', $word, $matches);
        $vowelCount = count($matches[0] ?? []);

        $syllables = $vowelCount - $subtractions;
        return max(1, $syllables);
    }

    /**
     * Split text into sentences with support for complex punctuation, abbreviations, and Persian/Arabic characters.
     *
     * @param string $text
     * @return array Array of sentence strings
     */
    public function splitSentences($text) {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        // Protect common English abbreviations (e.g., e.g., i.e., Dr., Mr., etc.)
        $protected = preg_replace_callback('/\b(Mr|Mrs|Ms|Dr|Prof|Sr|Jr|vs|etc|i\.e|e\.g)\./i', function($m) {
            return str_replace('.', '§DOT§', $m[0]);
        }, $text);

        // Protect decimal numbers (e.g. 3.14)
        $protected = preg_replace('/(\d+)\.(\d+)/', '$1§DOT§$2', $protected);

        // Split on terminal punctuation: . ! ? and Persian/Arabic equivalents (؛ ؟ ۔) and newlines
        $parts = preg_split('/(?<=[\.\!\?\؟\۔\n])\s+/u', $protected, -1, PREG_SPLIT_NO_EMPTY);

        $sentences = [];
        foreach ($parts as $part) {
            $restored = trim(str_replace('§DOT§', '.', $part));
            if ($restored !== '') {
                $sentences[] = $restored;
            }
        }

        return !empty($sentences) ? $sentences : [$text];
    }

    /**
     * Extract words from text.
     *
     * @param string $text
     * @return array
     */
    public function extractWords($text) {
        $clean = strip_tags($text);
        preg_match_all('/[\p{L}\p{N}]+/u', $clean, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Detect dominant language of the content.
     *
     * @param string $text
     * @return string 'en', 'fa', or 'other'
     */
    public function detectLanguage($text) {
        // Check for Persian/Arabic Unicode range (\x{0600}-\x{06FF})
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'fa';
        }
        return 'en';
    }

    /**
     * Interpret Flesch Reading Ease score into readable difficulty grade.
     *
     * @param float $score
     * @return array [label, grade_level, note]
     */
    public function interpretFleschScore($score) {
        if ($score >= 90.0) {
            return ['label' => 'Very Easy', 'grade' => '5th Grade', 'description' => 'Easily understood by an average 11-year-old student.'];
        } elseif ($score >= 80.0) {
            return ['label' => 'Easy', 'grade' => '6th Grade', 'description' => 'Conversational English for everyday readers.'];
        } elseif ($score >= 70.0) {
            return ['label' => 'Fairly Easy', 'grade' => '7th Grade', 'description' => 'Engaging and accessible to most general readers.'];
        } elseif ($score >= 60.0) {
            return ['label' => 'Standard', 'grade' => '8th-9th Grade', 'description' => 'Plain English, easily understood by 13- to 15-year-old students.'];
        } elseif ($score >= 50.0) {
            return ['label' => 'Fairly Difficult', 'grade' => '10th-12th Grade', 'description' => 'High school level reading comprehension.'];
        } elseif ($score >= 30.0) {
            return ['label' => 'Difficult', 'grade' => 'College', 'description' => 'Academic or professional audience.'];
        } else {
            return ['label' => 'Very Confusing', 'grade' => 'College Graduate', 'description' => 'Extremely complex, technical or academic language.'];
        }
    }

    /**
     * Score reading ease and grade level of given content.
     *
     * @param string $content
     * @return array
     */
    public function score($content) {
        $sentences = $this->splitSentences($content);
        $words = $this->extractWords($content);

        $sentenceCount = count($sentences);
        $wordCount = count($words);

        if ($wordCount === 0 || $sentenceCount === 0) {
            return [
                'flesch_reading_ease' => 0.0,
                'flesch_kincaid_grade' => 0.0,
                'interpretation'       => ['label' => 'No Content', 'grade' => 'N/A', 'description' => 'Content is empty.'],
                'words_count'          => 0,
                'sentences_count'      => 0,
                'syllables_count'      => 0,
                'avg_words_per_sentence' => 0.0,
                'avg_syllables_per_word' => 0.0,
                'estimated_reading_time_seconds' => 0,
                'language'             => 'en',
                'is_flesch_supported'  => true,
            ];
        }

        $language = $this->detectLanguage($content);
        $isFleschSupported = ($language === 'en');

        // Count total syllables
        $totalSyllables = 0;
        foreach ($words as $word) {
            $totalSyllables += $this->countSyllables($word);
        }

        $asl = $wordCount / $sentenceCount; // Average Sentence Length
        $asw = $totalSyllables / $wordCount; // Average Syllables per Word

        // Standard Flesch Reading Ease Formula
        // Score = 206.835 - (1.015 * ASL) - (84.6 * ASW)
        $fleschScore = round(206.835 - (1.015 * $asl) - (84.6 * $asw), 1);
        $fleschScore = max(0.0, min(100.0, $fleschScore));

        // Flesch-Kincaid Grade Level Formula
        // Grade = (0.39 * ASL) + (11.8 * ASW) - 15.59
        $gradeLevel = round((0.39 * $asl) + (11.8 * $asw) - 15.59, 1);
        $gradeLevel = max(0.0, $gradeLevel);

        $interpretation = $this->interpretFleschScore($fleschScore);

        // Average reading speed: 200 words per minute
        $readingTimeSeconds = (int) ceil(($wordCount / 200) * 60);

        return [
            'flesch_reading_ease'            => $fleschScore,
            'flesch_kincaid_grade'           => $gradeLevel,
            'interpretation'                  => $interpretation,
            'words_count'                     => $wordCount,
            'sentences_count'                 => $sentenceCount,
            'syllables_count'                 => $totalSyllables,
            'avg_words_per_sentence'          => round($asl, 2),
            'avg_syllables_per_word'          => round($asw, 2),
            'estimated_reading_time_seconds'  => $readingTimeSeconds,
            'language'                        => $language,
            'is_flesch_supported'             => $isFleschSupported,
        ];
    }
}
