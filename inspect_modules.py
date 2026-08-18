#!/usr/bin/env python3
import os
import re

SRC_DIR = "wp-content/plugins/apexseo/src"

modules = [
    "SEO/SeoModule.php",
    "Schema/SchemaModule.php",
    "Performance/PerformanceModule.php",
    "Media/MediaModule.php",
    "AI/AiModule.php",
    "Analytics/AnalyticsModule.php",
    "API/RestApiRouter.php",
    "Core/CLI/CliManager.php",
    "Core/Bootstrap/Plugin.php",
    "Core/REST/RestManager.php",
    "Core/Hooks/HookManager.php"
]

for m in modules:
    path = os.path.join(SRC_DIR, m)
    if os.path.exists(path):
        print(f"=== {m} ===")
        with open(path, "r", encoding="utf-8") as f:
            content = f.read()
            # print functions / hook registrations
            for line in content.splitlines():
                if "add_action" in line or "add_filter" in line or "register_rest_route" in line or "WP_CLI::add_command" in line or "singleton" in line or "set(" in line or "bind" in line or "new " in line:
                    print("  ", line.strip())
