<?php
namespace ApexSEO\AI\Generators;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * AI & Semantic Metadata Assistant.
 */
class MetadataAiGenerator implements ServiceContractInterface {
    /**
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Constructor.
     *
     * @param VariableEngine $variableEngine
     */
    public function __construct(VariableEngine $variableEngine) {
        $this->variableEngine = $variableEngine;
    }

    /**
     * Generate optimized meta title candidates from content summary.
     *
     * @param string $content
     * @param string $focusKeyword
     * @return array<string>
     */
    public function generateTitleCandidates($content, $focusKeyword = '') {
        $clean = trim(strip_tags($content));
        $firstSentence = strtok($clean, ".?!\n");
        $siteName = function_exists('get_bloginfo') ? get_bloginfo('name') : 'Apex SEO';

        $candidates = [];
        if (!empty($focusKeyword)) {
            $capitalized = ucwords($focusKeyword);
            $candidates[] = sprintf('%s: The Complete Guide | %s', $capitalized, $siteName);
            $candidates[] = sprintf('How to Master %s (%s) - %s', $capitalized, date('Y'), $siteName);
            $candidates[] = sprintf('%s Explained: Best Practices & Tips', $capitalized);
        } else {
            $candidates[] = sprintf('%s | %s', $firstSentence, $siteName);
            $candidates[] = sprintf('%s - %s Edition', $firstSentence, date('Y'));
        }

        return $candidates;
    }

    /**
     * Generate engaging meta description candidates.
     *
     * @param string $content
     * @param string $focusKeyword
     * @return array<string>
     */
    public function generateDescriptionCandidates($content, $focusKeyword = '') {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
        $excerpt = mb_substr($clean, 0, 145);

        $candidates = [];
        if (!empty($focusKeyword)) {
            $candidates[] = sprintf('Discover everything about %s. %s... Read more to learn top insights.', $focusKeyword, $excerpt);
            $candidates[] = sprintf('Looking for %s? Learn practical tips, step-by-step strategies, and expert advice.', $focusKeyword);
        } else {
            $candidates[] = sprintf('%s... Explore our latest in-depth analysis and findings.', $excerpt);
        }

        return $candidates;
    }
}
