<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real Phase 4 Content Analysis Integration Test.
 *
 * Tests post insertion lifecycle, analysis triggering, and database persistence.
 */
class RealPhase4ContentAnalysisTest extends TestCase {
    public function testRealEnglishPostSaveAndAnalysisPersistence() {
        if (!function_exists('wp_insert_post')) {
            $this->markTestSkipped('Real WordPress runtime not available.');
        }

        global $wpdb;

        $postData = [
            'post_title'   => 'CI Automated Guide to Distributed Cloud Computing Architecture',
            'post_content' => '<h2>Introduction to Cloud Scale</h2><p>In this guide to distributed cloud computing, we review scalable systems. Furthermore, automated replication improves system resilience.</p><h3>Database Performance</h3><p>Consequently, sharding data provides massive scalability. See our <a href="/sample-post/">tuning guide</a> and external <a href="https://example.com/spec">standards</a>.</p>',
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ];

        $postId = wp_insert_post($postData);
        $this->assertGreaterThan(0, $postId, 'Post insertion must succeed.');

        update_post_meta($postId, '_apexseo_primary_keyword', 'cloud computing');

        // Trigger save_post hook to execute ContentAnalysisService
        $post = get_post($postId);
        do_action('save_post', $postId, $post, true);

        // Assert indexables persistence with content analysis data
        $indexable = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wp_apex_indexables WHERE object_id = %d AND object_type = 'post'",
            $postId
        ), ARRAY_A);

        $this->assertNotNull($indexable, 'Indexable record must exist in wp_apex_indexables.');
        $this->assertEquals('cloud computing', $indexable['primary_focus_keyword']);
        $this->assertNotNull($indexable['readability_score']);
        $this->assertNotNull($indexable['keyword_density']);
        $this->assertNotEmpty($indexable['content_analysis']);

        // Assert link graph extraction
        $linksCount = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM wp_apex_links WHERE post_id = %d",
            $postId
        ));
        $this->assertGreaterThanOrEqual(2, $linksCount, 'Link extractor must record extracted internal & external links.');

        // Cleanup
        wp_delete_post($postId, true);
    }

    public function testRealPersianPostSaveAndAnalysisPersistence() {
        if (!function_exists('wp_insert_post')) {
            $this->markTestSkipped('Real WordPress runtime not available.');
        }

        global $wpdb;

        $postData = [
            'post_title'   => 'راهنمای جامع سئو و بهینه‌سازی موتورهای جستجو',
            'post_content' => '<h2>مقدمه سئو و بهینه‌سازی</h2><p>بهینه‌سازی وب‌سایت برای موتورهای جستجو اهمیت فراوانی دارد. بنابراین، ساختار درست محتوا و لینک‌های داخلی بسیار کلیدی است.</p>',
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ];

        $postId = wp_insert_post($postData);
        $this->assertGreaterThan(0, $postId);

        update_post_meta($postId, '_apexseo_primary_keyword', 'بهینه‌سازی');

        $post = get_post($postId);
        do_action('save_post', $postId, $post, true);

        $indexable = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wp_apex_indexables WHERE object_id = %d AND object_type = 'post'",
            $postId
        ), ARRAY_A);

        $this->assertNotNull($indexable, 'Persian analysis record must exist in wp_apex_indexables.');
        $this->assertEquals('بهینه‌سازی', $indexable['primary_focus_keyword']);
        $this->assertNotNull($indexable['readability_score']);

        wp_delete_post($postId, true);
    }
}
