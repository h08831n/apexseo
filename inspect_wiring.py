#!/usr/bin/env python3
import os
import re
import json

SRC_DIR = "wp-content/plugins/apexseo/src"
TESTS_DIR = "wp-content/plugins/apexseo/tests"

def inspect_codebase():
    # 1. Map all classes and files in src
    classes = {}
    for root, _, files in os.walk(SRC_DIR):
        for f in files:
            if f.endswith(".php"):
                fpath = os.path.join(root, f)
                with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                    content = fh.read()
                    cmatches = re.findall(r'^\s*(abstract\s+class|interface|class|trait)\s+([a-zA-Z0-9_]+)', content, re.MULTILINE)
                    for cm in cmatches:
                        cname = cm[1]
                        classes[cname] = {
                            "file": fpath,
                            "type": cm[0],
                            "content": content
                        }

    # 2. Check Plugin.php and container registrations
    plugin_file = os.path.join(SRC_DIR, "Core/Bootstrap/Plugin.php")
    with open(plugin_file, "r", encoding="utf-8") as fh:
        plugin_content = fh.read()

    print(f"Total classes/interfaces in src: {len(classes)}")
    print("\n--- Modules registered in Plugin.php ---")
    for line in plugin_content.splitlines():
        if "Module" in line or "register" in line or "Container" in line:
            print("  ", line.strip())

if __name__ == "__main__":
    inspect_codebase()
