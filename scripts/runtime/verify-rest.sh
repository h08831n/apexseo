#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - REAL REST API RUNTIME VALIDATION"
echo "=========================================================="

REST_BASE="http://localhost:8080/wp-json/apexseo/v1"
ADMIN_AUTH="apex_admin:AdminPassword123!"
FAILURES=0

test_rest_endpoint() {
    local NAME="$1"
    local METHOD="$2"
    local PATH="$3"
    local AUTH="$4"
    local DATA="$5"
    local EXPECTED_CODE="$6"

    echo -n "Testing $NAME ($METHOD $PATH) ... "

    local CURL_ARGS=(
        -s -o /tmp/rest_response.json
        -w "%{http_code}"
        -X "$METHOD"
        -H "Content-Type: application/json"
    )

    if [ -n "$AUTH" ]; then
        CURL_ARGS+=(-u "$AUTH")
    fi

    if [ -n "$DATA" ]; then
        CURL_ARGS+=(-d "$DATA")
    fi

    local HTTP_CODE
    HTTP_CODE=$(curl "${CURL_ARGS[@]}" "${REST_BASE}${PATH}")

    if [ "$HTTP_CODE" -eq "$EXPECTED_CODE" ]; then
        echo "[PASS] (HTTP $HTTP_CODE)"
    else
        echo "[FAIL] Expected HTTP $EXPECTED_CODE, got HTTP $HTTP_CODE"
        echo "Response snippet: $(head -c 200 /tmp/rest_response.json 2>/dev/null || true)"
        FAILURES=$((FAILURES + 1))
    fi
}

# 1. Test Unauthorized Access (Security Gate)
echo "--- [1/3] Security & Authorization Enforcement on REST Endpoints ---"
test_rest_endpoint "Unauthorized /status access" "GET" "/status" "" "" 401
test_rest_endpoint "Unauthorized /settings access" "GET" "/settings" "" "" 401
test_rest_endpoint "Unauthorized /redirects access" "GET" "/redirects" "" "" 401
test_rest_endpoint "Unauthorized /analysis access" "GET" "/analysis/post/1" "" "" 401

# 2. Test Authorized Endpoints
echo ""
echo "--- [2/3] Authorized REST Operations ---"
test_rest_endpoint "Authorized /status" "GET" "/status" "$ADMIN_AUTH" "" 200
test_rest_endpoint "Authorized /settings (GET)" "GET" "/settings" "$ADMIN_AUTH" "" 200
test_rest_endpoint "Authorized /settings (POST update)" "POST" "/settings" "$ADMIN_AUTH" '{"enable_auto_sitemap": true, "enable_breadcrumbs": true}' 200
test_rest_endpoint "Authorized /redirects (GET list)" "GET" "/redirects" "$ADMIN_AUTH" "" 200
test_rest_endpoint "Authorized /redirects (POST create)" "POST" "/redirects" "$ADMIN_AUTH" '{"source_url": "/promo-old", "target_url": "/promo-new", "status_code": 301}' 201
test_rest_endpoint "Authorized /404 (GET logs)" "GET" "/404" "$ADMIN_AUTH" "" 200
test_rest_endpoint "Authorized /cache/status" "GET" "/cache/status" "$ADMIN_AUTH" "" 200
test_rest_endpoint "Authorized /cache/purge" "POST" "/cache/purge" "$ADMIN_AUTH" '{"type": "all"}' 200
test_rest_endpoint "Authorized /media/status" "GET" "/media/status" "$ADMIN_AUTH" "" 200
test_rest_endpoint "Authorized /schema/validate" "POST" "/schema/validate" "$ADMIN_AUTH" '{"@context": "https://schema.org", "@type": "Organization", "name": "Apex SEO"}' 200
test_rest_endpoint "Authorized /analytics/overview" "GET" "/analytics/overview" "$ADMIN_AUTH" "" 200

# 3. Test Phase 4 Analysis REST Endpoints
echo ""
echo "--- [3/3] Phase 4 Content Analysis REST Endpoints ---"
# Get first available post ID
WP_PATH="/var/www/html"
WP_CLI="wp --allow-root --path=${WP_PATH}"
POST_ID=$($WP_CLI post list --post_type=post --post_status=publish --field=ID --posts_per_page=1 | head -n1 || echo "1")

test_rest_endpoint "Analysis GET /analysis/post/$POST_ID" "GET" "/analysis/post/$POST_ID" "$ADMIN_AUTH" "" 200
test_rest_endpoint "Analysis POST /analysis/post/$POST_ID (trigger fresh analysis)" "POST" "/analysis/post/$POST_ID" "$ADMIN_AUTH" '{"force": true}' 200

echo "=========================================================="
if [ $FAILURES -eq 0 ]; then
    echo "REAL REST API VALIDATION RESULT: ALL PASSED"
    exit 0
else
    echo "REAL REST API VALIDATION RESULT: $FAILURES FAILURES DETECTED"
    exit 1
fi
