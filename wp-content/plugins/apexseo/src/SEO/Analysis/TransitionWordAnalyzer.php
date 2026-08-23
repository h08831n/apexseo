<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-053: Transition Word Coverage Analyzer.
 *
 * Measures the presence and category distribution of transition words and connective phrases
 * across sentences in English and Persian content to assess content readability and flow.
 */
class TransitionWordAnalyzer {
    /**
     * English transition phrases categorized by function.
     *
     * @var array
     */
    protected static $englishTransitions = [
        'addition' => [
            'furthermore', 'moreover', 'in addition', 'additionally', 'also',
            'besides', 'along with', 'as well as', 'not only', 'on top of that',
            'what is more', 'coupled with', 'together with'
        ],
        'contrast' => [
            'however', 'nevertheless', 'nonetheless', 'on the other hand',
            'in contrast', 'conversely', 'although', 'even though', 'though',
            'whereas', 'while', 'despite', 'in spite of', 'yet', 'but',
            'alternatively', 'on the contrary'
        ],
        'cause_effect' => [
            'therefore', 'consequently', 'as a result', 'thus', 'hence',
            'accordingly', 'for this reason', 'because of this', 'due to',
            'since', 'because', 'so that'
        ],
        'sequence_time' => [
            'first', 'firstly', 'second', 'secondly', 'third', 'thirdly',
            'subsequently', 'meanwhile', 'afterwards', 'finally', 'eventually',
            'in the meantime', 'next', 'then', 'initially'
        ],
        'emphasis_example' => [
            'for example', 'for instance', 'in fact', 'indeed', 'notably',
            'specifically', 'particularly', 'especially', 'in particular',
            'namely', 'to illustrate', 'above all', 'significantly'
        ],
        'conclusion' => [
            'in conclusion', 'to summarize', 'in summary', 'overall',
            'ultimately', 'to sum up', 'all in all', 'in brief'
        ]
    ];

    /**
     * Persian transition phrases categorized by function.
     *
     * @var array
     */
    protected static $persianTransitions = [
        'addition' => [
            'علاوه بر این', 'به علاوه', 'همچنین', 'افزون بر این', 'همینطور',
            'نیز', 'ضمنا', 'گذشته از این', 'در کنار این'
        ],
        'contrast' => [
            'با این حال', 'با وجود این', 'اما', 'ولی', 'از سوی دیگر',
            'از طرف دیگر', 'در حالی که', 'برعکس', 'در مقابل', 'گرچه',
            'اگرچه', 'با این وجود', 'هرچند'
        ],
        'cause_effect' => [
            'بنابراین', 'در نتیجه', 'از این رو', 'به همین دلیل', 'بدین ترتیب',
            'به علت', 'به خاطر این که', 'از آنجا که', 'چرا که'
        ],
        'sequence_time' => [
            'نخست', 'در ابتدا', 'سپس', 'بعد از آن', 'در مرحله بعد',
            'همزمان', 'سرانجام', 'در نهایت', 'در پایان'
        ],
        'emphasis_example' => [
            'به عنوان مثال', 'برای نمونه', 'در واقع', 'به ویژه', 'مخصوصا',
            'به خصوص', 'به طور مشخص', 'قطعا', 'مسلما'
        ],
        'conclusion' => [
            'در مجموع', 'به طور کلی', 'خلاصه اینکه', 'در یک کلام', 'روی هم رفته'
        ]
    ];

    /**
     * Readability scorer instance for sentence extraction.
     *
     * @var ReadabilityScorer
     */
    protected $readability;

    /**
     * Keyword analyzer instance for normalization.
     *
     * @var KeywordAnalyzer
     */
    protected $keywordAnalyzer;

    /**
     * Recommended transition sentence percentage threshold.
     *
     * @var float
     */
    protected $recommendedThreshold = 30.0;

    /**
     * Constructor.
     *
     * @param ReadabilityScorer|null $readability
     * @param KeywordAnalyzer|null $keywordAnalyzer
     * @param float $recommendedThreshold
     */
    public function __construct(ReadabilityScorer $readability = null, KeywordAnalyzer $keywordAnalyzer = null, $recommendedThreshold = 30.0) {
        $this->readability = $readability ?: new ReadabilityScorer();
        $this->keywordAnalyzer = $keywordAnalyzer ?: new KeywordAnalyzer();
        $this->recommendedThreshold = max(5.0, (float) $recommendedThreshold);
    }

    /**
     * Set recommended threshold percentage.
     *
     * @param float $threshold
     * @return self
     */
    public function setRecommendedThreshold($threshold) {
        $this->recommendedThreshold = max(5.0, (float) $threshold);
        return $this;
    }

    /**
     * Get recommended threshold percentage.
     *
     * @return float
     */
    public function getRecommendedThreshold() {
        return $this->recommendedThreshold;
    }

    /**
     * Find transitions in a single sentence without substring false-positives.
     *
     * @param string $sentence
     * @param string $language 'en' or 'fa'
     * @return array [has_transition, found_transitions, categories]
     */
    public function findTransitionsInSentence($sentence, $language = 'en') {
        $normalizedSentence = $this->keywordAnalyzer->normalizeText($sentence);
        if ($normalizedSentence === '') {
            return ['has_transition' => false, 'found_transitions' => [], 'categories' => []];
        }

        $categoriesList = ($language === 'fa') ? self::$persianTransitions : self::$englishTransitions;

        $found = [];
        $categories = [];

        foreach ($categoriesList as $category => $phrases) {
            foreach ($phrases as $phrase) {
                $normalizedPhrase = $this->keywordAnalyzer->normalizeText($phrase);
                if (empty($normalizedPhrase)) {
                    continue;
                }

                // Check occurrences using token-level matching to prevent substring false-positives (e.g., 'but' in 'button')
                $occurrences = $this->keywordAnalyzer->countTermOccurrences($normalizedPhrase, $normalizedSentence);
                if ($occurrences > 0) {
                    $found[] = $phrase;
                    if (!in_array($category, $categories, true)) {
                        $categories[] = $category;
                    }
                }
            }
        }

        return [
            'has_transition'    => !empty($found),
            'found_transitions' => array_values(array_unique($found)),
            'categories'        => $categories,
        ];
    }

    /**
     * Analyze content for transition word coverage.
     *
     * @param string $content
     * @return array
     */
    public function analyze($content) {
        $sentences = $this->readability->splitSentences($content);
        $totalSentences = count($sentences);

        if ($totalSentences === 0) {
            return [
                'total_sentences'              => 0,
                'sentences_with_transitions'   => 0,
                'transition_percentage'        => 0.0,
                'threshold'                    => $this->recommendedThreshold,
                'is_acceptable'                => false,
                'category_breakdown'           => [],
                'all_found_transitions'        => [],
                'diagnostic'                   => ['status' => 'warning', 'message' => 'No content provided.'],
                'language'                     => 'en',
            ];
        }

        $language = $this->readability->detectLanguage($content);
        $sentencesWithTransitions = 0;
        $categoryCounts = [
            'addition'         => 0,
            'contrast'         => 0,
            'cause_effect'     => 0,
            'sequence_time'    => 0,
            'emphasis_example' => 0,
            'conclusion'       => 0,
        ];
        $allTransitionsFound = [];
        $sentenceDetails = [];

        foreach ($sentences as $index => $sentence) {
            $analysis = $this->findTransitionsInSentence($sentence, $language);

            if ($analysis['has_transition']) {
                $sentencesWithTransitions++;
                foreach ($analysis['categories'] as $cat) {
                    if (isset($categoryCounts[$cat])) {
                        $categoryCounts[$cat]++;
                    }
                }
                foreach ($analysis['found_transitions'] as $tr) {
                    $allTransitionsFound[] = $tr;
                }
            }

            $sentenceDetails[] = [
                'sentence_index'    => $index + 1,
                'sentence'          => $sentence,
                'has_transition'    => $analysis['has_transition'],
                'found_transitions' => $analysis['found_transitions'],
                'categories'        => $analysis['categories'],
            ];
        }

        $percentage = round(($sentencesWithTransitions / $totalSentences) * 100, 1);
        $isAcceptable = ($percentage >= $this->recommendedThreshold);

        $diagnostic = [
            'status'  => $isAcceptable ? 'good' : 'warning',
            'message' => $isAcceptable
                ? sprintf('Great: %.1f%% of sentences contain transition words (recommended: %.1f%% or higher).', $percentage, $this->recommendedThreshold)
                : sprintf('Only %.1f%% of sentences contain transition words. Recommended minimum is %.1f%% for smooth reading flow.', $percentage, $this->recommendedThreshold),
        ];

        return [
            'total_sentences'              => $totalSentences,
            'sentences_with_transitions'   => $sentencesWithTransitions,
            'transition_percentage'        => $percentage,
            'threshold'                    => $this->recommendedThreshold,
            'is_acceptable'                => $isAcceptable,
            'category_breakdown'           => $categoryCounts,
            'all_found_transitions'        => array_values(array_unique($allTransitionsFound)),
            'sentence_details'             => $sentenceDetails,
            'diagnostic'                   => $diagnostic,
            'language'                     => $language,
        ];
    }
}
