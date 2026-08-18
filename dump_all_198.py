#!/usr/bin/env python3
import os
import re
import json

with open("docs/FINAL-FEATURE-INDEX.md", "r", encoding="utf-8") as f:
    lines = f.readlines()

features = []
for line in lines:
    if not line.strip().startswith("|"):
        continue
    parts = [p.strip() for p in line.split("|") if p.strip() != ""]
    if len(parts) >= 7:
        m = re.search(r'APEX-(\d{3})', parts[0])
        if m:
            fid = m.group(0)
            cat = parts[1]
            name = parts[2]
            src_prod = parts[3]
            free_pro = parts[4]
            source_path = parts[5]
            apex_target = parts[6].replace('`', '')
            features.append({
                "id": fid,
                "category": cat,
                "name": name,
                "source_prod": src_prod,
                "free_pro": free_pro,
                "source_path": source_path,
                "apex_target": apex_target
            })

print(f"Total parsed features: {len(features)}")
for idx, feat in enumerate(features):
    print(f"{feat['id']} | {feat['category']} | {feat['name']} | Target: {feat['apex_target']}")
