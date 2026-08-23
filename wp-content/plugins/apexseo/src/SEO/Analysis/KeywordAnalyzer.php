<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-048: Multi-Keyword Density & TF-IDF Content Analyzer.
 *
 * Implements tokenization, Unicode/Persian/Arabic normalization, stop-word removal,
 * n-gram frequency extraction, keyword density calculation, Term Frequency (TF),
 * Inverse Document Frequency (IDF) with corpus support, and over-optimization detection.
 */
class KeywordAnalyzer {
    /**
     * Common English stop words.
     *
     * @var array
     */
    protected static $englishStopWords = [
        'a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and',
        'any', 'are', 'aren\'t', 'as', 'at', 'be', 'because', 'been', 'before', 'being',
        'below', 'between', 'both', 'but', 'by', 'can\'t', 'cannot', 'could', 'couldn\'t',
        'did', 'didn\'t', 'do', 'does', 'doesn\'t', 'doing', 'don\'t', 'down', 'during',
        'each', 'few', 'for', 'from', 'further', 'had', 'hadn\'t', 'has', 'hasn\'t',
        'have', 'haven\'t', 'having', 'he', 'he\'d', 'he\'ll', 'he\'s', 'her', 'here',
        'here\'s', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'how\'s', 'i',
        'i\'d', 'i\'ll', 'i\'m', 'i\'ve', 'if', 'in', 'into', 'is', 'isn\'t', 'it',
        'it\'s', 'its', 'itself', 'let\'s', 'me', 'more', 'most', 'mustn\'t', 'my',
        'myself', 'no', 'nor', 'not', 'of', 'off', 'on', 'once', 'only', 'or', 'other',
        'ought', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'same', 'shan\'t',
        'she', 'she\'d', 'she\'ll', 'she\'s', 'should', 'shouldn\'t', 'so', 'some',
        'such', 'than', 'that', 'that\'s', 'the', 'their', 'theirs', 'them', 'themselves',
        'then', 'there', 'there\'s', 'these', 'they', 'they\'d', 'they\'ll', 'they\'re',
        'they\'ve', 'this', 'those', 'through', 'to', 'too', 'under', 'until', 'up',
        'very', 'was', 'wasn\'t', 'we', 'we\'d', 'we\'ll', 'we\'re', 'we\'ve', 'were',
        'weren\'t', 'what', 'what\'s', 'when', 'when\'s', 'where', 'where\'s', 'which',
        'while', 'who', 'who\'s', 'whom', 'why', 'why\'s', 'with', 'won\'t', 'would',
        'wouldn\'t', 'you', 'you\'d', 'you\'ll', 'you\'re', 'you\'ve', 'your', 'yours',
        'yourself', 'yourselves'
    ];

    /**
     * Common Persian stop words.
     *
     * @var array
     */
    protected static $persianStopWords = [
        'از', 'به', 'در', 'با', 'که', 'و', 'را', 'برای', 'این', 'آن', 'است', 'شد', 'شده',
        'بود', 'باشد', 'یک', 'تا', 'بر', 'نیز', 'هم', 'اما', 'اگر', 'چون', 'پس', 'یا',
        'همان', 'همه', 'هر', 'دیگر', 'بین', 'روی', 'زیر', 'قبل', 'بعد', 'خود', 'ما', 'شما',
        'آنها', 'ایشان', 'او', 'من', 'تو', 'داشت', 'داشته', 'دارد', 'کنند', 'کند', 'کردن',
        'کرده', 'کرد', 'می', 'نمی', 'خواهند', 'خواهد', 'باید', 'تواند', 'توانند', 'چرا',
        'کدام', 'چه', 'چگونه', 'هست', 'هستند', 'نیست', 'نیستند', 'بوده', 'شوند', 'شود'
    ];

    /**
     * Optional corpus document count (N) for IDF calculation.
     *
     * @var int
     */
    protected $corpusDocCount = 0;

    /**
     * Optional corpus term document frequencies (df) for IDF calculation.
     *
     * @var array
     */
    protected $corpusDocFreqs = [];

    /**
     * Set external corpus statistics for true IDF calculation.
     *
     * @param int $totalDocs
     * @param array $docFrequencies Map of [term => doc_frequency]
     * @return self
     */
    public function setCorpusStatistics($totalDocs, array $docFrequencies = []) {
        $this->corpusDocCount = max(0, (int) $totalDocs);
        $this->corpusDocFreqs = $docFrequencies;
        return $this;
    }

    /**
     * Normalize text handling Persian/Arabic character variants, diacritics, and case.
     *
     * @param string $text
     * @return string
     */
    public function normalizeText($text) {
        if ($text === null || $text === '') {
            return '';
        }

        // 1. Strip HTML tags and entities
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Normalize Persian/Arabic character variants
        $arabicChars = [
            'ي', 'ك', 'ى', 'ة', 'ۀ', 'ؤ', 'إ', 'أ', 'آ', 'ٱ',
            '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', // Arabic digits
            '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'  // Persian digits
        ];
        $persianChars = [
            'ی', 'ک', 'ی', 'ه', 'ه', 'و', 'ا', 'ا', 'ا', 'ا',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
        ];
        $text = str_replace($arabicChars, $persianChars, $text);

        // 3. Remove Arabic/Persian diacritics (Tashkeel, Tanwin, etc.)
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);

        // 4. Normalize Zero-Width Non-Joiner (ZWNJ) and non-breaking spaces to standard space
        $text = preg_replace('/[\x{200C}\x{200B}\x{00A0}\x{FEFF}]/u', ' ', $text);

        // 5. Lowercase UTF-8 string
        $text = mb_strtolower($text, 'UTF-8');

        return trim($text);
    }

    /**
     * Extract token list from content.
     *
     * @param string $content
     * @param bool $removeStopWords
     * @return array Array of normalized string tokens
     */
    public function tokenize($content, $removeStopWords = false) {
        $normalized = $this->normalizeText($content);
        if ($normalized === '') {
            return [];
        }

        // Match all alphanumeric sequences including Unicode words (Latin, Persian, Arabic, Cyrillic, CJK, etc.)
        preg_match_all('/[\p{L}\p{N}]+/u', $normalized, $matches);
        $tokens = $matches[0] ?? [];

        if (!$removeStopWords) {
            return $tokens;
        }

        $stopWordsLookup = array_flip(array_merge(self::$englishStopWords, self::$persianStopWords));
        $filtered = [];
        foreach ($tokens as $token) {
            if (!isset($stopWordsLookup[$token]) && mb_strlen($token, 'UTF-8') > 1) {
                $filtered[] = $token;
            }
        }

        return $filtered;
    }

    /**
     * Count exact occurrences of a term/phrase in content (Unicode & word-boundary aware).
     *
     * @param string $term
     * @param string $content
     * @return int
     */
    public function countTermOccurrences($term, $content) {
        $normalizedTerm = $this->normalizeText($term);
        $normalizedContent = $this->normalizeText($content);

        if ($normalizedTerm === '' || $normalizedContent === '') {
            return 0;
        }

        // Quote term for regex safely
        $pattern = preg_quote($normalizedTerm, '/');
        // Word boundary matching with Unicode awareness
        $regex = '/(?<=^|[^\p{L}\p{N}])' . $pattern . '(?=$|[^\p{L}\p{N}])/u';

        $count = preg_match_all($regex, $normalizedContent, $matches);
        return $count !== false ? $count : 0;
    }

    /**
     * Calculate keyword density for a specific keyword in content.
     *
     * Density Formula: (Term Count * Word Count in Term / Total Content Words) * 100
     *
     * @param string $keyword
     * @param string $content
     * @return array Analysis metrics [count, word_count, total_words, density, is_over_optimized]
     */
    public function analyzeKeywordDensity($keyword, $content) {
        $allTokens = $this->tokenize($content, false);
        $totalWords = count($allTokens);

        if ($totalWords === 0 || empty($keyword)) {
            return [
                'keyword'           => $keyword,
                'count'             => 0,
                'term_word_count'   => 0,
                'total_words'       => $totalWords,
                'density'           => 0.0,
                'is_over_optimized' => false,
                'warning'           => $totalWords === 0 ? 'Content is empty.' : 'Keyword is empty.'
            ];
        }

        $keywordTokens = $this->tokenize($keyword, false);
        $termWordCount = count($keywordTokens);
        $occurrences = $this->countTermOccurrences($keyword, $content);

        // Density percentage calculation
        $density = ($termWordCount > 0 && $totalWords > 0)
            ? round(($occurrences * $termWordCount / $totalWords) * 100, 2)
            : 0.0;

        $isOverOptimized = $density > 3.5;
        $warning = null;

        if ($occurrences === 0) {
            $warning = sprintf('Keyword "%s" was not found in the content.', $keyword);
        } elseif ($density < 0.5) {
            $warning = sprintf('Keyword density (%.2f%%) is relatively low. Recommended is between 0.8%% and 2.5%%.', $density);
        } elseif ($isOverOptimized) {
            $warning = sprintf('Keyword density (%.2f%%) exceeds safe threshold (3.5%%), which may be flagged as keyword stuffing.', $density);
        }

        return [
            'keyword'           => $keyword,
            'count'             => $occurrences,
            'term_word_count'   => $termWordCount,
            'total_words'       => $totalWords,
            'density'           => $density,
            'is_over_optimized' => $isOverOptimized,
            'warning'           => $warning
        ];
    }

    /**
     * Compute Term Frequency (TF) for a term in the document.
     *
     * Standard TF = count(term, doc) / total_tokens_in_doc
     *
     * @param string $term
     * @param array $tokens Document token list
     * @return float
     */
    public function calculateTermFrequency($term, array $tokens) {
        $totalTokens = count($tokens);
        if ($totalTokens === 0) {
            return 0.0;
        }

        $normalizedTerm = $this->normalizeText($term);
        $count = 0;
        foreach ($tokens as $token) {
            if ($token === $normalizedTerm) {
                $count++;
            }
        }

        return $count / $totalTokens;
    }

    /**
     * Compute Inverse Document Frequency (IDF).
     *
     * Standard formula: log( (1 + N) / (1 + df) ) + 1
     * Fallback single document formula: log(1 + 1) = ~0.693
     *
     * @param string $term
     * @return float
     */
    public function calculateInverseDocumentFrequency($term) {
        $normalizedTerm = $this->normalizeText($term);

        if ($this->corpusDocCount > 0) {
            $df = isset($this->corpusDocFreqs[$normalizedTerm]) ? (int) $this->corpusDocFreqs[$normalizedTerm] : 0;
            return log((1 + $this->corpusDocCount) / (1 + $df)) + 1.0;
        }

        // Fallback default IDF when no external corpus is set
        return 1.0;
    }

    /**
     * Extract top N most relevant TF-IDF terms from content.
     *
     * @param string $content
     * @param int $topN
     * @return array Array of term metrics sorted by TF-IDF descending
     */
    public function extractTopTfIdfTerms($content, $topN = 10) {
        $tokens = $this->tokenize($content, true);
        $totalTokens = count($tokens);

        if ($totalTokens === 0) {
            return [];
        }

        // Count frequencies of filtered tokens
        $frequencies = array_count_values($tokens);
        $results = [];

        foreach ($frequencies as $term => $count) {
            $tf = $count / $totalTokens;
            $idf = $this->calculateInverseDocumentFrequency($term);
            $tfidf = round($tf * $idf, 5);

            $results[] = [
                'term'      => $term,
                'count'     => $count,
                'tf'        => round($tf, 4),
                'idf'       => round($idf, 4),
                'tfidf'     => $tfidf,
            ];
        }

        // Sort descending by TF-IDF score, then by raw count
        usort($results, function($a, $b) {
            if ($a['tfidf'] == $b['tfidf']) {
                return $b['count'] <=> $a['count'];
            }
            return ($a['tfidf'] < $b['tfidf']) ? 1 : -1;
        });

        return array_slice($results, 0, $topN);
    }

    /**
     * Perform comprehensive Multi-Keyword & TF-IDF analysis.
     *
     * @param string $content
     * @param string|null $primaryKeyword
     * @param array $secondaryKeywords
     * @return array Full analysis payload
     */
    public function analyze($content, $primaryKeyword = null, array $secondaryKeywords = []) {
        $allTokens = $this->tokenize($content, false);
        $totalWords = count($allTokens);

        $primaryAnalysis = null;
        if (!empty($primaryKeyword)) {
            $primaryAnalysis = $this->analyzeKeywordDensity($primaryKeyword, $content);
        }

        $secondaryAnalysis = [];
        foreach ($secondaryKeywords as $secondary) {
            if (!empty($secondary)) {
                $secondaryAnalysis[] = $this->analyzeKeywordDensity($secondary, $content);
            }
        }

        $topTerms = $this->extractTopTfIdfTerms($content, 10);

        return [
            'total_words'         => $totalWords,
            'primary_keyword'     => $primaryAnalysis,
            'secondary_keywords'   => $secondaryAnalysis,
            'top_tfidf_terms'     => $topTerms,
            'has_over_optimization' => ($primaryAnalysis && $primaryAnalysis['is_over_optimized']),
        ];
    }
}
