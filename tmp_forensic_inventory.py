#!/usr/bin/env python3
import os
import re
import glob

PLUGIN_DIR = "wp-content/plugins/apexseo"
SRC_DIR = os.path.join(PLUGIN_DIR, "src")
TESTS_DIR = os.path.join(PLUGIN_DIR, "tests")

def analyze_php_files():
    php_files = []
    for root, _, files in os.walk(PLUGIN_DIR):
        for f in files:
            if f.endswith(".php"):
                php_files.append(os.path.join(root, f))
    
    src_files = [f for f in php_files if f.startswith(SRC_DIR) or f in [os.path.join(PLUGIN_DIR, "apexseo.php"), os.path.join(PLUGIN_DIR, "uninstall.php")]]
    test_files = [f for f in php_files if f.startswith(TESTS_DIR)]
    
    print(f"Total PHP files: {len(php_files)}")
    print(f"Production PHP files: {len(src_files)}")
    print(f"Test PHP files: {len(test_files)}")
    
    classes = []
    interfaces = []
    abstract_classes = []
    methods_count = 0
    todos = []
    empty_methods = []
    
    for fpath in src_files:
        with open(fpath, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
            lines = content.splitlines()
            
            # Find classes, interfaces, abstract classes
            for m in re.finditer(r'^\s*(abstract\s+class|interface|class|trait)\s+([a-zA-Z0-9_]+)', content, re.MULTILINE):
                ctype = m.group(1).strip()
                cname = m.group(2).strip()
                if "interface" in ctype:
                    interfaces.append((cname, fpath))
                elif "abstract class" in ctype:
                    abstract_classes.append((cname, fpath))
                elif "class" in ctype:
                    classes.append((cname, fpath))
            
            # Find TODO / FIXME
            for idx, line in enumerate(lines, 1):
                if re.search(r'\b(TODO|FIXME|XXX|HACK)\b', line, re.IGNORECASE):
                    todos.append((fpath, idx, line.strip()))
                    
            # Find empty methods
            for m in re.finditer(r'(public|protected|private)?\s*function\s+([a-zA-Z0-9_]+)\s*\([^)]*\)\s*\{\s*\}', content):
                empty_methods.append((fpath, m.group(2)))
                
    print(f"\nExecutable Classes: {len(classes)}")
    print(f"Abstract Classes: {len(abstract_classes)}")
    print(f"Interfaces: {len(interfaces)}")
    print(f"TODO/FIXME count: {len(todos)}")
    for t in todos:
        print(f"  {t[0]}:{t[1]} -> {t[2]}")
    print(f"Empty methods count: {len(empty_methods)}")
    for em in empty_methods:
        print(f"  {em[0]} -> function {em[1]}()")

if __name__ == "__main__":
    analyze_php_files()
