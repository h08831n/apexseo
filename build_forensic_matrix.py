#!/usr/bin/env python3
import os
import re
import json

SRC_DIR = "wp-content/plugins/apexseo/src"
TESTS_DIR = "wp-content/plugins/apexseo/tests"
DOCS_DIR = "docs"

def audit_all_198():
    with open("docs/FINAL-FEATURE-INDEX.md", "r", encoding="utf-8") as f:
        lines = f.readlines()

    features = []
    for line in lines:
        if not line.strip().startswith("|"):
            continue
        parts = [p.strip() for p in line.split("|") if p.strip() != ""]
        if len(parts) >= 8:
            m = re.search(r'APEX-(\d{3})', parts[0])
            if m:
                fid = m.group(0)
                cat = parts[1]
                name = parts[2]
                src_prod = parts[3]
                free_pro = parts[4]
                source_path = parts[5]
                apex_target = parts[6].replace('`', '')
                spec_status = parts[7].replace('`', '')
                dep = parts[8].replace('`', '') if len(parts) > 8 else "None"
                features.append({
                    "id": fid,
                    "category": cat,
                    "name": name,
                    "source_prod": src_prod,
                    "free_pro": free_pro,
                    "source_path": source_path,
                    "apex_target": apex_target,
                    "spec_status": spec_status,
                    "dep": dep
                })

    # Let's inspect the entire codebase to see which APEX features have real implementation vs partial vs contract vs spec
    # Let's write rules for each APEX ID
    matrix_rows = []
    
    # We will build detailed forensic analysis
    return features

if __name__ == "__main__":
    audit_all_198()
