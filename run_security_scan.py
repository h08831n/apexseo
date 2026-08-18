#!/usr/bin/env python3
"""
Full Forensic Analyzer for APEX SEO (APEX-001 -> APEX-198)
"""
import os
import re
import json

PLUGIN_DIR = "wp-content/plugins/apexseo"
SRC_DIR = os.path.join(PLUGIN_DIR, "src")
TESTS_DIR = os.path.join(PLUGIN_DIR, "tests")
DOCS_DIR = "docs"

def main():
    print("=== APEX SEO COMPREHENSIVE FORENSIC CODE-FIRST AUDIT ===")
    
    # 1. Audit Security Functions / Risky Patterns
    risky_patterns = [
        r'\beval\s*\(',
        r'\bbase64_decode\s*\(',
        r'\bunserialize\s*\(',
        r'\binclude\s+\$_',
        r'\brequire\s+\$_',
        r'\bfile_get_contents\s*\(',
        r'\bfile_put_contents\s*\(',
        r'\bunlink\s*\(',
        r'\brename\s*\(',
        r'\bcopy\s*\(',
        r'\bshell_exec\s*\(',
        r'\bexec\s*\(',
        r'\bsystem\s*\(',
        r'\bpassthru\s*\(',
        r'\bproc_open\s*\(',
        r'\bcurl_exec\s*\(',
        r'\bwp_remote_get\s*\(',
        r'\bwp_remote_post\s*\(',
        r'->query\s*\(',
        r'->get_results\s*\(',
        r'->get_row\s*\(',
        r'->get_var\s*\(',
        r'->get_col\s*\('
    ]
    
    security_findings = []
    
    for root, _, files in os.walk(SRC_DIR):
        for f in files:
            if f.endswith(".php"):
                fpath = os.path.join(root, f)
                with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                    lines = fh.readlines()
                    for idx, line in enumerate(lines, 1):
                        for pat in risky_patterns:
                            if re.search(pat, line):
                                security_findings.append({
                                    "file": fpath,
                                    "line": idx,
                                    "pattern": pat,
                                    "code": line.strip()
                                })
                                
    print(f"Total security pattern occurrences in src/: {len(security_findings)}")
    
    # Group findings by pattern
    pat_counts = {}
    for sf in security_findings:
        p = sf["pattern"]
        pat_counts[p] = pat_counts.get(p, 0) + 1
    for p, c in sorted(pat_counts.items(), key=lambda x: -x[1]):
        print(f"  {p}: {c}")

if __name__ == "__main__":
    main()
