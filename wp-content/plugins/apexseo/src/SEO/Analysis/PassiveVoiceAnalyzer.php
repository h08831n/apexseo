<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-052: Passive Voice Detection Engine.
 *
 * Identifies passive voice constructions in English sentences using auxiliary verbs
 * and regular/irregular past participles, with intelligent false-positive filtering
 * (e.g., predicate adjectives, stative conditions) and configurable threshold ratings.
 */
class PassiveVoiceAnalyzer {
    /**
     * Common irregular English past participles.
     *
     * @var array
     */
    protected static $irregularParticiples = [
        'arisen', 'awoken', 'been', 'borne', 'beaten', 'become', 'begun', 'bent',
        'bet', 'bound', 'bitten', 'bled', 'blown', 'broken', 'bred', 'brought',
        'built', 'burnt', 'burst', 'bought', 'cast', 'caught', 'chosen', 'clung',
        'come', 'cost', 'crept', 'cut', 'dealt', 'dug', 'done', 'drawn',
        'dreamt', 'drunk', 'driven', 'eaten', 'fallen', 'fed', 'felt', 'fought',
        'found', 'fled', 'flung', 'flown', 'forbidden', 'forgotten', 'forgiven',
        'frozen', 'gotten', 'got', 'given', 'gone', 'ground', 'grown', 'hung',
        'heard', 'hidden', 'hit', 'held', 'hurt', 'kept', 'knelt', 'knit',
        'known', 'laid', 'led', 'leapt', 'learnt', 'left', 'lent', 'let',
        'lain', 'lighted', 'lit', 'lost', 'made', 'meant', 'met', 'misunderstood',
        'mown', 'overcome', 'overheard', 'overtaken', 'paid', 'proven', 'proved',
        'put', 'quit', 'read', 'rid', 'ridden', 'rung', 'risen', 'run',
        'sawn', 'said', 'seen', 'sought', 'sold', 'sent', 'set', 'sewn',
        'shaken', 'shaven', 'shorn', 'shed', 'shone', 'shot', 'shown', 'shrunk',
        'shut', 'sung', 'sunk', 'sat', 'slain', 'slept', 'slid', 'slit',
        'smelt', 'sown', 'spoken', 'sped', 'spent', 'spilt', 'spun', 'spit',
        'split', 'spoilt', 'spread', 'sprung', 'stood', 'stolen', 'stuck',
        'stung', 'stunk', 'strewn', 'struck', 'strung', 'striven', 'sworn',
        'swept', 'swollen', 'swum', 'swung', 'taken', 'taught', 'torn', 'told',
        'thought', 'thrived', 'thrown', 'thrust', 'trodden', 'understood',
        'undertaken', 'upset', 'woken', 'worn', 'woven', 'wept', 'won', 'wound',
        'withdrawn', 'withheld', 'withstood', 'written'
    ];

    /**
     * Common predicate adjectives / false-positive participles.
     *
     * @var array
     */
    protected static $stativeAdjectives = [
        'tired', 'interested', 'married', 'worried', 'excited', 'bored',
        'confused', 'frightened', 'scared', 'surprised', 'located', 'situated',
        'dressed', 'pleased', 'satisfied', 'closed', 'crowded', 'related',
        'opposed', 'supposed', 'accustomed', 'qualified', 'dedicated',
        'delighted', 'exhausted', 'finished', 'prepared', 'disappointed',
        'complicated', 'advanced', 'detailed', 'limited', 'experienced'
    ];

    /**
     * Readability scorer instance for sentence splitting.
     *
     * @var ReadabilityScorer
     */
    protected $readability;

    /**
     * Maximum acceptable passive voice sentence ratio (percentage).
     *
     * @var float
     */
    protected $maxPassiveRatio = 10.0;

    /**
     * Constructor.
     *
     * @param ReadabilityScorer|null $readability
     * @param float $maxPassiveRatio
     */
    public function __construct(ReadabilityScorer $readability = null, $maxPassiveRatio = 10.0) {
        $this->readability = $readability ?: new ReadabilityScorer();
        $this->maxPassiveRatio = max(1.0, (float) $maxPassiveRatio);
    }

    /**
     * Set max acceptable passive sentence percentage.
     *
     * @param float $ratio
     * @return self
     */
    public function setMaxPassiveRatio($ratio) {
        $this->maxPassiveRatio = max(1.0, (float) $ratio);
        return $this;
    }

    /**
     * Get max acceptable passive ratio.
     *
     * @return float
     */
    public function getMaxPassiveRatio() {
        return $this->maxPassiveRatio;
    }

    /**
     * Check if a single sentence contains a passive voice construction.
     *
     * @param string $sentence
     * @return array [is_passive, matches, text, details]
     */
    public function isPassiveSentence($sentence) {
        $clean = trim(strip_tags($sentence));
        if ($clean === '') {
            return ['is_passive' => false, 'matches' => [], 'text' => $sentence, 'details' => []];
        }

        // Auxiliary "to be" / "to get" forms
        $auxRegex = '\b(is|am|are|was|were|be|being|been|get|gets|got|gotten|\'s|\'re)\b';

        // Optional adverbs in-between (e.g., "was quickly written", "is often considered")
        $advRegex = '(?:\s+\b\w+ly\b|\s+\boften\b|\s+\balways\b|\s+\balso\b|\s+\balready\b|\s+\bever\b|\s+\bnever\b|\s+\bjust\b)?';

        // Verb target (regular -ed or known irregular participle)
        $irregularList = implode('|', self::$irregularParticiples);
        $verbRegex = '\s+(\b\w+ed\b|' . $irregularList . ')';

        $pattern = '/' . $auxRegex . $advRegex . $verbRegex . '/i';

        if (preg_match_all($pattern, $clean, $matches, PREG_SET_ORDER)) {
            $detectedPassives = [];
            $details = [];

            foreach ($matches as $match) {
                $matchedString = $match[0];
                $aux = strtolower(trim($match[1]));
                $verb = strtolower(trim(end($match)));

                // Filter out known stative adjectives / predicate adjectives
                if (in_array($verb, self::$stativeAdjectives, true)) {
                    continue;
                }

                // Confidence scoring: higher if explicit "by [agent]" exists in sentence
                $hasByAgent = (bool) preg_match('/\bby\s+(?:the|a|an|our|their|his|her|its|[a-zA-Z]+)\b/i', $clean);
                $confidence = $hasByAgent ? 0.95 : 0.88;

                $detectedPassives[] = $matchedString;
                $details[] = [
                    'matched_text' => $matchedString,
                    'auxiliary'    => $aux,
                    'participle'   => $verb,
                    'confidence'   => $confidence,
                    'reason'       => sprintf("Auxiliary verb '%s' followed by past participle '%s'.", $aux, $verb),
                ];
            }

            if (!empty($detectedPassives)) {
                return [
                    'is_passive' => true,
                    'matches'    => $detectedPassives,
                    'text'       => $clean,
                    'details'    => $details,
                ];
            }
        }

        return ['is_passive' => false, 'matches' => [], 'text' => $clean, 'details' => []];
    }

    /**
     * Analyze content for passive voice density and diagnostics.
     *
     * @param string $content
     * @return array
     */
    public function analyze($content) {
        $sentences = $this->readability->splitSentences($content);
        $totalSentences = count($sentences);

        if ($totalSentences === 0) {
            return [
                'total_sentences'   => 0,
                'passive_sentences' => 0,
                'passive_ratio'     => 0.0,
                'threshold'         => $this->maxPassiveRatio,
                'is_acceptable'     => true,
                'passive_details'   => [],
                'diagnostic'        => ['status' => 'good', 'message' => 'No content to analyze.'],
                'language'          => 'en',
                'methodology'       => 'heuristic_pattern_matching',
            ];
        }

        $language = $this->readability->detectLanguage($content);
        if ($language !== 'en') {
            return [
                'total_sentences'   => $totalSentences,
                'passive_sentences' => 0,
                'passive_ratio'     => 0.0,
                'threshold'         => $this->maxPassiveRatio,
                'is_acceptable'     => true,
                'passive_details'   => [],
                'diagnostic'        => [
                    'status'  => 'info',
                    'message' => 'Passive voice rules are optimized for English content.',
                ],
                'language'          => $language,
                'methodology'       => 'heuristic_pattern_matching',
            ];
        }

        $passiveCount = 0;
        $passiveDetails = [];

        foreach ($sentences as $index => $sentence) {
            $result = $this->isPassiveSentence($sentence);
            if ($result['is_passive']) {
                $passiveCount++;
                $passiveDetails[] = [
                    'sentence_index' => $index + 1,
                    'sentence'       => $sentence,
                    'matches'        => $result['matches'],
                    'details'        => $result['details'],
                ];
            }
        }

        $passiveRatio = round(($passiveCount / $totalSentences) * 100, 1);
        $isAcceptable = ($passiveRatio <= $this->maxPassiveRatio);

        $diagnostic = [
            'status'  => $isAcceptable ? 'good' : 'error',
            'message' => $isAcceptable
                ? sprintf('Passive voice is %.1f%% of sentences (below the %.1f%% recommended limit).', $passiveRatio, $this->maxPassiveRatio)
                : sprintf('Passive voice was detected in %.1f%% of sentences (recommended: %.1f%% or less). Consider rewriting some passive sentences in active voice.', $passiveRatio, $this->maxPassiveRatio),
        ];

        return [
            'total_sentences'   => $totalSentences,
            'passive_sentences' => $passiveCount,
            'passive_ratio'     => $passiveRatio,
            'threshold'         => $this->maxPassiveRatio,
            'is_acceptable'     => $isAcceptable,
            'passive_details'   => $passiveDetails,
            'diagnostic'        => $diagnostic,
            'language'          => $language,
            'methodology'       => 'heuristic_pattern_matching',
        ];
    }
}
