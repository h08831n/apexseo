<?php
namespace ApexSEO\SEO\Analysis;

use ApexSEO\SEO\Repository\IndexableRepository;

/**
 * Phase 4 Master Subsystem Coordinator: Content Intelligence & On-Page Analysis Engine.
 *
 * Coordinates APEX-048 through APEX-054 to perform unified multi-keyword density,
 * TF-IDF extraction, Flesch readability scoring, heading hierarchy checking,
 * internal link graph scanning, passive voice detection, and text structure analysis.
 */
class ContentAnalyzer {
    /**
     * @var KeywordAnalyzer
     */
    protected $keywordAnalyzer;

    /**
     * @var ReadabilityScorer
     */
    protected $readabilityScorer;

    /**
     * @var HeadingAnalyzer
     */
    protected $headingAnalyzer;

    /**
     * @var LinkGraphScanner
     */
    protected $linkGraphScanner;

    /**
     * @var PassiveVoiceAnalyzer
     */
    protected $passiveVoiceAnalyzer;

    /**
     * @var TransitionWordAnalyzer
     */
    protected $transitionWordAnalyzer;

    /**
     * @var TextStructureAnalyzer
     */
    protected $textStructureAnalyzer;

    /**
     * @var IndexableRepository|null
     */
    protected $indexableRepository;

    /**
     * Schema version for analysis reports.
     *
     * @var string
     */
    const SCHEMA_VERSION = '1.0.0';

    /**
     * Engine version.
     *
     * @var string
     */
    const ANALYZER_VERSION = '1.0.0';

    /**
     * Constructor.
     */
    public function __construct(
        KeywordAnalyzer $keywordAnalyzer = null,
        ReadabilityScorer $readabilityScorer = null,
        HeadingAnalyzer $headingAnalyzer = null,
        LinkGraphScanner $linkGraphScanner = null,
        PassiveVoiceAnalyzer $passiveVoiceAnalyzer = null,
        TransitionWordAnalyzer $transitionWordAnalyzer = null,
        TextStructureAnalyzer $textStructureAnalyzer = null,
        IndexableRepository $indexableRepository = null
    ) {
        $this->keywordAnalyzer = $keywordAnalyzer ?: new KeywordAnalyzer();
        $this->readabilityScorer = $readabilityScorer ?: new ReadabilityScorer();
        $this->headingAnalyzer = $headingAnalyzer ?: new HeadingAnalyzer();
        $this->linkGraphScanner = $linkGraphScanner ?: new LinkGraphScanner();
        $this->passiveVoiceAnalyzer = $passiveVoiceAnalyzer ?: new PassiveVoiceAnalyzer($this->readabilityScorer);
        $this->transitionWordAnalyzer = $transitionWordAnalyzer ?: new TransitionWordAnalyzer($this->readabilityScorer, $this->keywordAnalyzer);
        $this->textStructureAnalyzer = $textStructureAnalyzer ?: new TextStructureAnalyzer($this->readabilityScorer);
        $this->indexableRepository = $indexableRepository;
    }

    /**
     * Get KeywordAnalyzer.
     *
     * @return KeywordAnalyzer
     */
    public function getKeywordAnalyzer() {
        return $this->keywordAnalyzer;
    }

    /**
     * Get ReadabilityScorer.
     *
     * @return ReadabilityScorer
     */
    public function getReadabilityScorer() {
        return $this->readabilityScorer;
    }

    /**
     * Get HeadingAnalyzer.
     *
     * @return HeadingAnalyzer
     */
    public function getHeadingAnalyzer() {
        return $this->headingAnalyzer;
    }

    /**
     * Get LinkGraphScanner.
     *
     * @return LinkGraphScanner
     */
    public function getLinkGraphScanner() {
        return $this->linkGraphScanner;
    }

    /**
     * Get PassiveVoiceAnalyzer.
     *
     * @return PassiveVoiceAnalyzer
     */
    public function getPassiveVoiceAnalyzer() {
        return $this->passiveVoiceAnalyzer;
    }

    /**
     * Get TransitionWordAnalyzer.
     *
     * @return TransitionWordAnalyzer
     */
    public function getTransitionWordAnalyzer() {
        return $this->transitionWordAnalyzer;
    }

    /**
     * Get TextStructureAnalyzer.
     *
     * @return TextStructureAnalyzer
     */
    public function getTextStructureAnalyzer() {
        return $this->textStructureAnalyzer;
    }

    /**
     * Calculate composite Readability Score (0-100).
     *
     * @param array $readability
     * @param array $passiveVoice
     * @param array $transitionWords
     * @param array $textStructure
     * @return int Score between 0 and 100
     */
    public function calculateReadabilityScore(array $readability, array $passiveVoice, array $transitionWords, array $textStructure) {
        if (($readability['words_count'] ?? 0) === 0) {
            return 0;
        }

        $score = 100;
        $isFleschSupported = !empty($readability['is_flesch_supported']);

        if ($isFleschSupported) {
            // Flesch Reading Ease contribution (40% weight)
            $flesch = $readability['flesch_reading_ease'] ?? 60.0;
            if ($flesch < 50.0) {
                $score -= (int) round((50.0 - $flesch) * 0.6);
            }
        } else {
            // Non-English / Persian structural baseline
            $asl = $readability['avg_words_per_sentence'] ?? 15.0;
            if ($asl > 25.0) {
                $score -= (int) min(25, round(($asl - 25.0) * 1.5));
            }
        }

        // Passive Voice contribution (20% weight)
        if (empty($passiveVoice['is_acceptable'])) {
            $ratio = $passiveVoice['passive_ratio'] ?? 0.0;
            $threshold = $passiveVoice['threshold'] ?? 10.0;
            if ($ratio > $threshold) {
                $score -= (int) min(20, round(($ratio - $threshold) * 1.5));
            }
        }

        // Transition Words contribution (20% weight)
        if (empty($transitionWords['is_acceptable'])) {
            $transPercentage = $transitionWords['transition_percentage'] ?? 0.0;
            $threshold = $transitionWords['threshold'] ?? 30.0;
            if ($transPercentage < $threshold) {
                $score -= (int) min(20, round(($threshold - $transPercentage) * 0.7));
            }
        }

        // Sentence / Paragraph Length contribution (20% weight)
        if (!empty($textStructure['oversized_paragraphs'])) {
            $score -= (int) min(10, count($textStructure['oversized_paragraphs']) * 5);
        }
        $sentenceRatio = $textStructure['oversized_sentences_ratio'] ?? 0.0;
        $maxSentenceRatio = $textStructure['max_words_per_sentence_limit'] ?? 25.0;
        if ($sentenceRatio > 25.0) {
            $score -= (int) min(10, round(($sentenceRatio - 25.0) * 0.5));
        }

        return (int) max(0, min(100, $score));
    }

    /**
     * Calculate composite Content SEO Score (0-100).
     *
     * @param array $keywordAnalysis
     * @param array $headingAnalysis
     * @param array $linkAnalysis
     * @param int $wordCount
     * @return int Score between 0 and 100
     */
    public function calculateSeoScore(array $keywordAnalysis, array $headingAnalysis, array $linkAnalysis, $wordCount) {
        if ($wordCount === 0) {
            return 0;
        }

        $score = 100;

        // Content Length (Min 300 words recommended)
        if ($wordCount < 300) {
            $score -= (int) min(30, round((300 - $wordCount) / 10));
        }

        // Primary Keyword optimization
        if (!empty($keywordAnalysis['primary_keyword'])) {
            $pk = $keywordAnalysis['primary_keyword'];
            if ($pk['count'] === 0) {
                $score -= 30; // Missing focus keyword
            } elseif ($pk['density'] < 0.5) {
                $score -= 10; // Under-optimized
            } elseif ($pk['is_over_optimized']) {
                $score -= 25; // Keyword stuffing
            }
        }

        // Heading Structure
        $headingScore = $headingAnalysis['score'] ?? 100;
        if ($headingScore < 100) {
            $score -= (int) round((100 - $headingScore) * 0.25);
        }

        // Links presence (at least 1 internal link recommended)
        if (($linkAnalysis['internal_links'] ?? 0) === 0) {
            $score -= 10;
        }

        return (int) max(0, min(100, $score));
    }

    /**
     * Analyze content completely across all on-page intelligence metrics.
     *
     * @param string $content HTML or plain text
     * @param array $options [primary_keyword, secondary_keywords, post_id, site_url]
     * @return array Unified intelligence report
     */
    public function analyzeContent($content, array $options = []) {
        $primaryKeyword = $options['primary_keyword'] ?? null;
        $secondaryKeywords = $options['secondary_keywords'] ?? [];
        $postId = isset($options['post_id']) ? (int) $options['post_id'] : null;

        if (isset($options['site_url'])) {
            $this->linkGraphScanner->setSiteUrl($options['site_url']);
        }

        // 1. Keyword & TF-IDF Analysis (APEX-048)
        $keywords = $this->keywordAnalyzer->analyze($content, $primaryKeyword, $secondaryKeywords);

        // 2. Readability & Formulas (APEX-049)
        $readability = $this->readabilityScorer->score($content);

        // 3. Heading Structure Hierarchy (APEX-050)
        $headings = $this->headingAnalyzer->analyze($content);

        // 4. Internal Link Graph & Counters (APEX-051)
        $links = $this->linkGraphScanner->scan($content, $postId);

        // 5. Passive Voice Detection (APEX-052)
        $passiveVoice = $this->passiveVoiceAnalyzer->analyze($content);

        // 6. Transition Word Coverage (APEX-053)
        $transitions = $this->transitionWordAnalyzer->analyze($content);

        // 7. Paragraph & Sentence Length Analysis (APEX-054)
        $textStructure = $this->textStructureAnalyzer->analyze($content);

        // Composite Scores
        $readabilityScore = $this->calculateReadabilityScore($readability, $passiveVoice, $transitions, $textStructure);
        $seoScore = $this->calculateSeoScore($keywords, $headings, $links, $readability['words_count'] ?? 0);

        return [
            'schema_version'      => self::SCHEMA_VERSION,
            'analyzer_version'    => self::ANALYZER_VERSION,
            'score_disclaimer'    => 'Scores are heuristic on-page content optimization indicators, not proprietary search engine ranking algorithms.',
            'seo_score'           => $seoScore,
            'readability_score'   => $readabilityScore,
            'keywords'            => $keywords,
            'readability'         => $readability,
            'headings'            => $headings,
            'links'               => $links,
            'passive_voice'       => $passiveVoice,
            'transition_words'    => $transitions,
            'text_structure'      => $textStructure,
        ];
    }

    /**
     * Analyze a WordPress Post by ID, updating database scores if repository is available.
     *
     * @param int $postId
     * @return array|null
     */
    public function analyzePost($postId) {
        $post = function_exists('get_post') ? get_post($postId) : null;
        if (!$post) {
            return null;
        }

        $content = $post->post_content ?? '';
        $primaryKeyword = function_exists('get_post_meta') ? get_post_meta($postId, '_apexseo_focus_keyword', true) : null;

        $options = [
            'post_id'         => $postId,
            'primary_keyword' => $primaryKeyword,
        ];

        $analysis = $this->analyzeContent($content, $options);

        // Persist updated scores into Indexables table if repository exists
        if ($this->indexableRepository) {
            $indexable = $this->indexableRepository->findByObject('post', $postId);
            if ($indexable) {
                $indexable->seo_score = $analysis['seo_score'];
                $indexable->readability_score = $analysis['readability_score'];
                $this->indexableRepository->save($indexable);
            }
        }

        return $analysis;
    }
}
