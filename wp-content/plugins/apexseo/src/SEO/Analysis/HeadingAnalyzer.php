<?php
namespace ApexSEO\SEO\Analysis;

/**
 * APEX-050: Heading Structure Hierarchy Checker.
 *
 * Parses HTML content to validate H1-H6 heading hierarchy, detect missing/duplicate H1s,
 * identify skipped heading levels (e.g. H1 -> H3), and catch empty headings.
 */
class HeadingAnalyzer {
    /**
     * Parse and inspect heading elements in HTML content.
     *
     * @param string $html
     * @return array Array of heading objects: [tag, level, text, is_empty, position]
     */
    public function extractHeadings($html) {
        if (empty($html)) {
            return [];
        }

        $headings = [];
        $pattern = '/<(h[1-6])([^>]*)>(.*?)<\/\1>/is';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $position = 0;
            foreach ($matches as $match) {
                $tag = strtolower($match[1][0]);
                $level = (int) substr($tag, 1);
                $rawContent = $match[3][0];
                $cleanText = trim(strip_tags(html_entity_decode($rawContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $offset = $match[0][1];

                $headings[] = [
                    'tag'       => $tag,
                    'level'     => $level,
                    'text'      => $cleanText,
                    'is_empty'  => ($cleanText === ''),
                    'position'  => $position++,
                    'offset'    => $offset,
                ];
            }
        }

        return $headings;
    }

    /**
     * Analyze heading structure for SEO and accessibility best practices.
     *
     * @param string $html
     * @return array Detailed diagnostics and status report
     */
    public function analyze($html) {
        $headings = $this->extractHeadings($html);
        $totalHeadings = count($headings);

        $h1Count = 0;
        $h2Count = 0;
        $h3Count = 0;
        $h4Count = 0;
        $h5Count = 0;
        $h6Count = 0;
        $emptyHeadings = [];
        $hierarchyIssues = [];
        $diagnostics = [];

        $previousLevel = 0;

        foreach ($headings as $index => $heading) {
            $level = $heading['level'];
            $tag = $heading['tag'];

            // Level counters
            switch ($level) {
                case 1: $h1Count++; break;
                case 2: $h2Count++; break;
                case 3: $h3Count++; break;
                case 4: $h4Count++; break;
                case 5: $h5Count++; break;
                case 6: $h6Count++; break;
            }

            // Check for empty headings
            if ($heading['is_empty']) {
                $emptyHeadings[] = $heading;
                $diagnostics[] = [
                    'type'     => 'empty_heading',
                    'severity' => 'warning',
                    'message'  => sprintf('Found empty %s heading at position %d.', strtoupper($tag), $index + 1),
                    'tag'      => $tag,
                    'index'    => $index,
                ];
            }

            // Check for heading skips (e.g. H1 -> H3 without H2, H2 -> H4 without H3)
            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                $skippedFrom = 'H' . $previousLevel;
                $skippedTo = 'H' . $level;
                $issue = [
                    'from_tag' => $skippedFrom,
                    'to_tag'   => $skippedTo,
                    'index'    => $index,
                    'text'     => $heading['text'],
                    'message'  => sprintf('Heading level skipped: jumped from %s to %s without an intermediate heading.', $skippedFrom, $skippedTo),
                ];
                $hierarchyIssues[] = $issue;
                $diagnostics[] = [
                    'type'     => 'skipped_level',
                    'severity' => 'warning',
                    'message'  => $issue['message'],
                    'tag'      => $tag,
                    'index'    => $index,
                ];
            }

            $previousLevel = $level;
        }

        // H1 Diagnostics
        if ($h1Count === 0) {
            $diagnostics[] = [
                'type'     => 'missing_h1',
                'severity' => 'error',
                'message'  => 'No H1 heading found in the content. Each page should have exactly one main H1 heading.',
            ];
        } elseif ($h1Count > 1) {
            $diagnostics[] = [
                'type'     => 'multiple_h1',
                'severity' => 'warning',
                'message'  => sprintf('Found %d H1 headings. It is recommended to use only one main H1 per page for optimal hierarchy.', $h1Count),
            ];
        } else {
            $diagnostics[] = [
                'type'     => 'single_h1',
                'severity' => 'good',
                'message'  => 'Great job: Exactly one H1 heading found.',
            ];
        }

        // Overall Hierarchy Score (0 to 100)
        $score = 100;
        if ($h1Count === 0) {
            $score -= 30;
        } elseif ($h1Count > 1) {
            $score -= 15;
        }
        $score -= (count($hierarchyIssues) * 15);
        $score -= (count($emptyHeadings) * 10);
        $score = max(0, $score);

        return [
            'score'             => $score,
            'total_headings'    => $totalHeadings,
            'h1_count'          => $h1Count,
            'h2_count'          => $h2Count,
            'h3_count'          => $h3Count,
            'h4_count'          => $h4Count,
            'h5_count'          => $h5Count,
            'h6_count'          => $h6Count,
            'headings'          => $headings,
            'empty_headings'    => $emptyHeadings,
            'hierarchy_issues'  => $hierarchyIssues,
            'diagnostics'       => $diagnostics,
            'is_valid'          => ($h1Count === 1 && empty($hierarchyIssues) && empty($emptyHeadings)),
        ];
    }
}
