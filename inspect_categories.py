#!/usr/bin/env python3
import os
import re

with open("docs/FINAL-FEATURE-INDEX.md", "r", encoding="utf-8") as f:
    lines = f.readlines()

categories = {}
current_cat = "Unknown"
for line in lines:
    if line.startswith("## Category"):
        current_cat = line.strip().replace("#", "").strip()
        categories[current_cat] = []
    elif line.strip().startswith("|") and "APEX-" in line:
        parts = [p.strip() for p in line.split("|") if p.strip() != ""]
        if len(parts) >= 3:
            m = re.search(r'APEX-\d{3}', parts[0])
            if m:
                categories[current_cat].append((m.group(0), parts[1], parts[2]))

for cat, items in categories.items():
    print(f"\n=== {cat} ({len(items)} items) ===")
    for item in items:
        print(f"  {item[0]}: {item[2]}")
