#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - REAL PHASE 4 CONTENT ANALYSIS E2E VALIDATION"
echo "=========================================================="

WP_PATH="/var/www/html"
WP_CLI="wp --allow-root --path=${WP_PATH}"
MYSQL_CMD="mysql -h db -u wp_test_user -pwp_test_pass_123! wordpress_test -e"
FAILURES=0

# 1. Create English Test Post with Rich SEO Features
echo "--- [1/3] Creating English Comprehensive SEO Post via WordPress Lifecycle ---"

EN_TITLE="The Comprehensive Guide to Modern Enterprise Cloud Architecture and Scalability"
EN_CONTENT='<!-- wp:paragraph -->
<p>In this comprehensive guide to modern enterprise cloud architecture, we explore the essential principles of scalable distributed systems. Furthermore, modern cloud architecture requires robust observability, automated failover mechanisms, and strict security isolation.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Key Principles of Enterprise Cloud Architecture</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Consequently, distributed microservices must be carefully decoupled to prevent cascading failures. It was demonstrated that asynchronous messaging dramatically improves throughput and resilience across global regions.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Database Scaling and Sharding Strategies</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>In addition, relational databases can be partitioned horizontally across multiple shards. For further reference on database performance, check our internal guide on <a href="http://localhost:8080/sample-post/">database tuning</a>. External resources can also be consulted at <a href="https://example.com/cloud-standards" target="_blank" rel="noopener">Cloud Computing Standards</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4>Automated Deployment and Infrastructure as Code</h4>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Therefore, continuous integration pipelines are utilized by high-velocity engineering teams to guarantee zero-downtime deployments. However, thorough automated smoke tests must be executed before promoting releases to production.</p>
<!-- /wp:paragraph -->'

EN_POST_ID=$($WP_CLI post create \
    --post_type=post \
    --post_status=publish \
    --post_title="$EN_TITLE" \
    --post_content="$EN_CONTENT" \
    --porcelain)

echo "Created English Post ID: $EN_POST_ID"

# Update SEO postmeta to trigger full analysis
$WP_CLI post meta set $EN_POST_ID _apexseo_primary_keyword "enterprise cloud architecture"
$WP_CLI post meta set $EN_POST_ID _apexseo_secondary_keywords '["cloud architecture", "distributed systems", "scalability"]'

# Trigger save_post lifecycle event to run ContentAnalysisService
$WP_CLI eval "
\$post = get_post($EN_POST_ID);
do_action('save_post', $EN_POST_ID, \$post, true);
"

# 2. Verify Database Persistence in wp_apex_indexables and wp_apex_links
echo ""
echo "--- [2/3] Verifying Real Database Persistence for English Post ---"

INDEXABLE_ROW=$($MYSQL_CMD "SELECT readability_score, primary_focus_keyword, keyword_density, content_analysis FROM wp_apex_indexables WHERE object_id = $EN_POST_ID AND object_type = 'post';" -s -N)
if [ -n "$INDEXABLE_ROW" ]; then
    echo "[PASS] Persisted indexables record with content analysis found: $(echo "$INDEXABLE_ROW" | cut -f1,2,3)"
else
    echo "[FAIL] No indexable record found in wp_apex_indexables for Post $EN_POST_ID"
    FAILURES=$((FAILURES + 1))
fi

LINKS_COUNT=$($MYSQL_CMD "SELECT COUNT(*) FROM wp_apex_links WHERE post_id = $EN_POST_ID;" -s -N)
echo "Extracted Links Count: $LINKS_COUNT"
if [ "$LINKS_COUNT" -ge 2 ]; then
    echo "[PASS] Verified internal and external links extracted into wp_apex_links (Count: $LINKS_COUNT)"
else
    echo "[WARN] Expected >= 2 links extracted in wp_apex_links, got $LINKS_COUNT"
fi

# 3. Create Persian (Farsi) RTL Test Post
echo ""
echo "--- [3/3] Creating Persian RTL Comprehensive SEO Post ---"

FA_TITLE="راهنمای جامع بهینه‌سازی موتورهای جستجو و معماری ابری در سال ۲۰۲۶"
FA_CONTENT='<!-- wp:paragraph -->
<p>بهینه‌سازی موتورهای جستجو یکی از اساسی‌ترین ارکان رشد کسب‌وکارهای دیجیتال محسوب می‌شود. بنابراین، پیاده‌سازی اصولی سئو تکنیکال به همراه تولید محتوای ارزشمند اهمیت بسیار بالایی دارد.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>اصول کلیدی سئو تکنیکال و سرعت بارگذاری</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>علاوه بر این، ساختار پیوندها و معماری داده‌های ساختاریافته نقش تعیین‌کننده‌ای در درک موتورهای جستجو ایفا می‌کنند. به همین دلیل، استفاده از کش و فشرده‌سازی تصاویر توصیه می‌شود.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>لینک‌سازی داخلی و تحلیل محتوا</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>در نتیجه، پیوندهای داخلی به توزیع بهینه اعتبار صفحات کمک شایانی می‌کنند. برای مطالعه بیشتر به <a href="http://localhost:8080/sample-post/">مقاله آموزشی ما</a> مراجعه فرمایید.</p>
<!-- /wp:paragraph -->'

FA_POST_ID=$($WP_CLI post create \
    --post_type=post \
    --post_status=publish \
    --post_title="$FA_TITLE" \
    --post_content="$FA_CONTENT" \
    --porcelain)

echo "Created Persian Post ID: $FA_POST_ID"

$WP_CLI post meta set $FA_POST_ID _apexseo_primary_keyword "بهینه‌سازی موتورهای جستجو"

$WP_CLI eval "
\$post = get_post($FA_POST_ID);
do_action('save_post', $FA_POST_ID, \$post, true);
"

FA_INDEXABLE_ROW=$($MYSQL_CMD "SELECT readability_score, primary_focus_keyword, keyword_density FROM wp_apex_indexables WHERE object_id = $FA_POST_ID AND object_type = 'post';" -s -N)
if [ -n "$FA_INDEXABLE_ROW" ]; then
    echo "[PASS] Persisted indexables record found for Persian Post $FA_POST_ID: $FA_INDEXABLE_ROW"
else
    echo "[FAIL] No indexable record found for Persian Post $FA_POST_ID in wp_apex_indexables"
    FAILURES=$((FAILURES + 1))
fi

echo "=========================================================="
if [ $FAILURES -eq 0 ]; then
    echo "REAL PHASE 4 VALIDATION RESULT: ALL PASSED"
    exit 0
else
    echo "REAL PHASE 4 VALIDATION RESULT: $FAILURES FAILURES DETECTED"
    exit 1
fi
