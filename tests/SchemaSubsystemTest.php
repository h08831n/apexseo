<?php
namespace ApexSEO\Tests;

use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\SchemaGraphBuilder;
use ApexSEO\Schema\Validator\SchemaValidator;
use ApexSEO\Schema\Types\ArticleSchema;
use ApexSEO\Schema\Types\ProductSchema;
use ApexSEO\Schema\Types\FAQPageSchema;
use ApexSEO\Schema\Types\LocalBusinessSchema;
use ApexSEO\Schema\Types\OrganizationSchema;
use ApexSEO\Schema\Types\WebSiteSchema;
use ApexSEO\Schema\Types\RecipeSchema;
use ApexSEO\Schema\Types\JobPostingSchema;
use ApexSEO\Schema\Types\CourseSchema;
use ApexSEO\Schema\Types\EventSchema;
use ApexSEO\Schema\Types\SoftwareApplicationSchema;
use ApexSEO\Schema\Media\VideoObjectSchema;

class SchemaSubsystemTest extends TestCase {
    public function testSchemaRegistryDefaultTypes() {
        $registry = new SchemaRegistry();
        $this->assertNotNull($registry->getType('Article'));
        $this->assertNotNull($registry->getType('Product'));
        $this->assertNotNull($registry->getType('FAQPage'));
        $this->assertNotNull($registry->getType('LocalBusiness'));
        $this->assertNotNull($registry->getType('Organization'));
        $this->assertNotNull($registry->getType('WebSite'));
        $this->assertNotNull($registry->getType('Recipe'));
        $this->assertNotNull($registry->getType('JobPosting'));
        $this->assertNotNull($registry->getType('Course'));
        $this->assertNotNull($registry->getType('Event'));
        $this->assertNotNull($registry->getType('SoftwareApplication'));
        $this->assertNotNull($registry->getType('VideoObject'));
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

    public function testRecipeSchemaGeneration() {
        $schemaType = new RecipeSchema();
        $context = [
            'is_recipe'           => true,
            'title'               => 'Authentic Persian Saffron Rice',
            'canonical_url'       => 'https://example.com/recipe/saffron-rice/',
            'recipe_ingredients'  => ['2 cups Basmati rice', '1/2 tsp Saffron', '4 tbsp Butter'],
            'recipe_instructions' => ['Wash rice thoroughly.', 'Brew saffron in warm water.', 'Steam on low flame.'],
            'prep_time'           => 'PT20M',
            'cook_time'           => 'PT40M',
            'total_time'          => 'PT1H',
            'rating_value'        => 5.0,
            'review_count'        => 42,
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('Recipe', $data['@type']);
        $this->assertEquals('Authentic Persian Saffron Rice', $data['name']);
        $this->assertEquals(3, count($data['recipeIngredient']));
        $this->assertEquals(3, count($data['recipeInstructions']));
        $this->assertEquals('HowToStep', $data['recipeInstructions'][0]['@type']);
        $this->assertEquals(5.0, $data['aggregateRating']['ratingValue']);
    }

    public function testJobPostingSchemaGeneration() {
        $schemaType = new JobPostingSchema();
        $context = [
            'is_job_posting'      => true,
            'title'               => 'Principal Performance Engineer',
            'canonical_url'       => 'https://example.com/jobs/perf-eng/',
            'hiring_organization' => 'Apex Systems Inc',
            'base_salary_value'   => 185000,
            'currency'            => 'USD',
            'is_remote'           => true,
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('JobPosting', $data['@type']);
        $this->assertEquals('Principal Performance Engineer', $data['title']);
        $this->assertEquals('Apex Systems Inc', $data['hiringOrganization']['name']);
        $this->assertEquals('TELECOMMUTE', $data['jobLocationType']);
        $this->assertEquals(185000.0, $data['baseSalary']['value']['value']);
    }

    public function testCourseSchemaGeneration() {
        $schemaType = new CourseSchema();
        $context = [
            'is_course'              => true,
            'title'                  => 'Advanced WordPress Architecture',
            'canonical_url'          => 'https://example.com/courses/wp-arch/',
            'course_provider'        => 'Apex Academy',
            'educational_credential' => 'Master of Web Engineering Certificate',
            'price'                  => '499.00',
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('Course', $data['@type']);
        $this->assertEquals('Advanced WordPress Architecture', $data['name']);
        $this->assertEquals('Apex Academy', $data['provider']['name']);
        $this->assertEquals('499.00', $data['offers']['price']);
    }

    public function testEventSchemaGeneration() {
        $schemaType = new EventSchema();
        $context = [
            'is_event'           => true,
            'title'              => 'Global Web Performance Summit 2026',
            'canonical_url'      => 'https://example.com/events/summit-2026/',
            'event_start_date'   => '2026-10-15T09:00:00+00:00',
            'is_online_event'    => true,
            'event_stream_url'   => 'https://example.com/live/summit-2026',
            'price'              => '0.00',
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('Event', $data['@type']);
        $this->assertEquals('Global Web Performance Summit 2026', $data['name']);
        $this->assertEquals('https://schema.org/OnlineEventAttendanceMode', $data['eventAttendanceMode']);
        $this->assertEquals('VirtualLocation', $data['location']['@type']);
    }

    public function testSoftwareApplicationSchemaGeneration() {
        $schemaType = new SoftwareApplicationSchema();
        $context = [
            'is_software'      => true,
            'title'            => 'Apex Optimizer Pro',
            'canonical_url'    => 'https://example.com/software/apex-optimizer/',
            'software_version' => '2.4.0',
            'price'            => '79.00',
            'rating_value'     => 4.8,
            'review_count'     => 89,
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('SoftwareApplication', $data['@type']);
        $this->assertEquals('Apex Optimizer Pro', $data['name']);
        $this->assertEquals('2.4.0', $data['softwareVersion']);
        $this->assertEquals('79.00', $data['offers']['price']);
    }

    public function testVideoObjectSchemaGeneration() {
        $schemaType = new VideoObjectSchema();
        $context = [
            'has_video'         => true,
            'video_title'       => 'Deep Dive into PHP 8.4 Engine',
            'canonical_url'     => 'https://example.com/video/php-84-deep-dive/',
            'video_thumbnail'   => 'https://example.com/thumb.jpg',
            'video_content_url' => 'https://example.com/video.mp4',
            'video_duration'    => 'PT18M45S',
        ];

        $this->assertTrue($schemaType->isApplicable($context));
        $data = $schemaType->generate($context);

        $this->assertEquals('VideoObject', $data['@type']);
        $this->assertEquals('Deep Dive into PHP 8.4 Engine', $data['name']);
        $this->assertEquals('https://example.com/thumb.jpg', $data['thumbnailUrl']);
        $this->assertEquals('PT18M45S', $data['duration']);
    }

    public function testSchemaValidator() {
        $validator = new SchemaValidator();

        $validArticle = [
            '@type'    => 'Article',
            'headline' => 'Valid Headline',
        ];
        $this->assertEquals([], $validator->validate($validArticle));

        $invalidArticle = [
            '@type' => 'Article',
        ];
        $issues = $validator->validate($invalidArticle);
        $this->assertTrue(count($issues) > 0);

        $validRecipe = [
            '@type'              => 'Recipe',
            'name'               => 'Pancakes',
            'recipeIngredient'   => ['Flour', 'Milk', 'Eggs'],
            'recipeInstructions' => [['@type' => 'HowToStep', 'text' => 'Mix and cook']],
        ];
        $this->assertEquals([], $validator->validate($validRecipe));
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
