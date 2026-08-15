<?php
namespace ApexSEO\Tests;

use ApexSEO\Performance\Assets\CssMinifier;
use ApexSEO\Performance\Assets\JsMinifier;
use ApexSEO\Performance\Assets\HtmlMinifier;
use ApexSEO\Performance\Assets\DelayJsEngine;
use ApexSEO\Performance\Tweaks\ResourceHints;
use ApexSEO\Performance\Cache\StaticFileWriter;
use ApexSEO\Performance\Cache\SmartPurge;
use ApexSEO\Core\Environment\Server\DirectServerAdapter;

class PerformanceSubsystemTest extends TestCase {
    public function testCssMinification() {
        $minifier = new CssMinifier();
        $rawCss = '
            /* Comment */
            body {
                background-color: #ffffff;
                color: #333333;
            }
            .header {
                font-size: 16px;
            }
        ';

        $minified = $minifier->minify($rawCss);
        $this->assertEquals('body{background-color:#ffffff;color:#333333}.header{font-size:16px}', $minified);
    }

    public function testJsMinification() {
        $minifier = new JsMinifier();
        $rawJs = "
            /* Multi-line
               comment */
            // Single line comment
            var a = 10;
            var b = 20;
        ";

        $minified = $minifier->minify($rawJs);
        $this->assertStringNotContains('Multi-line', $minified);
        $this->assertStringNotContains('// Single line comment', $minified);
        $this->assertStringContains('var a = 10;', $minified);
    }

    public function testHtmlMinification() {
        $minifier = new HtmlMinifier();
        $rawHtml = '
            <!-- Top comment -->
            <div class="card">
                <h1>Hello World</h1>
                <p>Welcome to high performance SEO.</p>
            </div>
        ';

        $minified = $minifier->minify($rawHtml);
        $this->assertStringNotContains('<!-- Top comment -->', $minified);
        $this->assertStringContains('<h1>Hello World</h1>', $minified);
    }

    public function testDelayJsEngine() {
        $engine = new DelayJsEngine();
        $html = '<html><head></head><body><h1>Content</h1></body></html>';

        $processed = $engine->processHtml($html);
        $this->assertStringContains('apex-delay-js-loader', $processed);
        $this->assertStringContains('touchstart', $processed);
    }

    public function testResourceHints() {
        $hints = new ResourceHints();
        $hints->addDnsPrefetch('fonts.googleapis.com');
        $hints->addPreconnect('https://cdn.example.com');
        $hints->addPreload('https://example.com/font.woff2', 'font', ['type' => 'font/woff2', 'crossorigin' => true]);

        $html = $hints->renderHtml();
        $this->assertStringContains('<link rel="dns-prefetch" href="//fonts.googleapis.com" />', $html);
        $this->assertStringContains('<link rel="preconnect" href="https://cdn.example.com" crossorigin />', $html);
        $this->assertStringContains('<link rel="preload" href="https://example.com/font.woff2" as="font" type="font/woff2" crossorigin />', $html);
    }

    public function testStaticFileWriterAndSmartPurge() {
        $tempDir = sys_get_temp_dir() . '/apex_test_cache_' . uniqid();
        $writer = new StaticFileWriter($tempDir);
        $serverAdapter = new DirectServerAdapter();
        $purge = new SmartPurge($writer, $serverAdapter);

        $htmlContent = '<html><body>Cached Page</body></html>';
        $written = $writer->writeCache('https://example.com/test-page/', $htmlContent);
        $this->assertTrue($written);

        $cached = $writer->readCache('https://example.com/test-page/');
        $this->assertEquals($htmlContent, $cached);

        $purged = $purge->purge('https://example.com/test-page/');
        $this->assertTrue($purged);

        $cachedAfter = $writer->readCache('https://example.com/test-page/');
        $this->assertNull($cachedAfter);

        // Cleanup
        $writer->purgeAll();
        if (is_dir($tempDir)) {
            @rmdir($tempDir);
        }
    }
}
