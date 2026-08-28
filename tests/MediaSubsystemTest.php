<?php
namespace ApexSEO\Tests;

use ApexSEO\Media\LazyLoad\PlaceholderGenerator;
use ApexSEO\Media\LazyLoad\ImageLazyLoader;
use ApexSEO\Media\Optimizer\LcpOptimizer;

class MediaSubsystemTest extends TestCase {
    public function testSvgPlaceholderGenerator() {
        $gen = new PlaceholderGenerator();
        $dataUri = $gen->generateSvgPlaceholder(800, 600, '#e2e8f0');

        $this->assertStringContains('data:image/svg+xml;base64,', $dataUri);
    }

    public function testImageLazyLoader() {
        $gen = new PlaceholderGenerator();
        $loader = new ImageLazyLoader($gen);

        $html = '<div class="content"><img src="img1.jpg" alt="Hero"><img src="img2.jpg" alt="Second"></div>';
        $processed = $loader->processHtml($html, 1);

        // First image should be eager / high fetchpriority (LCP optimization)
        $this->assertStringContains('loading="eager"', $processed);
        $this->assertStringContains('fetchpriority="high"', $processed);

        // Second image should be lazy
        $this->assertStringContains('loading="lazy"', $processed);
    }

    public function testLcpOptimizer() {
        $optimizer = new LcpOptimizer();
        $html = '<article><img src="feature.png" alt="Featured" /></article>';

        $optimized = $optimizer->optimizeLcpImages($html);
        $this->assertStringContains('fetchpriority="high"', $optimized);
    }
}
