<?php
namespace ApexSEO\Tests;

/**
 * Real WordPress Runtime End-to-End Integration Suite.
 */
class RealWordPressRuntimeTest extends TestCase {
    protected $serverUrl = 'http://127.0.0.1:8080';

    protected function httpGet($path) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($res, 0, $headerSize);
        $body = substr($res, $headerSize);
        curl_close($ch);

        return [
            'status'  => $info['http_code'],
            'headers' => $headers,
            'body'    => $body,
        ];
    }

    public function testRealWordPressHomepage() {
        $res = $this->httpGet('/');
        $this->assertEquals(200, $res['status']);
        $this->assertStringContainsString('This site is optimized with the Apex SEO Platform', $res['body']);
        $this->assertStringContainsString('rel="canonical"', $res['body']);
    }

    public function testRealRobotsTxtOutput() {
        $res = $this->httpGet('/robots.txt');
        $this->assertEquals(200, $res['status']);
        $this->assertStringContainsString('User-agent:', $res['body']);
        $this->assertStringContainsString('Sitemap:', $res['body']);
    }

    public function testRealSitemapIndex() {
        $res = $this->httpGet('/sitemap_index.xml');
        $this->assertEquals(200, $res['status']);
        $this->assertStringContainsString('<sitemapindex', $res['body']);
        $this->assertStringContainsString('post-sitemap.xml', $res['body']);
    }

    public function testRealLlmsTxt() {
        $res = $this->httpGet('/llms.txt');
        $this->assertEquals(200, $res['status']);
        $this->assertStringContainsString('# Apex SEO Testbed', $res['body']);
    }

    public function testReal404XRobotsTagHeader() {
        $res = $this->httpGet('/non-existent-real-404-check/');
        $this->assertEquals(404, $res['status']);
        $this->assertStringContainsString('X-Robots-Tag: noindex, nofollow', $res['headers']);
    }
}
