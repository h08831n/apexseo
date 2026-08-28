#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - REAL WP-CLI COMMAND SUITE VALIDATION"
echo "=========================================================="

WP_PATH="/var/www/html"
WP_CLI="wp --allow-root --path=${WP_PATH}"
FAILURES=0

run_cli_test() {
    local NAME="$1"
    local CMD="$2"
    local EXPECTED_STRING="$3"

    echo -n "Testing CLI: $NAME ('$CMD') ... "

    local OUTPUT
    if OUTPUT=$($WP_CLI $CMD 2>&1); then
        if [ -n "$EXPECTED_STRING" ] && ! echo "$OUTPUT" | grep -qi "$EXPECTED_STRING"; then
            echo "[FAIL] Command succeeded but output missing expected string '$EXPECTED_STRING'"
            echo "Actual Output: $OUTPUT"
            FAILURES=$((FAILURES + 1))
        else
            echo "[PASS]"
        fi
    else
        echo "[FAIL] Command returned non-zero exit code"
        echo "Error Output: $OUTPUT"
        FAILURES=$((FAILURES + 1))
    fi
}

# 1. Root and Diagnostic Subcommands
echo "--- [1/3] Root and System Diagnostics ---"
run_cli_test "Root command" "apexseo" "Apex SEO"
run_cli_test "Doctor diagnostics" "apexseo doctor" "OK"
run_cli_test "System report" "apexseo report" "WordPress"

# 2. Phase 4 Content Analysis CLI Commands
echo ""
echo "--- [2/3] Content Analysis CLI Subsystem ---"
POST_ID=$($WP_CLI post list --post_type=post --post_status=publish --field=ID --posts_per_page=1 | head -n1 || echo "1")
run_cli_test "Analysis for single post" "apexseo analysis post $POST_ID" "score"
run_cli_test "Analysis for all posts" "apexseo analysis all" "Complete"
run_cli_test "Analysis reindex" "apexseo analysis reindex" "Done"

# 3. Core Subsystems Commands
echo ""
echo "--- [3/3] Performance, Sitemap, Schema, and Redirect Commands ---"
run_cli_test "Cache purge" "apexseo cache purge --all" "Purged"
run_cli_test "Sitemap rebuild" "apexseo sitemap rebuild" "sitemap"
run_cli_test "Schema validate" "apexseo schema validate --type=Article" "Valid"
run_cli_test "Redirect list" "apexseo redirect list" "Source"
run_cli_test "Index rebuild" "apexseo index rebuild" "Complete"
run_cli_test "Database cleanup" "apexseo db cleanup" "Optimized"

echo "=========================================================="
if [ $FAILURES -eq 0 ]; then
    echo "REAL WP-CLI VALIDATION RESULT: ALL PASSED"
    exit 0
else
    echo "REAL WP-CLI VALIDATION RESULT: $FAILURES FAILURES DETECTED"
    exit 1
fi
