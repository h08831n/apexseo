#!/usr/bin/env python3
import json
import os

with open('tools/production_hashes.json', 'r') as fh:
    manifest = json.load(fh)

files = manifest if isinstance(manifest, dict) else {}
missing = []
present = []

for rel_path in files.keys():
    if os.path.exists(rel_path):
        present.append(rel_path)
    else:
        missing.append(rel_path)

print(f"Total files in baseline: {len(files)}")
print(f"Present files: {len(present)}")
print(f"Missing files: {len(missing)}")

if missing:
    print("Missing list:")
    for m in missing:
        print(f" - {m}")
