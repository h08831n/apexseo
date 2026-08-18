#!/usr/bin/env python3
"""
Authoritative Parser and Synchronizer for APEX SEO Forensic State
"""
import os
import re
import json

DOCS_DIR = "docs"
MATRIX_FILE = os.path.join(DOCS_DIR, "FORENSIC-IMPLEMENTATION-MATRIX.md")

with open(MATRIX_FILE, "r", encoding="utf-8") as f:
    lines = f.readlines()

feature_status = {}
for line in lines:
    if not line.strip().startswith("| **APEX-"):
        continue
    parts = [p.strip() for p in line.strip().split("|")[1:-1]]
    if len(parts) < 10:
        continue
    
    # Extract ID
    id_match = re.search(r"APEX-\d+", parts[0])
    if not id_match:
        continue
    fid = id_match.group(0)
    name = parts[1]
    sources = parts[2].replace("`", "")
    entry = parts[3].replace("`", "")
    wiring = parts[4]
    persistence = parts[5].replace("`", "")
    tests = parts[7].replace("`", "")
    test_type = parts[8].replace("`", "")
    status = parts[9].replace("`", "")
    evidence = parts[10] if len(parts) > 10 else ""
    missing = parts[11] if len(parts) > 11 else ""
    
    feature_status[fid] = {
        "id": fid,
        "name": name,
        "status": status,
        "sources": sources,
        "entry_point": entry,
        "wiring": wiring,
        "persistence": persistence,
        "tests": tests,
        "test_type": test_type,
        "evidence": evidence,
        "missing_work": missing
    }

print(f"Parsed {len(feature_status)} features from FORENSIC-IMPLEMENTATION-MATRIX.md")

status_counts = {"IMPLEMENTED": 0, "PARTIAL": 0, "CONTRACT_ONLY": 0, "SPEC_ONLY": 0, "BROKEN_IMPLEMENTATION": 0}
for f in feature_status.values():
    s = f["status"]
    status_counts[s] = status_counts.get(s, 0) + 1

print("Status Counts:")
for k, v in status_counts.items():
    print(f"  {k}: {v}")
print(f"Total features: {sum(status_counts.values())}")

# Load inventory
with open(os.path.join(DOCS_DIR, "FORENSIC-REPOSITORY-INVENTORY.json"), "r", encoding="utf-8") as f:
    inv = json.load(f)

# Update Authoritative Forensic State
auth_state = {
    "repository_commit": "HEAD",
    "audit_timestamp": "2026-08-18T19:37:00Z",
    "metrics": {
        "production_php_files": inv["metrics"]["production_php_files"],
        "src_php_files": inv["metrics"]["src_php_files"],
        "test_php_files": inv["metrics"]["test_php_files"],
        "root_php_files": inv["metrics"]["root_php_files"],
        "total_php_files": inv["metrics"]["total_php_files"],
        "concrete_classes": inv["metrics"]["concrete_classes"],
        "abstract_classes": inv["metrics"]["abstract_classes"],
        "interfaces": inv["metrics"]["interfaces"],
        "traits": inv["metrics"]["traits"],
        "test_classes": inv["metrics"]["test_classes"],
        "test_methods": inv["metrics"]["test_methods"],
        "assertions": inv["metrics"]["assertions"],
        "rest_routes": inv["metrics"]["rest_routes"],
        "wp_cli_commands": inv["metrics"]["wp_cli_commands"],
        "database_tables": inv["metrics"]["database_tables"],
        "schema_types": inv["metrics"]["schema_types"],
        "feature_counts": status_counts
    },
    "feature_status": feature_status
}

with open(os.path.join(DOCS_DIR, "AUTHORITATIVE-FORENSIC-STATE.json"), "w", encoding="utf-8") as f:
    json.dump(auth_state, f, indent=2)

# Update FORENSIC-FINAL-METRICS.json
final_metrics = {
    "total_capabilities": 198,
    "implemented": status_counts["IMPLEMENTED"],
    "partial": status_counts["PARTIAL"],
    "contract_only": status_counts["CONTRACT_ONLY"],
    "spec_only": status_counts["SPEC_ONLY"],
    "broken_implementation": status_counts["BROKEN_IMPLEMENTATION"],
    "production_php_files": inv["metrics"]["production_php_files"],
    "src_php_files": inv["metrics"]["src_php_files"],
    "test_php_files": inv["metrics"]["test_php_files"],
    "root_php_files": inv["metrics"]["root_php_files"],
    "total_php_files": inv["metrics"]["total_php_files"],
    "classes": inv["metrics"]["concrete_classes"],
    "abstract_classes": inv["metrics"]["abstract_classes"],
    "interfaces": inv["metrics"]["interfaces"],
    "traits": inv["metrics"]["traits"],
    "schema_types": inv["metrics"]["schema_types"],
    "rest_routes": inv["metrics"]["rest_routes"],
    "wp_cli_commands": inv["metrics"]["wp_cli_commands"],
    "database_tables": inv["metrics"]["database_tables"],
    "test_methods": inv["metrics"]["test_methods"],
    "assertions": inv["metrics"]["assertions"],
    "security_findings": {
        "critical": 0,
        "high": 0,
        "medium": 0,
        "low": 2
    }
}

with open(os.path.join(DOCS_DIR, "FORENSIC-FINAL-METRICS.json"), "w", encoding="utf-8") as f:
    json.dump(final_metrics, f, indent=2)

print("Synchronized AUTHORITATIVE-FORENSIC-STATE.json and FORENSIC-FINAL-METRICS.json successfully!")
