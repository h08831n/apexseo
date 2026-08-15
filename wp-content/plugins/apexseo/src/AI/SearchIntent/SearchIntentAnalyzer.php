<?php
namespace ApexSEO\AI\SearchIntent;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Search Intent & Semantic Topic Classifier (Informational, Commercial, Transactional, Navigational).
 */
class SearchIntentAnalyzer implements ServiceContractInterface {
    /**
     * Informational keyword tokens.
     */
    protected $informationalTriggers = ['how', 'what', 'why', 'guide', 'tutorial', 'tips', 'best way', 'explained'];

    /**
     * Commercial keyword tokens.
     */
    protected $commercialTriggers = ['best', 'top', 'vs', 'versus', 'review', 'comparison', 'alternative'];

    /**
     * Transactional keyword tokens.
     */
    protected $transactionalTriggers = ['buy', 'price', 'pricing', 'discount', 'coupon', 'order', 'deal', 'cheap', 'purchase'];

    /**
     * Analyze search intent for a given title or focus keyword.
     *
     * @param string $query
     * @return array{primary_intent: string, confidence: float, suggestions: array}
     */
    public function analyze($query) {
        $clean = strtolower(trim($query));
        $words = explode(' ', $clean);

        $scores = [
            'informational' => 0,
            'commercial'    => 0,
            'transactional' => 0,
            'navigational'  => 0,
        ];

        foreach ($this->informationalTriggers as $trigger) {
            if (strpos($clean, $trigger) !== false) {
                $scores['informational'] += 2;
            }
        }

        foreach ($this->commercialTriggers as $trigger) {
            if (strpos($clean, $trigger) !== false) {
                $scores['commercial'] += 2;
            }
        }

        foreach ($this->transactionalTriggers as $trigger) {
            if (strpos($clean, $trigger) !== false) {
                $scores['transactional'] += 2;
            }
        }

        arsort($scores);
        $primaryIntent = key($scores);
        $topScore = reset($scores);

        if ($topScore === 0) {
            $primaryIntent = 'informational';
            $confidence = 0.50;
        } else {
            $confidence = min(0.95, 0.60 + ($topScore * 0.10));
        }

        return [
            'primary_intent' => $primaryIntent,
            'confidence'     => round($confidence, 2),
            'suggestions'    => $this->getIntentSuggestions($primaryIntent),
        ];
    }

    /**
     * Get structural recommendations based on primary intent.
     *
     * @param string $intent
     * @return array
     */
    protected function getIntentSuggestions($intent) {
        switch ($intent) {
            case 'transactional':
                return ['Add Product Schema', 'Include clear CTA buttons', 'Ensure pricing is visible in schema offers'];
            case 'commercial':
                return ['Add Comparison Table', 'Include Review Schema', 'Use Pros/Cons bullet format'];
            case 'informational':
            default:
                return ['Add FAQPage Schema', 'Include Table of Contents', 'Highlight Key Takeaways at top'];
        }
    }
}
