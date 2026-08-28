<?php
namespace ApexSEO\Tests;

use ApexSEO\AI\LlmsTxt\LlmsTxtGenerator;
use ApexSEO\AI\SearchIntent\SearchIntentAnalyzer;
use ApexSEO\AI\Generators\MetadataAiGenerator;
use ApexSEO\SEO\Variables\VariableEngine;

class AiSubsystemTest extends TestCase {
    public function testLlmsTxtGeneration() {
        $gen = new LlmsTxtGenerator();
        $options = [
            'site_name'        => 'Apex Docs',
            'site_description' => 'Documentation and API reference for Apex SEO.',
            'sections'         => [
                'Core Architecture' => [
                    ['title' => 'Container Dependency Injection', 'url' => 'https://example.com/docs/di/', 'description' => 'PSR-11 compliant container'],
                    ['title' => 'Schema Registry', 'url' => 'https://example.com/docs/schema/', 'description' => '52 Structured data types'],
                ],
            ],
        ];

        $markdown = $gen->generateLlmsTxt($options);
        $this->assertStringContains('# Apex Docs', $markdown);
        $this->assertStringContains('## Core Architecture', $markdown);
        $this->assertStringContains('[Container Dependency Injection](https://example.com/docs/di/)', $markdown);
    }

    public function testSearchIntentAnalyzer() {
        $analyzer = new SearchIntentAnalyzer();

        $infoResult = $analyzer->analyze('How to optimize Core Web Vitals in 2026');
        $this->assertEquals('informational', $infoResult['primary_intent']);
        $this->assertTrue($infoResult['confidence'] >= 0.70);

        $transResult = $analyzer->analyze('Buy high performance WordPress hosting discount');
        $this->assertEquals('transactional', $transResult['primary_intent']);

        $commResult = $analyzer->analyze('Best SEO plugins vs ApexSEO comparison');
        $this->assertEquals('commercial', $commResult['primary_intent']);
    }

    public function testMetadataAiGenerator() {
        $varEngine = new VariableEngine();
        $ai = new MetadataAiGenerator($varEngine);

        $content = 'High performance websites load in under 1 second and rank significantly better on Google search results.';
        $titles = $ai->generateTitleCandidates($content, 'core web vitals');

        $this->assertTrue(count($titles) >= 3);
        $this->assertStringContains('Core Web Vitals', $titles[0]);

        $descriptions = $ai->generateDescriptionCandidates($content, 'core web vitals');
        $this->assertTrue(count($descriptions) >= 2);
        $this->assertStringContains('core web vitals', $descriptions[0]);
    }
}
