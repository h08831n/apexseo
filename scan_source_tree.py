#!/usr/bin/env python3
"""
Forensic Codebase Scanner for APEX-001 to APEX-198
"""
import os
import re
import json

SRC_DIR = "wp-content/plugins/apexseo/src"
TESTS_DIR = "wp-content/plugins/apexseo/tests"
DOCS_DIR = "docs"

def trace_all():
    # 1. Parse all 198 features
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
                    "src_prod": src_prod,
                    "free_pro": free_pro,
                    "source_path": source_path,
                    "apex_target": apex_target,
                    "dep": dep
                })

    # Let's read all source files
    src_files = {}
    for root, _, files in os.walk(SRC_DIR):
        for file in files:
            if file.endswith(".php"):
                fpath = os.path.join(root, file)
                rel = os.path.relpath(fpath, "wp-content/plugins/apexseo")
                with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                    src_files[rel] = fh.read()

    # Let's read all test files
    test_files = {}
    for root, _, files in os.walk(TESTS_DIR):
        for file in files:
            if file.endswith(".php"):
                fpath = os.path.join(root, file)
                rel = os.path.relpath(fpath, "wp-content/plugins/apexseo")
                with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                    test_files[rel] = fh.read()

    return features, src_files, test_files

if __name__ == "__main__":
    feats, s_files, t_files = trace_all()
    print(f"Features: {len(feats)}, Src files: {len(s_files)}, Test files: {len(t_files)}")
