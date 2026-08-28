<?php
namespace ApexSEO\Tests;

use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Meta\TitlePresenter;
use ApexSEO\SEO\Meta\DescriptionPresenter;
use ApexSEO\SEO\Meta\CanonicalPresenter;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;
use ApexSEO\SEO\Meta\MetaTagManager;
use ApexSEO\SEO\Breadcrumbs\BreadcrumbGenerator;
use ApexSEO\SEO\Sitemap\SitemapGenerator;
use ApexSEO\SEO\Redirects\RedirectManager;
use ApexSEO\Core\Database\DatabaseManager;

class SeoSubsystemTest extends TestCase {
    public function testVariableEngineDefaultTokens() {
        $engine = new VariableEngine();
        $context = [
            'title'       => 'High-Speed SEO Guide',
            'author_name' => 'John Doe',
            'category'    => 'Performance',
            'sep'         => '|',
        ];

        $template = '%%title%% %%sep%% %%author_name%%';
        $result = $engine->replace($template, $context);
        $this->assertEquals('High-Speed SEO Guide | John Doe', $result);

        $customRegistered = $engine->registerVariable('custom_discount', function($ctx) {
            return '50% OFF';
        });
        $this->assertEquals('Special Deal: 50% OFF', $engine->replace('Special Deal: %%custom_discount%%', $context));
    }

    public function testTitleAndDescriptionPresenters() {
        $engine = new VariableEngine();
        $titlePresenter = new TitlePresenter($engine);
        $descPresenter = new DescriptionPresenter($engine);

        $context = [
            'page_type' => 'single',
            'title'     => '10 Speed Tips',
            'excerpt'   => 'Learn the fastest ways to optimize your website for search engines.',
            'sep'       => '·',
        ];

        $title = $titlePresenter->render($context);
        $this->assertStringContains('10 Speed Tips', $title);
        $this->assertStringContains('·', $title);

        $desc = $descPresenter->render($context);
        $this->assertStringContains('Learn the fastest ways to optimize', $desc);
    }

    public function testCanonicalAndRobotsPresenters() {
        $canonicalPresenter = new CanonicalPresenter();
        $robotsPresenter = new RobotsPresenter();

        $dirtyUrl = 'https://example.com/blog/speed-guide/?utm_source=facebook&fbclid=12345&tag=seo';
        $clean = $canonicalPresenter->cleanUrl($dirtyUrl);
        $this->assertEquals('https://example.com/blog/speed-guide/?tag=seo', $clean);

        $robots = $robotsPresenter->render(['robots_noindex' => true, 'robots_nofollow' => true]);
        $this->assertStringContains('noindex', $robots);
        $this->assertStringContains('nofollow', $robots);
    }

    public function testOpenGraphAndTwitterCardPresenters() {
        $engine = new VariableEngine();
        $ogPresenter = new OpenGraphPresenter($engine);
        $twPresenter = new TwitterCardPresenter($engine);

        $context = [
            'title'          => 'Apex SEO Architecture',
            'description'    => 'Enterprise SEO engine for WordPress.',
            'canonical_url'  => 'https://example.com/apex-seo/',
            'featured_image' => 'https://example.com/banner.jpg',
        ];

        $ogHtml = $ogPresenter->render($context);
        $this->assertStringContains('og:title', $ogHtml);
        $this->assertStringContains('Apex SEO Architecture', $ogHtml);
        $this->assertStringContains('og:image', $ogHtml);

        $twHtml = $twPresenter->render($context);
        $this->assertStringContains('twitter:card', $twHtml);
        $this->assertStringContains('summary_large_image', $twHtml);
    }

    public function testBreadcrumbGenerator() {
        $breadcrumbs = new BreadcrumbGenerator();
        $context = [
            'home_title'    => 'Home',
            'category'      => 'WordPress Tutorials',
            'title'         => 'Optimizing Core Web Vitals',
            'canonical_url' => 'https://example.com/wordpress/cwv/',
        ];

        $html = $breadcrumbs->renderHtml($context);
        $this->assertStringContains('schema.org/BreadcrumbList', $html);
        $this->assertStringContains('WordPress Tutorials', $html);
        $this->assertStringContains('Optimizing Core Web Vitals', $html);

        $schema = $breadcrumbs->generateSchema($context);
        $this->assertEquals('BreadcrumbList', $schema['@type']);
        $this->assertEquals(3, count($schema['itemListElement']));
    }

    public function testSitemapGenerator() {
        $sitemap = new SitemapGenerator();
        $urls = [
            [
                'loc'        => 'https://example.com/post-1/',
                'lastmod'    => '2026-08-15T12:00:00+00:00',
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ],
        ];

        $xml = $sitemap->renderUrlSitemap($urls);
        $this->assertStringContains('<urlset', $xml);
        $this->assertStringContains('<loc>https://example.com/post-1/</loc>', $xml);
        $this->assertStringContains('<priority>0.8</priority>', $xml);
    }

    public function testRedirectManager() {
        $db = new DatabaseManager();
        $redirects = new RedirectManager($db);

        $redirects->addRedirect('/old-speed-guide/', '/new-speed-guide/', 301);
        $match = $redirects->matchRedirect('/old-speed-guide/');

        $this->assertNotNull($match);
        $this->assertEquals('/new-speed-guide/', $match['target']);
        $this->assertEquals(301, $match['status']);

        $noMatch = $redirects->matchRedirect('/unknown-path/');
        $this->assertNull($noMatch);
    }
}
