<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-049: Flesch Reading Ease & Grade Level Formula Scorer.
 *
 * Implements language-aware readability scoring. Computes standard Flesch Reading Ease
 * and Flesch-Kincaid Grade Level for English prose, and provides structural sentence/word
 * distribution metrics for Persian with explicit formula limitation metadata.
 */
class ReadabilityScorer {
    /**
     * Common English abbreviations to prevent erroneous sentence splitting.
     *
     * @var array
     */
    protected static $abbreviations = [
        'dr', 'mr', 'mrs', 'ms', 'prof', 'sr', 'jr', 'vs', 'etc', 'ie', 'eg',
        'inc', 'ltd', 'co', 'corp', 'dept', 'approx', 'est', 'min', 'max',
        'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec',
        'us', 'uk', 'usa', 'eu', 'un', 'dc', 'st', 'ave', 'rd', 'blvd'
    ];

    /**
     * Common English syllable exceptions and prefixes/suffixes.
     *
     * @var array
     */
    protected static $syllableExceptions = [
        'the' => 1, 'water' => 2, 'banana' => 3, 'intelligent' => 4, 'simple' => 2,
        'cat' => 1, 'dog' => 1, 'people' => 2, 'rhythm' => 2, 'every' => 2,
        'area' => 3, 'idea' => 3, 'real' => 1, 'poem' => 2, 'create' => 2,
        'business' => 2, 'different' => 3, 'family' => 3, 'history' => 3
    ];

    /**
     * Detect language of the content (en, fa, or other).
     *
     * @param string $text
     * @return string Language code ('en', 'fa', or 'unknown')
     */
    public function detectLanguage($text) {
        if (empty($text)) {
            return 'en';
        }

        // Count Persian/Arabic characters vs Latin characters
        $persianCount = preg_match_all('/[\x{0600}-\x{06FF}\x{FB8A}\x{067E}\x{0686}\x{06AF}]/u', $text);
        $latinCount = preg_match_all('/[a-zA-Z]/', $text);

        if ($persianCount > $latinCount) {
            return 'fa';
        }

        return 'en';
    }

    /**
     * Count syllables in a single English word with linguistic heuristics.
     *
     * @param string $word
     * @return int Syllable count (minimum 1)
     */
    public function countSyllables($word) {
        $clean = strtolower(trim(preg_replace('/[^a-zA-Z]/', '', $word)));

        if ($clean === '') {
            return 1;
        }

        // Check direct exception dictionary
        if (isset(self::$syllableExceptions[$clean])) {
            return self::$syllableExceptions[$clean];
        }

        if (strlen($clean) <= 3) {
            return 1;
        }

        // Remove non-vowel endings that do not add a syllable
        $word = preg_replace('/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $clean);
        $word = preg_replace('/^y/', '', $word);

        // Count vowel groups
        preg_match_all('/[aeiouy]{1,2}/', $word, $matches);
        $count = count($matches[0] ?? []);

        return max(1, $count);
    }

    /**
     * Split content into individual sentences with robust abbreviation and decimal handling.
     *
     * @param string $content HTML or plain text
     * @return array Array of sentence strings
     */
    public function splitSentences($content) {
        if (empty($content)) {
            return [];
        }

        // 1. Strip HTML tags, decode entities, replace breaks with punctuation boundaries
        $clean = preg_replace('/<(br|p|div|h[1-6]|li|blockquote)[^>]*>/i', ".\n", $content);
        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Protect decimals (e.g. 3.14, $3.50, 10.5)
        $clean = preg_replace('/(\d+)\.(\d+)/', '$1##DOT##$2', $clean);

        // 3. Protect common abbreviations
        foreach (self::$abbreviations as $abbr) {
            // Match abbreviation with dot case-insensitively with word boundary
            $clean = preg_replace('/\b(' . preg_quote($abbr, '/') . ')\./i', '$1##DOT##', $clean);
        }

        // 4. Protect single uppercase letters with dot (e.g., D.C., U.S., J.K.)
        $clean = preg_replace('/\b([A-Z])\./', '$1##DOT##', $clean);

        // 5. Split by sentence terminators (. ! ? and Persian equivalents)
        $rawSentences = preg_split('/(?<=[\.\!\?\x{061F}\x{06D4}])(?:\s+|\n+|$)|(?<=[a-zA-Z\x{0600}-\x{06FF}])(?=[\.\!\?]{1,3}[A-Z\x{0600}-\x{06FF}])/u', $clean);

        $sentences = [];
        foreach ($rawSentences as $sentence) {
            // Restore protected dots
            $sentence = str_replace('##DOT##', '.', $sentence);
            $trimmed = trim(preg_replace('/\s+/u', ' ', $sentence));
            if ($trimmed !== '' && preg_match('/[\p{L}\p{N}]/u', $trimmed)) {
                $sentences[] = $trimmed;
            }
        }

        return $sentences;
    }

    /**
     * Extract clean words from text.
     *
     * @param string $text
     * @return array Array of words
     */
    public function extractWords($text) {
        if (empty($text)) {
            return [];
        }

        $clean = strip_tags($text);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        preg_match_all('/[\p{L}\p{N}]+/u', $clean, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Interpret standard Flesch Reading Ease score into readable difficulty bands.
     *
     * @param float $score
     * @return array [label, grade_equivalent, description]
     */
    public function interpretFleschScore($score) {
        if ($score >= 90.0) {
            return [
                'label'            => 'Very Easy',
                'grade_equivalent' => '5th Grade',
                'description'      => 'Very easy to read. Easily understood by an average 11-year-old student.',
            ];
        } elseif ($score >= 80.0) {
            return [
                'label'            => 'Easy',
                'grade_equivalent' => '6th Grade',
                'description'      => 'Easy to read. Conversational English for a general audience.',
            ];
        } elseif ($score >= 70.0) {
            return [
                'label'            => 'Fairly Easy',
                'grade_equivalent' => '7th Grade',
                'description'      => 'Fairly easy to read. Suitable for standard consumer articles.',
            ];
        } elseif ($score >= 60.0) {
            return [
                'label'            => 'Standard',
                'grade_equivalent' => '8th & 9th Grade',
                'description'      => 'Plain English. Easily understood by 13- to 15-year-old students.',
            ];
        } elseif ($score >= 50.0) {
            return [
                'label'            => 'Fairly Difficult',
                'grade_equivalent' => '10th to 12th Grade',
                'description'      => 'Fairly difficult to read. Appropriate for high school and professional readers.',
            ];
        } elseif ($score >= 30.0) {
            return [
                'label'            => 'Difficult',
                'grade_equivalent' => 'College Level',
                'description'      => 'Difficult to read. Best understood by university students.',
            ];
        } else {
            return [
                'label'            => 'Very Confusing',
                'grade_equivalent' => 'College Graduate',
                'description'      => 'Very difficult to read. Best understood by subject-matter academic experts.',
            ];
        }
    }

    /**
     * Perform complete readability analysis with language-aware formula calibration.
     *
     * @param string $content
     * @return array Comprehensive readability report
     */
    public function score($content) {
        $sentences = $this->splitSentences($content);
        $words = $this->extractWords($content);

        $sentenceCount = count($sentences);
        $wordCount = count($words);

        $language = $this->detectLanguage($content);
        $isFleschSupported = ($language === 'en');

        if ($wordCount === 0 || $sentenceCount === 0) {
            return [
                'formula'                         => $isFleschSupported ? 'Flesch Reading Ease & Flesch-Kincaid' : null,
                'language'                        => $language,
                'is_flesch_supported'             => $isFleschSupported,
                'limitations'                     => $isFleschSupported ? 'Requires English prose.' : 'Flesch formulas are calibrated for English syllable patterns and are not mathematically valid for Persian.',
                'flesch_reading_ease'            => null,
                'flesch_kincaid_grade'           => null,
                'interpretation'                  => null,
                'words_count'                     => 0,
                'sentences_count'                 => 0,
                'syllables_count'                 => 0,
                'avg_words_per_sentence'          => 0.0,
                'avg_syllables_per_word'          => 0.0,
                'estimated_reading_time_seconds'  => 0,
            ];
        }

        $asl = $wordCount / $sentenceCount; // Average Sentence Length

        // Average reading speed: 200 words per minute (same baseline across Latin and Persian)
        $readingTimeSeconds = (int) ceil(($wordCount / 200) * 60);

        // Persian / non-English Handling: Do NOT return false English Flesch formulas
        if (!$isFleschSupported) {
            return [
                'formula'                         => null,
                'language'                        => $language,
                'is_flesch_supported'             => false,
                'limitations'                     => 'Flesch Reading Ease and Flesch-Kincaid Grade Level formulas are calibrated specifically for English phonetics and syllable structures, and are not mathematically valid for Persian.',
                'flesch_reading_ease'            => null,
                'flesch_kincaid_grade'           => null,
                'interpretation'                  => [
                    'label'            => 'Persian Content Structure',
                    'grade_equivalent' => 'N/A',
                    'description'      => sprintf('Persian prose with %d words across %d sentences (avg %.1f words/sentence).', $wordCount, $sentenceCount, $asl),
                ],
                'words_count'                     => $wordCount,
                'sentences_count'                 => $sentenceCount,
                'syllables_count'                 => 0,
                'avg_words_per_sentence'          => round($asl, 2),
                'avg_syllables_per_word'          => 0.0,
                'estimated_reading_time_seconds'  => $readingTimeSeconds,
            ];
        }

        // Count total syllables for English
        $totalSyllables = 0;
        foreach ($words as $word) {
            $totalSyllables += $this->countSyllables($word);
        }

        $asw = $totalSyllables / $wordCount; // Average Syllables per Word

        // Standard Flesch Reading Ease Formula: Score = 206.835 - (1.015 * ASL) - (84.6 * ASW)
        $fleschScore = round(206.835 - (1.015 * $asl) - (84.6 * $asw), 1);
        $fleschScore = max(0.0, min(100.0, $fleschScore));

        // Flesch-Kincaid Grade Level Formula: Grade = (0.39 * ASL) + (11.8 * ASW) - 15.59
        $gradeLevel = round((0.39 * $asl) + (11.8 * $asw) - 15.59, 1);
        $gradeLevel = max(0.0, $gradeLevel);

        $interpretation = $this->interpretFleschScore($fleschScore);

        return [
            'formula'                         => 'Flesch Reading Ease (206.835 - 1.015*ASL - 84.6*ASW) & Flesch-Kincaid (0.39*ASL + 11.8*ASW - 15.59)',
            'language'                        => 'en',
            'is_flesch_supported'             => true,
            'limitations'                     => 'Calibrated for standard English prose.',
            'flesch_reading_ease'            => $fleschScore,
            'flesch_kincaid_grade'           => $gradeLevel,
            'interpretation'                  => $interpretation,
            'words_count'                     => $wordCount,
            'sentences_count'                 => $sentenceCount,
            'syllables_count'                 => $totalSyllables,
            'avg_words_per_sentence'          => round($asl, 2),
            'avg_syllables_per_word'          => round($asw, 2),
            'estimated_reading_time_seconds'  => $readingTimeSeconds,
        ];
    }
}
