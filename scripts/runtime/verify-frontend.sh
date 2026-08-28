#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - REAL FRONTEND HTTP & PHASE 5A/5B VALIDATION"
echo "=========================================================="

SITE_URL="http://localhost:8080"
FAILURES=0

test_http_header_and_body() {
    local NAME="$1"
    local PATH="$2"
    local EXPECTED_CODE="$3"
    local EXPECTED_HEADER="$4"
    local EXPECTED_BODY="$5"

    echo -n "Testing $NAME ($PATH) ... "

    local HEADERS_FILE="/tmp/fe_headers.txt"
    local BODY_FILE="/tmp/fe_body.txt"

    local HTTP_CODE
    HTTP_CODE=$(curl -s -D "$HEADERS_FILE" -o "$BODY_FILE" -w "%{http_code}" "${SITE_URL}${PATH}")

    if [ "$HTTP_CODE" -ne "$EXPECTED_CODE" ]; then
        echo "[FAIL] Expected HTTP $EXPECTED_CODE, got HTTP $HTTP_CODE"
        FAILURES=$((FAILURES + 1))
        return
    fi

    if [ -n "$EXPECTED_HEADER" ] && ! grep -qi "$EXPECTED_HEADER" "$HEADERS_FILE"; then
        echo "[FAIL] Expected header '$EXPECTED_HEADER' missing"
        echo "Received headers:"
        cat "$HEADERS_FILE"
        FAILURES=$((FAILURES + 1))
        return
    fi

    if [ -n "$EXPECTED_BODY" ] && ! grep -qi "$EXPECTED_BODY" "$BODY_FILE"; then
        echo "[FAIL] Expected body substring '$EXPECTED_BODY' missing"
        echo "Body snippet: $(head -c 300 "$BODY_FILE")"
        FAILURES=$((FAILURES + 1))
        return
    fi

    echo "[PASS] (HTTP $HTTP_CODE)"
}

# 1. Real Phase 5A: Frontend Head Meta & Schema Rendering
echo "--- [1/3] Phase 5A: Frontend Head, OpenGraph, Twitter, and JSON-LD ---"
test_http_header_and_body "Homepage HTTP 200" "/" 200 "" "<title>"
test_http_header_and_body "Canonical Link in Head" "/" 200 "" 'rel="canonical"'
test_http_header_and_body "OpenGraph Title Tag" "/" 200 "" 'property="og:title"'
test_http_header_and_body "Twitter Card Tag" "/" 200 "" 'name="twitter:card"'
test_http_header_and_body "JSON-LD Structured Data" "/" 200 "" 'application/ld+json'

# 2. Real Phase 5B: Robots.txt and AI Crawler Directives
echo ""
echo "--- [2/3] Phase 5B: Robots.txt, Sitemaps, and LLMs.txt ---"
test_http_header_and_body "Robots.txt Output" "/robots.txt" 200 "Content-Type: text/plain" "User-agent:"
test_http_header_and_body "Sitemap Index XML" "/sitemap_index.xml" 200 "Content-Type: " "sitemapindex"
test_http_header_and_body "LLMs.txt AI Manifest" "/llms.txt" 200 "Content-Type: text/plain" ""

# 3. Real Phase 5B: X-Robots-Tag Headers & Category Redirects
echo ""
echo "--- [3/3] Phase 5B: X-Robots-Tag Headers & Category Permalinks ---"
test_http_header_and_body "404 Not Found X-Robots-Tag" "/definitely-nonexistent-page-404-check/" 404 "X-Robots-Tag: noindex" ""
test_http_header_and_body "Search Query X-Robots-Tag" "/?s=apexseo_test_query" 200 "X-Robots-Tag: noindex" ""
test_http_header_and_body "RSS Feed X-Robots-Tag" "/feed/" 200 "X-Robots-Tag: noindex" ""

# Category Base Stripping Redirect Test
WP_PATH="/var/www/html"
WP_CLI="wp --allow-root --path=${WP_PATH}"
CAT_ID=$($WP_CLI term create category "Cloud Computing" --slug="cloud-computing" --porcelain 2>/dev/null || $WP_CLI term get category cloud-computing --field=term_id 2>/dev/null || echo "")

if [ -n "$CAT_ID" ]; then
    echo "Created/Found Category ID: $CAT_ID (cloud-computing)"
    # Test category base redirect if enabled in config
    test_http_header_and_body "Direct Category Archive" "/cloud-computing/" 200 "" ""
fi

echo "=========================================================="
if [ $FAILURES -eq 0 ]; then
    echo "REAL FRONTEND VALIDATION RESULT: ALL PASSED"
    exit 0
else
    echo "REAL FRONTEND VALIDATION RESULT: $FAILURES FAILURES DETECTED"
    exit 1
fi
