#!/usr/bin/env bash
set -eo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "##########################################################"
echo "# APEX SEO - COMPLETE REAL RUNTIME TEST ORCHESTRATOR    #"
echo "##########################################################"

START_TIME=$(date +%s)
TOTAL_FAILURES=0

run_step() {
    local TITLE="$1"
    local SCRIPT="$2"

    echo ""
    echo ">>> EXECUTING: $TITLE ($SCRIPT)"
    if bash "$SCRIPT_DIR/$SCRIPT"; then
        echo ">>> SUCCESS: $TITLE"
    else
        echo ">>> FAILURE DETECTED IN: $TITLE"
        TOTAL_FAILURES=$((TOTAL_FAILURES + 1))
    fi
}

# 1. Setup WordPress Environment
run_step "WordPress Core & Plugin Bootstrap" "setup-wordpress.sh"

# 2. Database Schema & Relational Integrity
run_step "Real Database Table & CRUD Verification" "verify-db.sh"

# 3. Phase 4 Content Analysis Pipeline E2E
run_step "Phase 4 Post Lifecycle & Database Persistence" "verify-phase4.sh"

# 4. Real REST API Test Suite
run_step "Real REST Controller Endpoints" "verify-rest.sh"

# 5. Real WP-CLI Command Suite
run_step "Real WP-CLI Subcommands" "verify-cli.sh"

# 6. Real Frontend Head & Permalinks
run_step "Frontend Meta, Robots, Permalinks & Headers" "verify-frontend.sh"

# 7. Security & Failure Injection
run_step "Security Rejection & Exception Isolation" "verify-security.sh"

# 8. Performance Smoke Measurements
run_step "Performance Latency & TTFB Benchmarks" "verify-performance.sh"

END_TIME=$(date +%s)
ELAPSED=$((END_TIME - START_TIME))

echo ""
echo "##########################################################"
echo "# ALL INTEGRATION STAGES COMPLETED IN ${ELAPSED}s"
if [ $TOTAL_FAILURES -eq 0 ]; then
    echo "# OVERALL STATUS: ALL REAL RUNTIME GATES PASSED (0 FAILURES)"
    echo "##########################################################"
    exit 0
else
    echo "# OVERALL STATUS: GATE FAILED WITH $TOTAL_FAILURES FAILURES"
    echo "##########################################################"
    exit 1
fi
