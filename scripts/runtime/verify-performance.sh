#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - REAL PERFORMANCE SMOKE TEST"
echo "=========================================================="

SITE_URL="http://localhost:8080"
REST_BASE="http://localhost:8080/wp-json/apexseo/v1"
ADMIN_AUTH="apex_admin:AdminPassword123!"
if [ -f /tmp/apex_admin_auth ]; then
    ADMIN_AUTH=$(cat /tmp/apex_admin_auth)
fi
OUTPUT_FILE="/tmp/performance_measurements.json"

measure_http() {
    local NAME="$1"
    local URL="$2"
    local AUTH="$3"

    local CURL_ARGS=(-s -o /dev/null -w "%{time_starttransfer}:%{time_total}:%{http_code}")
    if [ -n "$AUTH" ]; then
        CURL_ARGS+=(-u "$AUTH")
    fi

    local RESULT
    RESULT=$(curl "${CURL_ARGS[@]}" "$URL")

    local TTFB
    TTFB=$(echo "$RESULT" | cut -d':' -f1)
    local TOTAL
    TOTAL=$(echo "$RESULT" | cut -d':' -f2)
    local HTTP_CODE
    HTTP_CODE=$(echo "$RESULT" | cut -d':' -f3)

    local TTFB_MS
    TTFB_MS=$(awk "BEGIN {print int($TTFB * 1000)}")
    local TOTAL_MS
    TOTAL_MS=$(awk "BEGIN {print int($TOTAL * 1000)}")

    echo "Metric: $NAME -> HTTP $HTTP_CODE | TTFB: ${TTFB_MS}ms | Total: ${TOTAL_MS}ms"
}

echo "Measuring real HTTP response latencies..."
measure_http "Frontend Homepage" "$SITE_URL/" ""
measure_http "Robots.txt" "$SITE_URL/robots.txt" ""
measure_http "Sitemap Index XML" "$SITE_URL/sitemap_index.xml" ""
measure_http "REST Status Endpoint" "$REST_BASE/status" "$ADMIN_AUTH"
measure_http "REST Settings Endpoint" "$REST_BASE/settings" "$ADMIN_AUTH"

# Measure Analysis execution time via WP-CLI
WP_PATH="/var/www/html"
WP_CLI="wp --allow-root --path=${WP_PATH}"
POST_ID=$($WP_CLI post list --post_type=post --field=ID --posts_per_page=1 | head -n1 || echo "")
if [ -z "$POST_ID" ]; then
    POST_ID=$($WP_CLI post create --post_title="Performance Benchmark Post" --post_content="<p>Performance benchmark test content for APEX SEO analysis engine.</p>" --post_status=publish --porcelain)
fi

echo -n "Measuring WP-CLI Analysis Execution for Post $POST_ID ... "
START_TS=$(date +%s%N)
$WP_CLI apexseo analysis post "$POST_ID" > /dev/null
END_TS=$(date +%s%N)
DURATION_MS=$(( (END_TS - START_TS) / 1000000 ))
echo "${DURATION_MS}ms"

cat <<EOF > "$OUTPUT_FILE"
{
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "frontend_ttfb_ms": $TTFB_MS,
  "frontend_total_ms": $TOTAL_MS,
  "analysis_cli_duration_ms": $DURATION_MS
}
EOF

echo "Saved real performance artifacts to $OUTPUT_FILE"
echo "=========================================================="
echo "REAL PERFORMANCE SMOKE TEST COMPLETE"
