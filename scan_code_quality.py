#!/usr/bin/env python3
import os
import re

SRC_DIR = "wp-content/plugins/apexseo/src"

keywords = [
    "TODO", "FIXME", "XXX", "HACK", "stub", "placeholder", "not implemented",
    "throw new", "return null", "return []", "return false"
]

findings = {}
for root, _, files in os.walk(SRC_DIR):
    for f in files:
        if f.endswith(".php"):
            fpath = os.path.join(root, f)
            rel = os.path.relpath(fpath, "wp-content/plugins/apexseo")
            with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                lines = fh.readlines()
                for idx, line in enumerate(lines):
                    for kw in keywords:
                        if kw.lower() in line.lower():
                            if rel not in findings:
                                findings[rel] = []
                            findings[rel].append((idx + 1, kw, line.strip()))

print(f"Scanned {len(os.listdir(SRC_DIR))} directories. Files with matches: {len(findings)}")
for rel, matches in sorted(findings.items())[:15]:
    print(f"\n{rel}:")
    for m in matches[:4]:
        print(f"  Line {m[0]} [{m[1]}]: {m[2]}")
