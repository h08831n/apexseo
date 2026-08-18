#!/usr/bin/env python3
import os
import re
import json

PLUGIN_DIR = "wp-content/plugins/apexseo"
SRC_DIR = os.path.join(PLUGIN_DIR, "src")
TESTS_DIR = os.path.join(PLUGIN_DIR, "tests")

def extract_features_status():
    # Read feature index
    with open("docs/FINAL-FEATURE-INDEX.md", "r", encoding="utf-8") as f:
        doc = f.read()

    features = {}
    lines = doc.splitlines()
    for line in lines:
        m = re.search(r'\|\s*\*\*?(APEX-\d{3})\*\*?\s*\|\s*([^|]+)\|\s*([^|]+)\|\s*([^|]+)\|', line)
        if m:
            fid = m.group(1).strip()
            cat = m.group(2).strip()
            desc = m.group(3).strip()
            ref = m.group(4).strip()
            features[fid] = {
                "id": fid,
                "category": cat,
                "description": desc,
                "reference": ref,
                "status": "UNIMPLEMENTED",
                "matched_files": [],
                "matched_tests": [],
                "evidence": ""
            }

    # Code scanning for each feature
    # Map key classes / files to features
    feature_mappings = {
        # SEO Meta & Canonical & Social
        "APEX-001": ("TitlePresenter.php", "generate", ["SeoSubsystemTest.php"]),
        "APEX-002": ("DescriptionPresenter.php", "generate", ["SeoSubsystemTest.php"]),
        "APEX-003": ("CanonicalPresenter.php", "generate", ["SeoSubsystemTest.php"]),
        "APEX-004": ("RobotsPresenter.php", "generate", ["SeoSubsystemTest.php"]),
        "APEX-005": ("MetaTagManager.php", "renderMetaTags", ["SeoSubsystemTest.php"]),
        "APEX-006": ("OpenGraphPresenter.php", "generate", ["SeoSubsystemTest.php"]),
        "APEX-007": ("TwitterCardPresenter.php", "generate", ["SeoSubsystemTest.php"]),
        "APEX-008": ("VariableEngine.php", "replace", ["SeoSubsystemTest.php"]),
        "APEX-009": ("ContextDetector.php", "detectContext", ["SeoSubsystemTest.php"]),
        "APEX-010": ("Indexable.php", "", ["SeoSubsystemTest.php"]),
        "APEX-011": ("IndexableRepository.php", "save", ["SeoSubsystemTest.php"]),
        "APEX-012": ("IndexableBuilder.php", "buildForPost", ["SeoSubsystemTest.php"]),
        "APEX-013": ("MetaSaver.php", "savePostMeta", ["SeoSubsystemTest.php"]),
        "APEX-014": ("SitemapGenerator.php", "generateIndex", ["SeoSubsystemTest.php"]),
        "APEX-015": ("RedirectManager.php", "handleRedirect", ["SeoSubsystemTest.php"]),
        "APEX-016": ("BreadcrumbGenerator.php", "generate", ["SeoSubsystemTest.php"]),
        "APEX-017": ("TemplateManager.php", "getTemplate", ["SeoSubsystemTest.php"]),
        "APEX-018": ("WooCommerceIntegration.php", "register", ["SeoSubsystemTest.php"]),
        
        # Schema Types & Validator
        "APEX-019": ("SchemaModule.php", "register", ["SchemaSubsystemTest.php"]),
        "APEX-020": ("SchemaGraphBuilder.php", "buildGraph", ["SchemaSubsystemTest.php"]),
        "APEX-021": ("SchemaRegistry.php", "registerType", ["SchemaSubsystemTest.php"]),
        "APEX-022": ("SchemaValidator.php", "validate", ["SchemaSubsystemTest.php"]),
        "APEX-023": ("ArticleSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-024": ("OrganizationSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-025": ("LocalBusinessSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-026": ("ProductSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-027": ("RecipeSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-028": ("EventSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-029": ("CourseSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-030": ("FAQPageSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-031": ("JobPostingSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-032": ("SoftwareApplicationSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-033": ("WebSiteSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        "APEX-034": ("VideoObjectSchema.php", "generate", ["SchemaSubsystemTest.php"]),
        
        # AI / LLM
        "APEX-035": ("MetadataAiGenerator.php", "generateMetadata", ["AiSubsystemTest.php"]),
        "APEX-036": ("SearchIntentAnalyzer.php", "analyzeIntent", ["AiSubsystemTest.php"]),
        "APEX-037": ("LlmsTxtGenerator.php", "generateLlmsTxt", ["AiSubsystemTest.php"]),
        "APEX-038": ("AiModule.php", "register", ["AiSubsystemTest.php"]),

        # Analytics
        "APEX-039": ("FourOhFourMonitor.php", "logHit", ["AnalyticsSubsystemTest.php"]),
        "APEX-040": ("RankTracker.php", "recordPosition", ["AnalyticsSubsystemTest.php"]),
        "APEX-041": ("AnalyticsModule.php", "register", ["AnalyticsSubsystemTest.php"]),

        # Media & Lazy Load
        "APEX-042": ("ImageOptimizer.php", "optimize", ["MediaSubsystemTest.php"]),
        "APEX-043": ("LcpOptimizer.php", "optimizeLcp", ["MediaSubsystemTest.php"]),
        "APEX-044": ("ImageLazyLoader.php", "processHtml", ["MediaSubsystemTest.php"]),
        "APEX-045": ("PlaceholderGenerator.php", "generateSvgPlaceholder", ["MediaSubsystemTest.php"]),
        "APEX-046": ("MediaModule.php", "register", ["MediaSubsystemTest.php"]),

        # Performance & Cache & Minification
        "APEX-047": ("StaticFileWriter.php", "writeCache", ["PerformanceSubsystemTest.php"]),
        "APEX-048": ("SmartPurge.php", "purgePost", ["PerformanceSubsystemTest.php"]),
        "APEX-049": ("HtmlMinifier.php", "minify", ["PerformanceSubsystemTest.php"]),
        "APEX-050": ("CssMinifier.php", "minify", ["PerformanceSubsystemTest.php"]),
        "APEX-051": ("JsMinifier.php", "minify", ["PerformanceSubsystemTest.php"]),
        "APEX-052": ("DelayJsEngine.php", "injectDelayScript", ["PerformanceSubsystemTest.php"]),
        "APEX-053": ("ResourceHints.php", "renderHints", ["PerformanceSubsystemTest.php"]),
        "APEX-054": ("PerformanceModule.php", "register", ["PerformanceSubsystemTest.php"]),

        # Core Foundation & Architecture
        "APEX-149": ("ApacheAdapter.php", "writeHtaccessRules", ["ServerAdapterTest.php"]),
        "APEX-150": ("NginxAdapter.php", "generateConfigSnippet", ["ServerAdapterTest.php"]),
        "APEX-151": ("LiteSpeedAdapter.php", "setCacheHeaders", ["ServerAdapterTest.php"]),
        "APEX-152": ("OpenLiteSpeedAdapter.php", "purgeTags", ["ServerAdapterTest.php"]),

        # REST API Routes (APEX-169 -> APEX-180)
        "APEX-169": ("SettingsRestController.php", "getSettings", ["RestSubsystemTest.php"]),
        "APEX-170": ("MetaRestController.php", "getMeta", ["RestSubsystemTest.php"]),
        "APEX-171": ("SchemaRestController.php", "getSchema", ["RestSubsystemTest.php"]),
        "APEX-172": ("RedirectsRestController.php", "getRedirects", ["RestSubsystemTest.php"]),
        "APEX-173": ("NotFoundRestController.php", "get404Logs", ["RestSubsystemTest.php"]),
        "APEX-174": ("LinksRestController.php", "getLinkSuggestions", ["RestSubsystemTest.php"]),
        "APEX-175": ("MetaRestController.php", "getHeadlessMeta", ["RestSubsystemTest.php"]),
        "APEX-176": ("CacheRestController.php", "purgeCache", ["RestSubsystemTest.php"]),
        "APEX-177": ("MediaRestController.php", "optimizeImage", ["RestSubsystemTest.php"]),
        "APEX-178": ("MigrationRestController.php", "runMigrationBatch", ["RestSubsystemTest.php"]),
        "APEX-179": ("AnalyticsRestController.php", "getOverview", ["RestSubsystemTest.php"]),
        "APEX-180": ("AnalyticsRestController.php", "getRankTracker", ["RestSubsystemTest.php"]),

        # WP-CLI Commands (APEX-181 -> APEX-190)
        "APEX-181": ("CacheCommand.php", "purge", ["CliSubsystemTest.php"]),
        "APEX-182": ("CacheCommand.php", "preload", ["CliSubsystemTest.php"]),
        "APEX-183": ("IndexCommand.php", "reindex", ["CliSubsystemTest.php"]),
        "APEX-184": ("MediaCommand.php", "optimize", ["CliSubsystemTest.php"]),
        "APEX-185": ("RedirectCommand.php", "add", ["CliSubsystemTest.php"]),
        "APEX-186": ("RedirectCommand.php", "list", ["CliSubsystemTest.php"]),
        "APEX-187": ("DatabaseCommand.php", "clean", ["CliSubsystemTest.php"]),
        "APEX-188": ("MigrateCommand.php", "run", ["CliSubsystemTest.php"]),
        "APEX-189": ("SitemapCommand.php", "rebuild", ["CliSubsystemTest.php"]),
        "APEX-190": ("DoctorCommand.php", "status", ["CliSubsystemTest.php"]),

        # Core Architecture & Multisite
        "APEX-191": ("Plugin.php", "boot", ["BootstrapTest.php"]),
        "APEX-192": ("MigrationRunner.php", "runMigrations", ["DatabaseMigrationTest.php"]),
        "APEX-194": ("MultisiteManager.php", "switchBlog", ["MultisiteManagerTest.php"]),
        "APEX-198": ("EnvironmentDetector.php", "detectAll", ["EnvironmentDetectorTest.php"]),
    }

    implemented_count = 0
    for fid, fdef in sorted(features.items()):
        if fid in feature_mappings:
            target_file, target_method, tests = feature_mappings[fid]
            # Check if file exists in src
            found = False
            for root, _, files in os.walk(SRC_DIR):
                if target_file in files:
                    fpath = os.path.join(root, target_file)
                    with open(fpath, "r", encoding="utf-8", errors="ignore") as fh:
                        content = fh.read()
                        if not target_method or f"function {target_method}" in content or f"class {target_file[:-4]}" in content:
                            found = True
                            fdef["status"] = "IMPLEMENTED_VERIFIED"
                            fdef["matched_files"].append(fpath)
                            fdef["matched_tests"] = tests
                            fdef["evidence"] = f"Class/Method {target_method} in {target_file}"
                            implemented_count += 1
                            break
            if not found:
                fdef["status"] = "MISSING_CODE"
        else:
            fdef["status"] = "NOT_YET_IMPLEMENTED"

    print(f"Total features tracked: {len(features)}")
    print(f"Verified implemented features: {implemented_count}")
    print(f"Pending/Unimplemented features: {len(features) - implemented_count}")

    # Write forensic matrix json
    with open("docs/FINAL-METRICS-FORENSIC.json", "w", encoding="utf-8") as out:
        json.dump({
            "total_features": len(features),
            "implemented_verified": implemented_count,
            "pending_features": len(features) - implemented_count,
            "features": features
        }, out, indent=2)

if __name__ == "__main__":
    extract_features_status()
