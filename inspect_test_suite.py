#!/usr/bin/env python3
import os
import re

TESTS_DIR = "wp-content/plugins/apexseo/tests"

def inspect_tests():
    test_files = [f for f in os.listdir(TESTS_DIR) if f.endswith("Test.php")]
    total_assertions = 0
    total_test_methods = 0
    
    print(f"Total test files: {len(test_files)}")
    for tf in sorted(test_files):
        fpath = os.path.join(TESTS_DIR, tf)
        with open(fpath, "r", encoding="utf-8") as fh:
            content = fh.read()
            methods = re.findall(r'public\s+function\s+(test[a-zA-Z0-9_]+)', content)
            assertions = re.findall(r'\$this->assert[a-zA-Z0-9_]+', content)
            total_test_methods += len(methods)
            total_assertions += len(assertions)
            print(f"  {tf}: {len(methods)} test methods, {len(assertions)} assertions")
            
    print(f"\nTotal Test Methods: {total_test_methods}")
    print(f"Total Assertions: {total_assertions}")

if __name__ == "__main__":
    inspect_tests()
