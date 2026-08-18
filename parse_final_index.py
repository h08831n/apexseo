#!/usr/bin/env python3
import os
import re
import json

def parse_final_feature_index():
    with open("docs/FINAL-FEATURE-INDEX.md", "r", encoding="utf-8") as f:
        lines = f.readlines()

    features = {}
    for line in lines:
        if not line.strip().startswith("|"):
            continue
        parts = [p.strip() for p in line.split("|")]
        # Filter out empty splits from leading/trailing pipe
        parts = [p for p in parts if p != ""]
        if len(parts) >= 8:
            id_match = re.search(r'APEX-\d{3}', parts[0])
            if id_match:
                fid = id_match.group(0)
                category = parts[1]
                feature_name = parts[2]
                source_prod = parts[3]
                free_pro = parts[4]
                source_path = parts[5]
                apex_target = parts[6]
                status = parts[7]
                dep = parts[8] if len(parts) > 8 else "None"
                features[fid] = {
                    "id": fid,
                    "category": category,
                    "name": feature_name,
                    "source_prod": source_prod,
                    "free_pro": free_pro,
                    "source_path": source_path,
                    "apex_target": apex_target,
                    "spec_status": status,
                    "dep": dep
                }

    print(f"Total features parsed: {len(features)}")
    return features

if __name__ == "__main__":
    features = parse_final_feature_index()
    for fid in sorted(features.keys())[:10]:
        print(fid, features[fid])
