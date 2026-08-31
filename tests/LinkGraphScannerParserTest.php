<?php
namespace ApexSEO\Tests;

use ApexSEO\SEO\Analysis\LinkGraphScanner;

/**
 * Dedicated parser & edge-case test suite for LinkGraphScanner.
 */
class LinkGraphScannerParserTest extends TestCase {
    /**
     * @var LinkGraphScanner
     */
    private $scanner;

    public function setUp(): void {
        parent::setUp();
        $this->scanner = new LinkGraphScanner(null, 'https://example.com');
    }

    /**
     * Test double-quoted href attribute parsing.
     */
    public function testDoubleQuotedHref() {
        $html = '<a href="https://example.com/double-quoted" title="Test">Double Quoted</a>';
        $result = $this->scanner->scan($html);

        $this->assertEquals(1, $result['total_links']);
        $this->assertEquals(1, $result['internal_links']);
        $this->assertEquals('https://example.com/double-quoted', $result['links'][0]['url']);
        $this->assertEquals('Double Quoted', $result['links'][0]['anchor_text']);
    }

    /**
     * Test single-quoted href attribute parsing.
     */
    public function testSingleQuotedHref() {
        $html = "<a href='https://example.com/single-quoted' target='_blank'>Single Quoted</a>";
        $result = $this->scanner->scan($html);

        $this->assertEquals(1, $result['total_links']);
        $this->assertEquals(1, $result['internal_links']);
        $this->assertEquals('https://example.com/single-quoted', $result['links'][0]['url']);
        $this->assertEquals('Single Quoted', $result['links'][0]['anchor_text']);
    }

    /**
     * Test relative internal link parsing (e.g. /blog/post-1 and sub/page).
     */
    public function testRelativeInternalLink() {
        $html = '<p>Check out our <a href="/blog/post-1">Post 1</a> and <a href="docs/install">Docs</a></p>';
        $result = $this->scanner->scan($html);

        $this->assertEquals(2, $result['total_links']);
        $this->assertEquals(2, $result['internal_links']);
        $this->assertEquals('https://example.com/blog/post-1', $result['links'][0]['url']);
        $this->assertEquals('https://example.com/docs/install', $result['links'][1]['url']);
    }

    /**
     * Test absolute same-site internal link with fragment stripping.
     */
    public function testAbsoluteSameSiteInternalLink() {
        $html = '<a href="https://example.com/features#pricing">Features and Pricing</a>';
        $result = $this->scanner->scan($html);

        $this->assertEquals(1, $result['total_links']);
        $this->assertEquals(1, $result['internal_links']);
        $this->assertEquals(0, $result['external_links']);
        $this->assertEquals('https://example.com/features', $result['links'][0]['url']);
        $this->assertEquals('Features and Pricing', $result['links'][0]['anchor_text']);
    }

    /**
     * Test external link classification and attributes (rel=sponsored, rel=ugc, rel=nofollow).
     */
    public function testExternalLink() {
        $html = '<a href="https://partner-site.org/referral" rel="nofollow sponsored">External Partner</a>';
        $result = $this->scanner->scan($html);

        $this->assertEquals(1, $result['total_links']);
        $this->assertEquals(0, $result['internal_links']);
        $this->assertEquals(1, $result['external_links']);
        $this->assertEquals(1, $result['nofollow_links']);
        $this->assertEquals('external', $result['links'][0]['link_type']);
        $this->assertTrue($result['links'][0]['is_nofollow']);
        $this->assertTrue($result['links'][0]['is_sponsored']);
        $this->assertFalse($result['links'][0]['is_ugc']);
    }

    /**
     * Test anchor text containing nested HTML elements (<span>, <strong>, <em>, <img>).
     */
    public function testAnchorTextContainingNestedHtml() {
        $html = '<a href="/nested-link"><span>Featured</span>: <strong>Super <em>SEO</em> Guide</strong></a>';
        $result = $this->scanner->scan($html);

        $this->assertEquals(1, $result['total_links']);
        $this->assertEquals('Featured: Super SEO Guide', $result['links'][0]['anchor_text']);
    }

    /**
     * Test malformed anchor markup and non-standard HTML.
     */
    public function testMalformedAnchorMarkup() {
        $html = '<div><a href="https://example.com/valid">Valid</a> <a href="">Empty</a> <a href="#top">Anchor Only</a> <a href="javascript:void(0)">JS Link</a></div>';
        $result = $this->scanner->scan($html);

        // Empty, #top, and javascript: links should be safely ignored
        $this->assertEquals(1, $result['total_links']);
        $this->assertEquals('https://example.com/valid', $result['links'][0]['url']);
        $this->assertEquals('Valid', $result['links'][0]['anchor_text']);
    }

    /**
     * Test case-insensitive tag and attribute names (<A HREF=...> and <A CLASS=... HREF=...>).
     */
    public function testCaseInsensitiveAnchorMarkup() {
        $html = '<A CLASS="btn btn-primary" HREF="HTTPS://EXAMPLE.COM/CASE-INSENSITIVE">CLICK HERE</A>';
        $result = $this->scanner->scan($html);

        $this->assertEquals(1, $result['total_links']);
        $this->assertEquals(1, $result['internal_links']);
        $this->assertEquals('HTTPS://EXAMPLE.COM/CASE-INSENSITIVE', $result['links'][0]['url']);
        $this->assertEquals('CLICK HERE', $result['links'][0]['anchor_text']);
    }
}
