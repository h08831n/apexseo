<?php
namespace ApexSEO\Tests;

use ApexSEO\SEO\Analysis\KeywordAnalyzer;
use ApexSEO\SEO\Analysis\ReadabilityScorer;
use ApexSEO\SEO\Analysis\HeadingAnalyzer;
use ApexSEO\SEO\Analysis\LinkGraphScanner;
use ApexSEO\SEO\Analysis\PassiveVoiceAnalyzer;
use ApexSEO\SEO\Analysis\TransitionWordAnalyzer;
use ApexSEO\SEO\Analysis\TextStructureAnalyzer;
use ApexSEO\SEO\Analysis\ContentAnalyzer;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Hardened Behavioral Test Suite for Phase 4: Content Intelligence & On-Page Analysis Engine.
 * Tests APEX-048 through APEX-054.
 */
class AnalysisSubsystemTest extends TestCase {
    /**
     * APEX-048: Multi-Keyword Density & TF-IDF Content Analyzer.
     */
    public function testKeywordAnalyzerTokenizationAndNormalization() {
        $analyzer = new KeywordAnalyzer();

        // 1. Unicode & Persian/Arabic character normalization
        $persianInput = "آموزش سئو و بهینه‌سازی وب‌سایت با کلمات کلیدی مختلف است. يک متن آزمايشي با اعراب: کِتابُ!";
        $normalized = $analyzer->normalizeText($persianInput);

        $this->assertStringContains('یک متن آزمایشی', $normalized);
        $this->assertStringContains('کتاب', $normalized);
        $this->assertFalse(strpos($normalized, 'ي')); // Arabic Yeh should be converted to Persian Yeh

        // 2. Tokenization with and without stop-words
        $englishText = "The ultimate guide to fast WordPress search engine optimization and SEO architecture.";
        $allTokens = $analyzer->tokenize($englishText, false);
        $tokensWithoutStopWords = $analyzer->tokenize($englishText, true);

        $this->assertEquals(11, count($allTokens));
        $this->assertTrue(in_array('ultimate', $tokensWithoutStopWords));
        $this->assertTrue(in_array('guide', $tokensWithoutStopWords));
        $this->assertTrue(in_array('seo', $tokensWithoutStopWords));
        $this->assertFalse(in_array('the', $tokensWithoutStopWords)); // Stop word removed
        $this->assertFalse(in_array('to', $tokensWithoutStopWords));  // Stop word removed
        $this->assertFalse(in_array('and', $tokensWithoutStopWords)); // Stop word removed

        // 3. Multi-word phrase token count
        $this->assertEquals(2, $analyzer->countTermOccurrences('search engine', $englishText));
        $this->assertEquals(0, $analyzer->countTermOccurrences('nonexistent term', $englishText));
    }

    /**
     * APEX-048: Keyword Density, TF-IDF and Over-Optimization.
     */
    public function testKeywordAnalyzerDensityAndTfIdf() {
        $analyzer = new KeywordAnalyzer();

        $content = "WordPress performance optimization is critical. Great WordPress optimization requires fast servers and clean code. Optimization helps search engines.";

        // Count occurrences (single and multi-word)
        $this->assertEquals(2, $analyzer->countTermOccurrences('WordPress', $content));
        $this->assertEquals(2, $analyzer->countTermOccurrences('WordPress optimization', $content));

        // Density analysis
        $densityResult = $analyzer->analyzeKeywordDensity('WordPress', $content);
        $this->assertEquals(2, $densityResult['count']);
        $this->assertTrue($densityResult['density'] > 0.0);
        $this->assertFalse($densityResult['is_over_optimized']);
        $this->assertEquals(3.5, $densityResult['threshold']);

        // Configurable threshold test
        $analyzer->setOverOptimizationThreshold(1.5);
        $this->assertEquals(1.5, $analyzer->getOverOptimizationThreshold());

        // Over-optimization detection (Keyword stuffing simulation)
        $stuffedContent = str_repeat("best seo plugin ", 20) . "for WordPress websites.";
        $stuffedResult = $analyzer->analyzeKeywordDensity('best seo plugin', $stuffedContent);
        $this->assertTrue($stuffedResult['is_over_optimized']);
        $this->assertStringContains('exceeds configured threshold', $stuffedResult['warning']);

        // TF-IDF Fallback vs Corpus calculation
        $fallbackIdf = $analyzer->calculateInverseDocumentFrequency('wordpress');
        $this->assertEquals('fallback', $fallbackIdf['idf_source']);
        $this->assertEquals(1.0, $fallbackIdf['idf']);

        // Explicit corpus statistics
        $analyzer->setCorpusStatistics(100, ['wordpress' => 80, 'architecture' => 5]);
        $corpusIdf = $analyzer->calculateInverseDocumentFrequency('wordpress');
        $this->assertEquals('corpus', $corpusIdf['idf_source']);
        $this->assertTrue($corpusIdf['idf'] > 0.0);
        $this->assertEquals(100, $corpusIdf['corpus_docs']);
        $this->assertEquals(80, $corpusIdf['doc_frequency']);

        // Top TF-IDF term extraction
        $topTerms = $analyzer->extractTopTfIdfTerms($content, 5);
        $this->assertTrue(!empty($topTerms));
        $this->assertEquals('corpus', $topTerms[0]['idf_source']);
        $this->assertTrue(isset($topTerms[0]['tfidf']));
        $this->assertTrue(isset($topTerms[0]['tf']));
        $this->assertTrue(isset($topTerms[0]['idf']));
    }

    /**
     * APEX-049: Flesch Reading Ease & Grade Level Formula Scorer.
     */
    public function testReadabilityScorerFormulas() {
        $scorer = new ReadabilityScorer();

        // 1. Syllable counter verification
        $this->assertEquals(1, $scorer->countSyllables('cat'));
        $this->assertEquals(2, $scorer->countSyllables('water'));
        $this->assertEquals(3, $scorer->countSyllables('banana'));
        $this->assertEquals(4, $scorer->countSyllables('intelligent'));
        $this->assertEquals(1, $scorer->countSyllables('the'));
        $this->assertEquals(2, $scorer->countSyllables('simple'));

        // 2. Sentence splitting with abbreviations and decimals
        $complexText = "Dr. Smith went to Washington D.C. yesterday. He bought a car for $3.50. It was fast!";
        $sentences = $scorer->splitSentences($complexText);
        $this->assertEquals(3, count($sentences));

        // 3. Flesch Reading Ease on clear English text
        $easyEnglish = "The cat sat on the mat. It was a sunny day. The dog ran in the park. They played with a red ball.";
        $scoreResult = $scorer->score($easyEnglish);

        $this->assertEquals('en', $scoreResult['language']);
        $this->assertTrue($scoreResult['is_flesch_supported']);
        $this->assertTrue($scoreResult['flesch_reading_ease'] >= 80.0, 'Easy text should score >= 80 on Flesch scale');
        $this->assertTrue($scoreResult['flesch_kincaid_grade'] <= 6.0, 'Easy text should have low grade level');
        $this->assertEquals('Easy', $scoreResult['interpretation']['label']);
        $this->assertStringContains('Flesch Reading Ease', $scoreResult['formula']);

        // 4. Persian language handling (No invalid English Flesch score)
        $persianText = "سئو و بهینه‌سازی وب‌سایت یکی از مهم‌ترین عوامل رشد کسب‌وکار آنلاین است. تولید محتوای ارزشمند اهمیت دارد.";
        $persianResult = $scorer->score($persianText);

        $this->assertEquals('fa', $persianResult['language']);
        $this->assertFalse($persianResult['is_flesch_supported']);
        $this->assertNull($persianResult['flesch_reading_ease']);
        $this->assertNull($persianResult['flesch_kincaid_grade']);
        $this->assertStringContains('not mathematically valid for Persian', $persianResult['limitations']);
        $this->assertTrue($persianResult['words_count'] > 10);
        $this->assertTrue($persianResult['sentences_count'] >= 2);
    }

    /**
     * APEX-050: Heading Structure Hierarchy Checker.
     */
    public function testHeadingStructureChecker() {
        $checker = new HeadingAnalyzer();

        // 1. Valid single H1 structure with proper hierarchy
        $validHtml = "<h1>Main Article Title</h1><p>Introduction</p><h2>Section One</h2><p>Content</p><h3>Subsection</h3><p>Details</p><h2>Section Two</h2><p>End</p>";
        $validResult = $checker->analyze($validHtml);

        $this->assertTrue($validResult['is_valid']);
        $this->assertEquals(1, $validResult['h1_count']);
        $this->assertEquals(2, $validResult['h2_count']);
        $this->assertEquals(1, $validResult['h3_count']);
        $this->assertEquals(100, $validResult['score']);
        $this->assertEmpty($validResult['hierarchy_issues']);

        // 2. Nested HTML inside headings (e.g. <span><b>)
        $nestedHtml = "<h1><span>Title with <b>Bold</b> and &nbsp; entities</span></h1><h2>Subheading</h2>";
        $nestedResult = $checker->analyze($nestedHtml);
        $this->assertEquals(1, $nestedResult['h1_count']);
        $this->assertEquals('Title with Bold and entities', $nestedResult['headings'][0]['text']);

        // 3. Invalid structure: missing H1 and skipped heading levels (H2 -> H4)
        $invalidHtml = "<h2>Section Title</h2><p>Text</p><h4>Skipped Heading</h4><h2>Another Section</h2><h3>&nbsp;   </h3>";
        $invalidResult = $checker->analyze($invalidHtml);

        $this->assertFalse($invalidResult['is_valid']);
        $this->assertEquals(0, $invalidResult['h1_count']);
        $this->assertEquals(1, count($invalidResult['hierarchy_issues'])); // H2 -> H4 skipped
        $this->assertEquals(1, count($invalidResult['empty_headings']));  // Empty whitespace H3 detected
        $this->assertTrue($invalidResult['score'] < 70);

        // 4. Multiple H1 tags
        $multiH1Html = "<h1>Title One</h1><p>Content</p><h1>Title Two</h1>";
        $multiResult = $checker->analyze($multiH1Html);
        $this->assertEquals(2, $multiResult['h1_count']);
        $this->assertFalse($multiResult['is_valid']);
    }

    /**
     * APEX-051: Internal Link Graph Scanner & Inbound Counter.
     */
    public function testLinkGraphScanner() {
        $scanner = new LinkGraphScanner(null, 'https://mysite.com');

        $html = '<p>Welcome to our blog. Check out our <a href="/seo-guide/">internal SEO Guide</a> and our <a href="https://mysite.com/contact#form" rel="nofollow">Contact Page</a>. Also visit <a href="https://external-resource.org/docs" rel="sponsored ugc">External Docs</a>, or ignore <a href="#top">Top</a> and <a href="javascript:void(0)">Invalid</a>.</p>';

        $scan = $scanner->scan($html);

        $this->assertEquals(3, $scan['total_links']);
        $this->assertEquals(2, $scan['internal_links']);
        $this->assertEquals(1, $scan['external_links']);
        $this->assertEquals(1, $scan['nofollow_links']);

        // Inspect extracted links
        $links = $scan['links'];
        $this->assertEquals('https://mysite.com/seo-guide', $links[0]['url']);
        $this->assertEquals('internal', $links[0]['link_type']);
        $this->assertEquals('internal SEO Guide', $links[0]['anchor_text']);
        $this->assertFalse($links[0]['is_nofollow']);

        // Fragment was stripped from #form
        $this->assertEquals('https://mysite.com/contact', $links[1]['url']);
        $this->assertTrue($links[1]['is_nofollow']);

        // External with UGC and sponsored
        $this->assertEquals('https://external-resource.org/docs', $links[2]['url']);
        $this->assertEquals('external', $links[2]['link_type']);
        $this->assertTrue($links[2]['is_sponsored']);
        $this->assertTrue($links[2]['is_ugc']);

        // Custom internal hosts
        $scanner->setAllowedInternalHosts(['cdn.mysite.com']);
        $this->assertTrue($scanner->isInternalUrl('https://cdn.mysite.com/asset.js'));
        $this->assertFalse($scanner->isInternalUrl('https://other.com/asset.js'));
    }

    /**
     * APEX-052: Passive Voice Detection Engine.
     */
    public function testPassiveVoiceAnalyzer() {
        $analyzer = new PassiveVoiceAnalyzer();

        // 1. Active sentence vs Passive sentence
        $activeText = "The engineer built the website quickly. Our team writes high quality articles.";
        $activeResult = $analyzer->analyze($activeText);
        $this->assertEquals(0, $activeResult['passive_sentences']);
        $this->assertEquals(0.0, $activeResult['passive_ratio']);
        $this->assertTrue($activeResult['is_acceptable']);

        $passiveText = "The website was built by the engineer. The report was written by our team. The letter was sent yesterday.";
        $passiveResult = $analyzer->analyze($passiveText);
        $this->assertEquals(3, $passiveResult['passive_sentences']);
        $this->assertEquals(100.0, $passiveResult['passive_ratio']);
        $this->assertFalse($passiveResult['is_acceptable']);
        $this->assertEquals('error', $passiveResult['diagnostic']['status']);
        $this->assertEquals('heuristic_pattern_matching', $passiveResult['methodology']);

        // 2. False positive protection (Stative adjectives)
        $stativeText = "She was tired after the long journey. He is interested in modern SEO. They were married last year.";
        $stativeResult = $analyzer->analyze($stativeText);
        $this->assertEquals(0, $stativeResult['passive_sentences']);
        $this->assertEquals(0.0, $stativeResult['passive_ratio']);

        // 3. Configurable max passive ratio
        $analyzer->setMaxPassiveRatio(20.0);
        $this->assertEquals(20.0, $analyzer->getMaxPassiveRatio());
    }

    /**
     * APEX-053: Transition Word Coverage Analyzer.
     */
    public function testTransitionWordAnalyzer() {
        $analyzer = new TransitionWordAnalyzer();

        // 1. English text with transition words
        $englishText = "First, we should optimize server response times. Furthermore, images must be compressed. However, caching is the most critical element.";
        $enResult = $analyzer->analyze($englishText);

        $this->assertEquals(3, $enResult['total_sentences']);
        $this->assertEquals(3, $enResult['sentences_with_transitions']);
        $this->assertEquals(100.0, $enResult['transition_percentage']);
        $this->assertTrue($enResult['is_acceptable']);
        $this->assertTrue($enResult['category_breakdown']['addition'] > 0);
        $this->assertTrue($enResult['category_breakdown']['contrast'] > 0);
        $this->assertTrue($enResult['category_breakdown']['sequence_time'] > 0);

        // 2. Avoid substring false positives (e.g. "button" containing "but")
        $nonTransitionSentence = "Please click the blue button to submit your application.";
        $checkResult = $analyzer->findTransitionsInSentence($nonTransitionSentence, 'en');
        $this->assertFalse($checkResult['has_transition']);

        // 3. Persian text with transition words
        $persianText = "در ابتدا باید سرعت سرور را بررسی کنیم. علاوه بر این تصاویر باید بهینه شوند. با این حال کش مهمترین بخش است.";
        $faResult = $analyzer->analyze($persianText);

        $this->assertEquals('fa', $faResult['language']);
        $this->assertEquals(3, $faResult['total_sentences']);
        $this->assertEquals(3, $faResult['sentences_with_transitions']);
        $this->assertEquals(100.0, $faResult['transition_percentage']);
        $this->assertTrue($faResult['is_acceptable']);

        // 4. Configurable threshold
        $analyzer->setRecommendedThreshold(40.0);
        $this->assertEquals(40.0, $analyzer->getRecommendedThreshold());
    }

    /**
     * APEX-054: Paragraph & Sentence Length Analysis.
     */
    public function testTextStructureAnalyzer() {
        $analyzer = new TextStructureAnalyzer(null, 20, 10); // Custom short thresholds for testing

        $content = "<p>This is a short introductory paragraph.</p><p>This is a very long and overly complex sentence that intentionally exceeds ten words to trigger our sentence length analyzer warning for testing purposes.</p>";

        $result = $analyzer->analyze($content);

        $this->assertEquals(2, $result['total_paragraphs']);
        $this->assertEquals(2, $result['total_sentences']);
        $this->assertEquals(1, count($result['oversized_sentences']));
        $this->assertTrue($result['longest_sentence']['word_count'] > 10);
        $this->assertEquals(20, $analyzer->getMaxWordsPerParagraph());
        $this->assertEquals(10, $analyzer->getMaxWordsPerSentence());
    }

    /**
     * Phase 4 Master Subsystem Coordinator: ContentAnalyzer.
     */
    public function testMasterContentAnalyzerIntegration() {
        $coordinator = new ContentAnalyzer();

        $htmlContent = '
            <h1>High Speed WordPress SEO Guide</h1>
            <p>WordPress SEO optimization is essential for online success. In this guide, we explore how to build fast websites.</p>
            <h2>Speed Best Practices</h2>
            <p>Furthermore, image compression and database cleanups reduce latency significantly. Check out our <a href="/speed-tips/">Speed Tips Article</a> for more insights.</p>
            <h2>Conclusion</h2>
            <p>In conclusion, speed directly boosts search rankings and reader satisfaction.</p>
        ';

        $options = [
            'primary_keyword'     => 'WordPress SEO',
            'secondary_keywords'   => ['speed', 'latency'],
            'site_url'            => 'https://mysite.com'
        ];

        $analysis = $coordinator->analyzeContent($htmlContent, $options);

        // Versioning and metadata
        $this->assertEquals('1.0.0', $analysis['schema_version']);
        $this->assertEquals('1.0.0', $analysis['analyzer_version']);
        $this->assertStringContains('heuristic', $analysis['score_disclaimer']);

        // Assert all 7 capabilities are integrated and populated
        $this->assertTrue(isset($analysis['seo_score']));
        $this->assertTrue(isset($analysis['readability_score']));
        $this->assertTrue($analysis['seo_score'] > 0);
        $this->assertTrue($analysis['readability_score'] > 0);

        // Keyword analysis (APEX-048)
        $this->assertEquals('WordPress SEO', $analysis['keywords']['primary_keyword']['keyword']);
        $this->assertTrue($analysis['keywords']['primary_keyword']['count'] >= 1);

        // Readability (APEX-049)
        $this->assertTrue($analysis['readability']['words_count'] > 30);
        $this->assertEquals('en', $analysis['readability']['language']);

        // Headings (APEX-050)
        $this->assertEquals(1, $analysis['headings']['h1_count']);
        $this->assertEquals(2, $analysis['headings']['h2_count']);
        $this->assertTrue($analysis['headings']['is_valid']);

        // Links (APEX-051)
        $this->assertEquals(1, $analysis['links']['total_links']);
        $this->assertEquals(1, $analysis['links']['internal_links']);

        // Passive Voice (APEX-052)
        $this->assertTrue(isset($analysis['passive_voice']['passive_ratio']));

        // Transition Words (APEX-053)
        $this->assertTrue($analysis['transition_words']['sentences_with_transitions'] >= 2);

        // Text Structure (APEX-054)
        $this->assertEquals(4, $analysis['text_structure']['total_paragraphs']);

        // Dependency getters
        $this->assertInstanceOf(KeywordAnalyzer::class, $coordinator->getKeywordAnalyzer());
        $this->assertInstanceOf(ReadabilityScorer::class, $coordinator->getReadabilityScorer());
        $this->assertInstanceOf(HeadingAnalyzer::class, $coordinator->getHeadingAnalyzer());
        $this->assertInstanceOf(LinkGraphScanner::class, $coordinator->getLinkGraphScanner());
        $this->assertInstanceOf(PassiveVoiceAnalyzer::class, $coordinator->getPassiveVoiceAnalyzer());
        $this->assertInstanceOf(TransitionWordAnalyzer::class, $coordinator->getTransitionWordAnalyzer());
        $this->assertInstanceOf(TextStructureAnalyzer::class, $coordinator->getTextStructureAnalyzer());
    }
}
