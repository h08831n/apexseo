<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-052: Passive Voice Detection Engine.
 *
 * Identifies passive voice constructions in English sentences using auxiliary verbs
 * and regular/irregular past participles, with intelligent false-positive filtering
 * (e.g., predicate adjectives, stative conditions) and readability threshold ratings.
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
        'opposed', 'supposed', 'accustomed', 'qualified', 'dedicated'
    ];

    /**
     * Readability scorer instance for sentence splitting.
     *
     * @var ReadabilityScorer
     */
    protected $readability;

    /**
     * Constructor.
     */
    public function __construct(ReadabilityScorer $readability = null) {
        $this->readability = $readability ?: new ReadabilityScorer();
    }

    /**
     * Check if a single sentence contains a passive voice construction.
     *
     * @param string $sentence
     * @return array [is_passive, matches, text]
     */
    public function isPassiveSentence($sentence) {
        $clean = trim(strip_tags($sentence));
        if ($clean === '') {
            return ['is_passive' => false, 'matches' => [], 'text' => $sentence];
        }

        // Auxiliary "to be" forms: is, am, are, was, were, be, being, been, 's, 're, 've been
        $auxRegex = '\b(is|am|are|was|were|be|being|been|get|gets|got|gotten|\'s|\'re)\b';

        // Optional adverbs in-between (e.g., "was quickly written", "is often considered")
        $advRegex = '(?:\s+\b\w+ly\b|\s+\boften\b|\s+\balways\b|\s+\balso\b|\s+\balready\b|\s+\bever\b)?';

        // Verb target (regular -ed or known irregular participle)
        $irregularList = implode('|', self::$irregularParticiples);
        $verbRegex = '\s+(\b\w+ed\b|' . $irregularList . ')';

        $pattern = '/' . $auxRegex . $advRegex . $verbRegex . '/i';

        if (preg_match_all($pattern, $clean, $matches, PREG_SET_ORDER)) {
            $detectedPassives = [];
            foreach ($matches as $match) {
                $verb = strtolower(trim(end($match)));

                // Filter out known stative adjectives
                if (in_array($verb, self::$stativeAdjectives)) {
                    continue;
                }

                $detectedPassives[] = $match[0];
            }

            if (!empty($detectedPassives)) {
                return [
                    'is_passive' => true,
                    'matches'    => $detectedPassives,
                    'text'       => $clean,
                ];
            }
        }

        return ['is_passive' => false, 'matches' => [], 'text' => $clean];
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
                'is_acceptable'     => true,
                'passive_details'   => [],
                'diagnostic'        => ['status' => 'good', 'message' => 'No content to analyze.'],
                'language'          => 'en',
            ];
        }

        $language = $this->readability->detectLanguage($content);
        if ($language !== 'en') {
            return [
                'total_sentences'   => $totalSentences,
                'passive_sentences' => 0,
                'passive_ratio'     => 0.0,
                'is_acceptable'     => true,
                'passive_details'   => [],
                'diagnostic'        => [
                    'status'  => 'info',
                    'message' => 'Passive voice rules are optimized for English content.',
                ],
                'language'          => $language,
            ];
        }

        $passiveCount = 0;
        $passiveDetails = [];

        foreach ($sentences as $index => $sentence) {
            $result = $this->isPassiveSentence($sentence);
            if ($result['is_passive']) {
                $passiveCount++;
                $passiveDetails[] = [
                    'index'   => $index + 1,
                    'text'    => $result['text'],
                    'matches' => $result['matches'],
                ];
            }
        }

        $passiveRatio = round(($passiveCount / $totalSentences) * 100, 1);
        $isAcceptable = ($passiveRatio <= 10.0);

        if ($passiveRatio <= 10.0) {
            $diagnostic = [
                'status'  => 'good',
                'message' => sprintf('Passive voice is %.1f%%, which is within the recommended limit (<= 10%%).', $passiveRatio),
            ];
        } elseif ($passiveRatio <= 15.0) {
            $diagnostic = [
                'status'  => 'warning',
                'message' => sprintf('Passive voice is %.1f%%. Try to use more active voice (recommended <= 10%%).', $passiveRatio),
            ];
        } else {
            $diagnostic = [
                'status'  => 'error',
                'message' => sprintf('Passive voice is %.1f%%, which is too high. Active voice improves clarity and reader engagement.', $passiveRatio),
            ];
        }

        return [
            'total_sentences'   => $totalSentences,
            'passive_sentences' => $passiveCount,
            'passive_ratio'     => $passiveRatio,
            'is_acceptable'     => $isAcceptable,
            'passive_details'   => $passiveDetails,
            'diagnostic'        => $diagnostic,
            'language'          => $language,
        ];
    }
}
