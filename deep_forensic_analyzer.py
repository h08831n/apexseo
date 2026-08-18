#!/usr/bin/env python3
"""
Deep Forensic Analyzer for all 198 APEX IDs.
"""
import os
import re
import json

SRC_DIR = "wp-content/plugins/apexseo/src"
TESTS_DIR = "wp-content/plugins/apexseo/tests"
DOCS_DIR = "docs"

def main():
    # 1. Parse all 198 capabilities from FINAL-FEATURE-INDEX.md
    with open(os.path.join(DOCS_DIR, "FINAL-FEATURE-INDEX.md"), "r", encoding="utf-8") as f:
        lines = f.readlines()

    features = {}
    for line in lines:
        if not line.strip().startswith("|"):
            continue
        parts = [p.strip() for p in line.split("|") if p.strip() != ""]
        if len(parts) >= 8:
            m = re.search(r'APEX-(\d{3})', parts[0])
            if m:
                fid = m.group(0)
                category = parts[1]
                name = parts[2]
                source_prod = parts[3]
                free_pro = parts[4]
                source_path = parts[5]
                apex_target = parts[6].replace('`', '')
                spec_status = parts[7].replace('`', '')
                dep = parts[8].replace('`', '') if len(parts) > 8 else "None"
                features[fid] = {
                    "id": fid,
                    "category": category,
                    "name": name,
                    "source_prod": source_prod,
                    "free_pro": free_pro,
                    "source_path": source_path,
                    "apex_target": apex_target,
                    "spec_status": spec_status,
                    "dep": dep
                }

    print(f"Loaded {len(features)} feature definitions.")
    
    # Let's inspect all files in src/
    all_src_files = {}
    for root, _, files in os.walk(SRC_DIR):
        for file in files:
            if file.endswith(".php"):
                fpath = os.path.join(root, file)
                rel_path = os.path.relpath(fpath, "wp-content/plugins/apexseo")
                with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                    content = fh.read()
                    all_src_files[rel_path] = {
                        "abs_path": fpath,
                        "content": content,
                        "filename": file
                    }

    # Let's inspect all files in tests/
    all_test_files = {}
    for root, _, files in os.walk(TESTS_DIR):
        for file in files:
            if file.endswith(".php"):
                fpath = os.path.join(root, file)
                rel_path = os.path.relpath(fpath, "wp-content/plugins/apexseo")
                with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                    content = fh.read()
                    all_test_files[rel_path] = {
                        "abs_path": fpath,
                        "content": content,
                        "filename": file
                    }

    print(f"Total src PHP files: {len(all_src_files)}")
    print(f"Total test PHP files: {len(all_test_files)}")

    # Let's check wiring in Plugin.php, HookManager.php, Container.php, and each Module
    module_files = [k for k in all_src_files if k.endswith("Module.php") or k.endswith("Plugin.php") or k.endswith("HookManager.php") or k.endswith("RestManager.php") or k.endswith("CliManager.php")]
    print(f"Core wiring files: {module_files}")

if __name__ == "__main__":
    main()
