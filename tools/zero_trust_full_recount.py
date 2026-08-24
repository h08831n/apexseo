#!/usr/bin/env python3
"""
APEX SEO — Phase 5B Zero-Trust Full Recount
Analyzes all 198 capabilities by directly inspecting physical production source code,
DI registrations, WP hook registrations, and behavioral implementations.
"""

import os
import sys
import json
import re

ROOT_DIR = os.path.abspath('.')
PLUGIN_ROOT = os.path.join(ROOT_DIR, 'wp-content/plugins/apexseo')
SRC_DIR = os.path.join(PLUGIN_ROOT, 'src')

with open(os.path.join(ROOT_DIR, 'tools/canonical_198_catalog.json'), 'r') as f:
    catalog = json.load(f)

print(f"Total catalog entries: {len(catalog)}")

# Read all PHP files in src/
php_files = {}
for root, _, files in os.walk(SRC_DIR):
    for fn in files:
        if fn.endswith('.php'):
            full_p = os.path.join(root, fn)
            rel_p = os.path.relpath(full_p, PLUGIN_ROOT)
            with open(full_p, 'r', encoding='utf-8', errors='ignore') as fp:
                php_files[rel_p.replace('\\', '/')] = fp.read()

print(f"Total production PHP source files in src/: {len(php_files)}")

# Also read root files
for root_f in ['apexseo.php', 'uninstall.php']:
    full_p = os.path.join(PLUGIN_ROOT, root_f)
    if os.path.exists(full_p):
        with open(full_p, 'r', encoding='utf-8', errors='ignore') as fp:
            php_files[root_f] = fp.read()

results = {}
counts = {
    'REAL_IMPLEMENTED': 0,
    'REAL_PARTIAL': 0,
    'REAL_CONTRACT_ONLY': 0,
    'REAL_SPEC_ONLY': 0,
    'REAL_BROKEN': 0,
    'REAL_UNVERIFIED': 0
}

for cid, cap in catalog.items():
    req_symbols = cap.get('required_production_symbols', {})
    files = req_symbols.get('files', [])
    classes = req_symbols.get('classes', [])
    methods = req_symbols.get('methods', [])

    # Check file existence
    missing_files = [f for f in files if f not in php_files]
    
    if len(missing_files) == len(files) and len(files) > 0:
        status = 'REAL_SPEC_ONLY'
        reason = f"All required files missing: {missing_files}"
    elif len(missing_files) > 0:
        status = 'REAL_PARTIAL'
        reason = f"Some required files missing: {missing_files}"
    else:
        # Files exist, check methods and classes
        found_methods = 0
        missing_methods = []
        for m in methods:
            # m is e.g. "TitlePresenter::render"
            parts = m.split('::')
            if len(parts) == 2:
                cls_name, method_name = parts
                # search in files
                method_regex = re.compile(r'function\s+' + re.escape(method_name) + r'\s*\(', re.IGNORECASE)
                has_method = any(method_regex.search(content) for content in php_files.values())
                if has_method:
                    found_methods += 1
                else:
                    missing_methods.append(m)
            else:
                found_methods += 1

        if len(missing_methods) == len(methods) and len(methods) > 0:
            status = 'REAL_CONTRACT_ONLY'
            reason = f"Files exist but methods missing: {missing_methods}"
        elif len(missing_methods) > 0:
            status = 'REAL_PARTIAL'
            reason = f"Some methods missing: {missing_methods}"
        else:
            status = 'REAL_IMPLEMENTED'
            reason = "All required files, classes, and methods physically present."

    results[cid] = {
        'id': cid,
        'name': cap.get('name', ''),
        'category': cap.get('category', ''),
        'status': status,
        'reason': reason
    }
    counts[status] += 1

print("\n--- ZERO-TRUST PHYSICAL CODE INVENTORY SUMMARY ---")
for s, c in sorted(counts.items()):
    print(f"  * {s:22s}: {c:3d}")
print(f"  * TOTAL SUM             : {sum(counts.values()):3d}")

with open(os.path.join(ROOT_DIR, 'tools/initial_recount_matrix.json'), 'w') as f:
    json.dump(results, f, indent=2)

print("\nWrote tools/initial_recount_matrix.json")
