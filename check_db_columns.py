#!/usr/bin/env python3
import os
import re

PLUGIN_DIR = "wp-content/plugins/apexseo"
SRC_DIR = os.path.join(PLUGIN_DIR, "src")

def check_table_columns():
    redirect_usages = []
    schema_usages = []
    analytics_usages = []
    
    for root, _, files in os.walk(SRC_DIR):
        for f in files:
            if f.endswith(".php"):
                fpath = os.path.join(root, f)
                with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                    content = fh.read()
                    
                    if "apex_redirects" in content:
                        redirect_usages.append((fpath, [line.strip() for line in content.splitlines() if "apex_redirects" in line or "source_" in line or "hits" in line]))
                    if "apex_schema" in content or "apex_schema_templates" in content:
                        schema_usages.append((fpath, [line.strip() for line in content.splitlines() if "apex_schema" in line]))
                    if "apex_analytics" in content or "apex_rank_tracking" in content:
                        analytics_usages.append((fpath, [line.strip() for line in content.splitlines() if "apex_analytics" in line or "apex_rank_tracking" in line]))

    print("=== REDIRECT TABLE USAGES ===")
    for u in redirect_usages:
        print(f"File: {u[0]}")
        for l in u[1]:
            print(f"  {l}")

    print("\n=== SCHEMA TABLE USAGES ===")
    for u in schema_usages:
        print(f"File: {u[0]}")
        for l in u[1]:
            print(f"  {l}")

    print("\n=== ANALYTICS TABLE USAGES ===")
    for u in analytics_usages:
        print(f"File: {u[0]}")
        for l in u[1]:
            print(f"  {l}")

if __name__ == "__main__":
    check_table_columns()
