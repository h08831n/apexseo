<?php
namespace ApexSEO\Tests;

use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\SchemaGraphBuilder;
use ApexSEO\Schema\Types\ArticleSchema;
use ApexSEO\Schema\Types\ProductSchema;
use ApexSEO\Schema\Types\FAQPageSchema;
use ApexSEO\Schema\Types\LocalBusinessSchema;
use ApexSEO\Schema\Types\OrganizationSchema;
use ApexSEO\Schema\Types\WebSiteSchema;

class SchemaSubsystemTest extends TestCase {
    public function testSchemaRegistryDefaultTypes() {
        $registry = new SchemaRegistry();
        $this->assertNotNull($registry->getType('Article'));
        $this->assertNotNull($registry->getType('Product'));
        $this->assertNotNull($registry->getType('FAQPage'));
        $this->assertNotNull($registry->getType('LocalBusiness'));
        $this->assertNotNull($registry->getType('Organization'));
        $this->assertNotNull($registry->getType('WebSite'));
    }

    public function testArticleSchemaGeneration() {
        $schemaType = new ArticleSchema('Article');
        $context = [
            'page_type'      => 'single',
            'title'          => 'Speed Benchmark 2026',
            'canonical_url'  => 'https://example.com/speed-2026/',
            'author_name'    => 'Sarah Connor',
            'featured_image' => 'https://example.com/hero.jpg',
            'description'    => 'Full performance breakdown.',
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('Article', $data['@type']);
        $this->assertEquals('Speed Benchmark 2026', $data['headline']);
        $this->assertEquals('Sarah Connor', $data['author']['name']);
        $this->assertEquals('https://example.com/hero.jpg', $data['image']['url']);
    }

    public function testProductSchemaGeneration() {
        $schemaType = new ProductSchema();
        $context = [
            'is_product'     => true,
            'title'          => 'Apex Pro SEO Tool',
            'canonical_url'  => 'https://example.com/product/apex-pro/',
            'price'          => '99.00',
            'currency'       => 'USD',
            'in_stock'       => true,
            'rating_value'   => 4.9,
            'review_count'   => 120,
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('Product', $data['@type']);
        $this->assertEquals('99.00', $data['offers']['price']);
        $this->assertEquals('https://schema.org/InStock', $data['offers']['availability']);
        $this->assertEquals(4.9, $data['aggregateRating']['ratingValue']);
    }

    public function testFaqPageSchemaGeneration() {
        $schemaType = new FAQPageSchema();
        $context = [
            'faq_items' => [
                ['question' => 'Is Apex SEO lightweight?', 'answer' => 'Yes, zero bloat modular code.'],
                ['question' => 'Does it support LiteSpeed cache?', 'answer' => 'Yes, native ESI and LSCache purge.'],
            ],
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('FAQPage', $data['@type']);
        $this->assertEquals(2, count($data['mainEntity']));
        $this->assertEquals('Is Apex SEO lightweight?', $data['mainEntity'][0]['name']);
    }

    public function testSchemaGraphBuilderOutput() {
        $registry = new SchemaRegistry();
        $builder = new SchemaGraphBuilder($registry);

        $context = [
            'page_type'     => 'single',
            'title'         => 'Test Graph Article',
            'canonical_url' => 'https://example.com/graph-article/',
        ];

        $graph = $builder->buildGraph($context);
        $this->assertEquals('https://schema.org', $graph['@context']);
        $this->assertTrue(is_array($graph['@graph']));
        $this->assertTrue(count($graph['@graph']) >= 2); // Article + WebSite

        $scriptHtml = $builder->renderScript($context);
        $this->assertStringContains('<script type="application/ld+json"', $scriptHtml);
        $this->assertStringContains('Test Graph Article', $scriptHtml);
    }
}
