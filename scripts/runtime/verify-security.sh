#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - REAL SECURITY & FAILURE INJECTION SUITE"
echo "=========================================================="

REST_BASE="http://localhost:8080/wp-json/apexseo/v1"
ADMIN_AUTH="apex_admin:AdminPassword123!"
if [ -f /tmp/apex_admin_auth ]; then
    ADMIN_AUTH=$(cat /tmp/apex_admin_auth)
fi
FAILURES=0

test_security_payload() {
    local NAME="$1"
    local METHOD="$2"
    local PATH="$3"
    local AUTH="$4"
    local DATA="$5"
    local EXPECTED_RANGE="$6"

    echo -n "Testing Security: $NAME ... "

    local CURL_ARGS=(
        -s -o /tmp/sec_response.json
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

    # Check if HTTP_CODE is in error range 400-499
    if [[ "$HTTP_CODE" =~ ^4[0-9]{2}$ ]]; then
        echo "[PASS] Successfully Rejected / Handled (HTTP $HTTP_CODE)"
    else
        echo "[FAIL] Expected 4xx client rejection, got HTTP $HTTP_CODE"
        echo "Response: $(head -c 200 /tmp/sec_response.json 2>/dev/null || true)"
        FAILURES=$((FAILURES + 1))
    fi
}

# 1. Injection & Malicious URLs
echo "--- [1/3] Malicious URL Schemes & Invalid Regex Injection ---"
test_security_payload "XSS Javascript URL in Redirect Target" "POST" "/redirects" "$ADMIN_AUTH" '{"source_url": "/test-xss", "target_url": "javascript:alert(1)", "status_code": 301}' "400"
test_security_payload "Data URI scheme injection in Redirect" "POST" "/redirects" "$ADMIN_AUTH" '{"source_url": "/test-data", "target_url": "data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==", "status_code": 301}' "400"
test_security_payload "Malformed Unclosed Regex in Redirect Rule" "POST" "/redirects" "$ADMIN_AUTH" '{"source_url": "/test-(unclosed", "target_url": "/target", "is_regex": 1}' "400"

# 2. Malformed JSON & Schema Payloads
echo ""
echo "--- [2/3] Malformed Payloads & Schema Validation ---"
test_security_payload "Malformed Raw JSON Payload" "POST" "/settings" "$ADMIN_AUTH" '{unquoted_broken_json:' "400"
test_security_payload "Invalid Schema Object without @context or @type" "POST" "/schema/validate" "$ADMIN_AUTH" '{"random_key": 123}' "400"

# 3. Invalid IDs and Nonexistent Resources
echo ""
echo "--- [3/3] Nonexistent Posts & Invalid Resource IDs ---"
test_security_payload "Nonexistent Post ID in Analysis" "GET" "/analysis/post/999999" "$ADMIN_AUTH" "" "404"
test_security_payload "Negative Post ID in Analysis" "GET" "/analysis/post/-1" "$ADMIN_AUTH" "" "400"
test_security_payload "Non-numeric Post ID in Meta" "GET" "/meta/abc_invalid_id" "$ADMIN_AUTH" "" "404"

echo "=========================================================="
if [ $FAILURES -eq 0 ]; then
    echo "REAL SECURITY VALIDATION RESULT: ALL PASSED"
    exit 0
else
    echo "REAL SECURITY VALIDATION RESULT: $FAILURES FAILURES DETECTED"
    exit 1
fi
