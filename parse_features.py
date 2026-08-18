#!/usr/bin/env python3
import re
import os

def parse_feature_index():
    path = "docs/FINAL-FEATURE-INDEX.md"
    if not os.path.exists(path):
        path = "docs/02-COMPLETE-FEATURE-INVENTORY.md"
        
    features = {}
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()
        
    # Match lines like: APEX-001 | Title Presenter | ... or ### APEX-001
    for line in content.splitlines():
        m = re.search(r'(APEX-\d{3})', line)
        if m:
            apex_id = m.group(1)
            if apex_id not in features:
                features[apex_id] = line.strip()
                
    print(f"Total APEX IDs found in docs: {len(features)}")
    for k in sorted(features.keys()):
        print(f"{k}: {features[k][:80]}")

if __name__ == "__main__":
    parse_feature_index()
