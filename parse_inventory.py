#!/usr/bin/env python3
import os
import re
import json

SRC_DIR = "wp-content/plugins/apexseo/src"
TESTS_DIR = "wp-content/plugins/apexseo/tests"
PLUGIN_ENTRY = "wp-content/plugins/apexseo/apexseo.php"
UNINSTALL_ENTRY = "wp-content/plugins/apexseo/uninstall.php"

def parse_full_inventory():
    # Read FINAL-FEATURE-INDEX.md or 02-COMPLETE-FEATURE-INVENTORY.md
    with open("docs/02-COMPLETE-FEATURE-INVENTORY.md", "r", encoding="utf-8") as f:
        content = f.read()

    features = {}
    for line in content.splitlines():
        m = re.search(r'\|\s*\*\*?(APEX-\d{3})\*\*?\s*\|\s*([^|]+)\|\s*([^|]+)\|\s*([^|]+)\|', line)
        if m:
            fid = m.group(1).strip()
            cat = m.group(2).strip()
            desc = m.group(3).strip()
            ref = m.group(4).strip()
            features[fid] = {
                "id": fid,
                "category": cat,
                "capability": desc,
                "ref": ref
            }
            
    print(f"Loaded {len(features)} features from inventory.")
    return features

if __name__ == "__main__":
    parse_full_inventory()
