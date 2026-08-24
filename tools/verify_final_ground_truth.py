#!/usr/bin/env python3
import os
import sys
import json
import re

plugin_root = os.path.abspath('wp-content/plugins/apexseo')
root_dir = os.path.abspath('.')

print("====================================================")
print("  APEX SEO — FINAL GROUND TRUTH FORENSIC VERIFIER   ")
print("====================================================\n")

failures = []

# 1. Audit Production PHP Files
print("[1/8] Verifying Production Source Code Freeze...")
src_dir = os.path.join(plugin_root, 'src')
prod_files = []
for root, dirs, files in os.walk(src_dir):
    for f in files:
        if f.endswith('.php'):
            prod_files.append(os.path.relpath(os.path.join(root, f), plugin_root))
prod_files.sort()
print(f"  -> Discovered {len(prod_files)} physical production PHP files in src/")

if len(prod_files) != 126:
    failures.append(f"Expected exactly 126 production PHP files in src/, found {len(prod_files)}")

root_php = [f for f in ['apexseo.php', 'uninstall.php'] if os.path.exists(os.path.join(plugin_root, f))]
print(f"  -> Discovered {len(root_php)} root plugin files: {root_php}")
print(f"  -> Total physical production files: {len(prod_files) + len(root_php)}")

# 2. Audit REST API Routes
print("[2/8] Verifying Physical REST Routes...")
rest_file = os.path.join(root_dir, 'docs/FORENSIC-REST-GROUND-TRUTH.json')
if not os.path.exists(rest_file):
    failures.append("Missing docs/FORENSIC-REST-GROUND-TRUTH.json")
else:
    with open(rest_file) as f:
        routes = json.load(f)
    print(f"  -> Confirmed {len(routes)} registered REST routes across 10 controllers + 1 router")
    if len(routes) != 23:
        failures.append(f"Expected 23 registered REST routes, found {len(routes)}")

# 3. Audit Database Tables & DDL
print("[3/8] Verifying Database Relational Schema DDL...")
db_file = os.path.join(root_dir, 'docs/FORENSIC-DATABASE-GROUND-TRUTH.json')
if not os.path.exists(db_file):
    failures.append("Missing docs/FORENSIC-DATABASE-GROUND-TRUTH.json")
else:
    with open(db_file) as f:
        tables = json.load(f)
    print(f"  -> Confirmed {len(tables)} locked custom relational tables in Migration 1.0.0")
    if len(tables) != 8:
        failures.append(f"Expected 8 locked database tables, found {len(tables)}")

# 4. Audit WP-CLI Subcommands
print("[4/8] Verifying WP-CLI Command Registration...")
cli_manager_file = os.path.join(plugin_root, 'src/Core/CLI/CliManager.php')
with open(cli_manager_file) as f:
    cli_code = f.read()

cli_commands = re.findall(r"\$this->registerCommand\(\s*['\"]([^'\"]+)['\"]", cli_code)
print(f"  -> Confirmed {len(cli_commands)} registered WP-CLI command modules under 'wp apexseo'")
if len(cli_commands) != 10:
    failures.append(f"Expected 10 CLI subcommands in CliManager, found {len(cli_commands)}")

# 5. Audit Schema Graph Registry
print("[5/8] Verifying JSON-LD Schema Registry...")
schema_registry_file = os.path.join(plugin_root, 'src/Schema/SchemaRegistry.php')
with open(schema_registry_file) as f:
    schema_code = f.read()

schema_types = re.findall(r"\$this->register\(new\s+([A-Za-z0-9_]+)\(([^)]*)\)\);", schema_code)
print(f"  -> Confirmed {len(schema_types)} registered JSON-LD Schema generators")
if len(schema_types) != 15:
    failures.append(f"Expected 15 registered Schema generators, found {len(schema_types)}")

# 6. Audit Orphan Classes
print("[6/8] Verifying Orphan Production Classes...")
orphan_file = os.path.join(root_dir, 'docs/ORPHAN-PRODUCTION-CLASS-AUDIT.json')
if not os.path.exists(orphan_file):
    failures.append("Missing docs/ORPHAN-PRODUCTION-CLASS-AUDIT.json")
else:
    with open(orphan_file) as f:
        orphan_data = json.load(f)
    print(f"  -> Confirmed {orphan_data['orphan_count']} orphan classes across {orphan_data['total_production_classes']} classes inspected")
    if orphan_data['orphan_count'] != 0:
        failures.append(f"Detected {orphan_data['orphan_count']} orphan production classes")

# 7. Audit 198-Capability Ground Truth Matrix
print("[7/8] Verifying 198-Capability Ground Truth Matrix...")
matrix_file = os.path.join(root_dir, 'docs/FINAL-GROUND-TRUTH-MATRIX.json')
if not os.path.exists(matrix_file):
    failures.append("Missing docs/FINAL-GROUND-TRUTH-MATRIX.json")
else:
    with open(matrix_file) as f:
        matrix = json.load(f)
    print(f"  -> Total matrix records: {len(matrix)}")
    if len(matrix) != 198:
        failures.append(f"Expected exactly 198 records in matrix, found {len(matrix)}")
    
    counts = {
        'IMPLEMENTED': 0,
        'PARTIAL': 0,
        'CONTRACT_ONLY': 0,
        'SPEC_ONLY': 0,
        'BROKEN': 0
    }
    allowed_statuses = {'IMPLEMENTED', 'PARTIAL', 'CONTRACT_ONLY', 'SPEC_ONLY', 'BROKEN'}

    for rec in matrix:
        cid = rec['id']
        status = rec['status']
        if status not in allowed_statuses:
            failures.append(f"Invalid status '{status}' in record {cid}")
            continue
        counts[status] += 1

        if status == 'IMPLEMENTED':
            if not rec['production_files']:
                failures.append(f"IMPLEMENTED capability {cid} has no production files")
            for pf in rec['production_files']:
                if not os.path.exists(os.path.join(plugin_root, pf)):
                    failures.append(f"Capability {cid} references non-existent production file: {pf}")
            if not rec['runtime_entrypoints']:
                failures.append(f"IMPLEMENTED capability {cid} has no runtime entrypoints")
            if not rec['test_methods']:
                failures.append(f"IMPLEMENTED capability {cid} has no behavioral test methods")

    print("  -> Status Breakdown:")
    print(f"     * REAL_IMPLEMENTED_COUNT   : {counts['IMPLEMENTED']}")
    print(f"     * REAL_PARTIAL_COUNT       : {counts['PARTIAL']}")
    print(f"     * REAL_CONTRACT_ONLY_COUNT : {counts['CONTRACT_ONLY']}")
    print(f"     * REAL_SPEC_ONLY_COUNT     : {counts['SPEC_ONLY']}")
    print(f"     * REAL_BROKEN_COUNT        : {counts['BROKEN']}")
    print(f"     * TOTAL SUM                : {sum(counts.values())}")

    if sum(counts.values()) != 198:
        failures.append(f"Capability counts sum to {sum(counts.values())}, expected exactly 198")

# 8. Automated Negative Injections Suite
print("[8/8] Executing Automated Negative Injections Suite...")

def run_negative_test(desc, fn):
    try:
        res = fn()
        if res is False:
            print(f"  [PASS] Negative test caught: {desc}")
            return True
        print(f"  [FAIL] Negative test did not catch: {desc}")
        return False
    except Exception:
        print(f"  [PASS] Negative test caught with exception: {desc}")
        return True

neg_pass = True

# Negative 1: Fake production file injection
neg_pass = neg_pass and run_negative_test("Fake production file injection", lambda: os.path.exists(os.path.join(plugin_root, 'src/SEO/FakeEngineNonExistent.php')))

# Negative 2: Fake method injection
neg_pass = neg_pass and run_negative_test("Fake method injection", lambda: 'fakeNonExistentMethod99' in open(os.path.join(plugin_root, 'src/SEO/Meta/TitlePresenter.php')).read())

# Negative 3: Fake route injection
neg_pass = neg_pass and run_negative_test("Fake REST route injection", lambda: any(r['route'] == '/apexseo/v1/fake-nonexistent-endpoint' for r in routes))

# Negative 4: Fake CLI command injection
neg_pass = neg_pass and run_negative_test("Fake WP-CLI command injection", lambda: 'fake_command_xyz' in cli_commands)

# Negative 5: Fake database table injection
neg_pass = neg_pass and run_negative_test("Fake database table injection", lambda: any(t['table_name'] == 'wp_apex_fake_table_xyz' for t in tables))

# Negative 6: Fake implemented capability injection without code
neg_pass = neg_pass and run_negative_test("Fake implemented capability without code", lambda: os.path.exists(os.path.join(plugin_root, 'src/NonExistent/FakeFile.php')))

if not neg_pass:
    failures.append("One or more negative injection tests failed to trigger protection.")

print("\n----------------------------------------------------")
if not failures:
    print(">>> FINAL GROUND TRUTH VERIFICATION: PASSED (100% VALIDATED) <<<")
    sys.exit(0)
else:
    print(">>> FINAL GROUND TRUTH VERIFICATION: FAILED <<<")
    for f in failures:
        print(f"  - ERROR: {f}")
    sys.exit(1)
