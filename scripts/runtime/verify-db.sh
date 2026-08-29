#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - REAL DATABASE VALIDATION SUITE"
echo "=========================================================="

MYSQL_CMD="mysql -h db -u wp_test_user -pwp_test_pass_123! wordpress_test -e"
FAILURES=0

EXPECTED_TABLES=(
    "wp_apex_indexables"
    "wp_apex_schema"
    "wp_apex_redirects"
    "wp_apex_404_logs"
    "wp_apex_links"
    "wp_apex_image_history"
    "wp_apex_analytics"
    "wp_apex_rank_tracking"
)

# 1. Verify Table Existence (Exactly 8 tables)
echo "--- [1/4] Checking Existence of all 8 APEX Tables ---"
for TABLE in "${EXPECTED_TABLES[@]}"; do
    EXISTS=$($MYSQL_CMD "SHOW TABLES LIKE '$TABLE';" -s -N)
    if [ "$EXISTS" = "$TABLE" ]; then
        echo "[PASS] Table exists: $TABLE"
    else
        echo "[FAIL] Missing expected table: $TABLE"
        FAILURES=$((FAILURES + 1))
    fi
done

# Verify wp_apex_content_analysis is ABSENT
NO_NINTH_TABLE=$($MYSQL_CMD "SHOW TABLES LIKE 'wp_apex_content_analysis';" -s -N)
if [ -z "$NO_NINTH_TABLE" ]; then
    echo "[PASS] Verified table wp_apex_content_analysis is NOT present (8-table schema enforced)"
else
    echo "[FAIL] Unexpected 9th table wp_apex_content_analysis exists"
    FAILURES=$((FAILURES + 1))
fi

# 2. Verify Schema Definitions, Primary Keys and Key Columns
echo ""
echo "--- [2/4] Checking Columns and Primary Keys ---"

# Check wp_apex_indexables
echo "Validating wp_apex_indexables schema..."
$MYSQL_CMD "DESCRIBE wp_apex_indexables;" > /tmp/desc_indexables.txt
grep -q "primary_focus_keyword" /tmp/desc_indexables.txt && echo "[PASS] Column primary_focus_keyword present" || { echo "[FAIL] Missing primary_focus_keyword"; FAILURES=$((FAILURES + 1)); }
grep -q "keyword_density" /tmp/desc_indexables.txt && echo "[PASS] Column keyword_density present" || { echo "[FAIL] Missing keyword_density"; FAILURES=$((FAILURES + 1)); }
grep -q "readability_score" /tmp/desc_indexables.txt && echo "[PASS] Column readability_score present" || { echo "[FAIL] Missing readability_score"; FAILURES=$((FAILURES + 1)); }
grep -q "content_analysis" /tmp/desc_indexables.txt && echo "[PASS] Column content_analysis present" || { echo "[FAIL] Missing content_analysis"; FAILURES=$((FAILURES + 1)); }
grep -q "is_cornerstone" /tmp/desc_indexables.txt && echo "[PASS] Column is_cornerstone present" || { echo "[FAIL] Missing is_cornerstone"; FAILURES=$((FAILURES + 1)); }

# Check wp_apex_redirects
echo "Validating wp_apex_redirects schema..."
$MYSQL_CMD "DESCRIBE wp_apex_redirects;" > /tmp/desc_redirects.txt
grep -q "source_path" /tmp/desc_redirects.txt && echo "[PASS] Column source_path present" || { echo "[FAIL] Missing source_path"; FAILURES=$((FAILURES + 1)); }
grep -q "target_url" /tmp/desc_redirects.txt && echo "[PASS] Column target_url present" || { echo "[FAIL] Missing target_url"; FAILURES=$((FAILURES + 1)); }
grep -q "status_code" /tmp/desc_redirects.txt && echo "[PASS] Column status_code present" || { echo "[FAIL] Missing status_code"; FAILURES=$((FAILURES + 1)); }
grep -q "match_type" /tmp/desc_redirects.txt && echo "[PASS] Column match_type present" || { echo "[FAIL] Missing match_type"; FAILURES=$((FAILURES + 1)); }
grep -q "hits" /tmp/desc_redirects.txt && echo "[PASS] Column hits present" || { echo "[FAIL] Missing hits"; FAILURES=$((FAILURES + 1)); }
grep -q "is_active" /tmp/desc_redirects.txt && echo "[PASS] Column is_active present" || { echo "[FAIL] Missing is_active"; FAILURES=$((FAILURES + 1)); }

# 3. Verify Indexes & Unique Constraints
echo ""
echo "--- [3/4] Checking Unique Constraints and Secondary Indexes ---"
INDEXES_INDEXABLES=$($MYSQL_CMD "SHOW INDEX FROM wp_apex_indexables;" -s -N)
echo "$INDEXES_INDEXABLES" | grep -q "uk_object" && echo "[PASS] Unique key uk_object verified" || { echo "[FAIL] Missing uk_object"; FAILURES=$((FAILURES + 1)); }
echo "$INDEXES_INDEXABLES" | grep -q "idx_permalink_hash" && echo "[PASS] Index idx_permalink_hash verified" || { echo "[FAIL] Missing idx_permalink_hash"; FAILURES=$((FAILURES + 1)); }

INDEXES_REDIRECTS=$($MYSQL_CMD "SHOW INDEX FROM wp_apex_redirects;" -s -N)
echo "$INDEXES_REDIRECTS" | grep -q "uk_source_path" && echo "[PASS] Unique key uk_source_path verified" || { echo "[FAIL] Missing uk_source_path"; FAILURES=$((FAILURES + 1)); }
echo "$INDEXES_REDIRECTS" | grep -q "idx_active" && echo "[PASS] Index idx_active verified" || { echo "[FAIL] Missing idx_active"; FAILURES=$((FAILURES + 1)); }

INDEXES_404=$($MYSQL_CMD "SHOW INDEX FROM wp_apex_404_logs;" -s -N)
echo "$INDEXES_404" | grep -q "uk_request_uri" && echo "[PASS] Unique key uk_request_uri verified" || { echo "[FAIL] Missing uk_request_uri"; FAILURES=$((FAILURES + 1)); }
echo "$INDEXES_404" | grep -q "idx_hits" && echo "[PASS] Index idx_hits verified" || { echo "[FAIL] Missing idx_hits"; FAILURES=$((FAILURES + 1)); }

# 4. Perform Real CRUD Test
echo ""
echo "--- [4/4] Executing Direct CRUD Operations ---"
# 1. INSERT test redirect using production schema
$MYSQL_CMD "INSERT INTO wp_apex_redirects (source_path, target_url, status_code, match_type, hits, is_active) VALUES ('/old-test-slug', '/new-test-slug', 301, 'exact', 0, 1);"
echo "[PASS] Inserted test redirect into wp_apex_redirects"

# 2. SELECT using source_path
RETRIEVED_TARGET=$($MYSQL_CMD "SELECT target_url FROM wp_apex_redirects WHERE source_path = '/old-test-slug';" -s -N)
if [ "$RETRIEVED_TARGET" = "/new-test-slug" ]; then
    echo "[PASS] Verified SELECT target_url: $RETRIEVED_TARGET"
else
    echo "[FAIL] SELECT target_url mismatch: expected '/new-test-slug', got '$RETRIEVED_TARGET'"
    FAILURES=$((FAILURES + 1))
fi

# 3. Increment and verify hits
$MYSQL_CMD "UPDATE wp_apex_redirects SET hits = hits + 1 WHERE source_path = '/old-test-slug';"
HITS=$($MYSQL_CMD "SELECT hits FROM wp_apex_redirects WHERE source_path = '/old-test-slug';" -s -N)
if [ "$HITS" = "1" ]; then
    echo "[PASS] Verified UPDATE hits: $HITS"
else
    echo "[FAIL] UPDATE hits failed: expected '1', got '$HITS'"
    FAILURES=$((FAILURES + 1))
fi

# 4. DELETE using source_path
$MYSQL_CMD "DELETE FROM wp_apex_redirects WHERE source_path = '/old-test-slug';"
DELETED_CHECK=$($MYSQL_CMD "SELECT COUNT(*) FROM wp_apex_redirects WHERE source_path = '/old-test-slug';" -s -N)
if [ "$DELETED_CHECK" = "0" ]; then
    echo "[PASS] Verified DELETE operation"
else
    echo "[FAIL] Row still exists after DELETE"
    FAILURES=$((FAILURES + 1))
fi

echo "=========================================================="
if [ $FAILURES -eq 0 ]; then
    echo "REAL DATABASE VALIDATION RESULT: ALL PASSED"
    exit 0
else
    echo "REAL DATABASE VALIDATION RESULT: $FAILURES FAILURES DETECTED"
    exit 1
fi
