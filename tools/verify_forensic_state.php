<?php
/**
 * Authoritative Independent Forensic State Verification Tool for Apex SEO.
 * Exits 0 on strict consistency; Exits 1 on any discrepancy.
 */

$pluginDir = dirname(__DIR__) . '/wp-content/plugins/apexseo';
$srcDir    = $pluginDir . '/src';
$testsDir  = $pluginDir . '/tests';
$docsDir   = dirname(__DIR__) . '/docs';

$errors = [];
$checks = [];

echo "====================================================\n";
echo "APEX SEO — INDEPENDENT FORENSIC VERIFICATION RUNNER\n";
echo "====================================================\n\n";

// 1. Check Authoritative JSON Existence
$authJsonPath = $docsDir . '/AUTHORITATIVE-FORENSIC-STATE.json';
if (!file_exists($authJsonPath)) {
    echo "[-] FATAL: AUTHORITATIVE-FORENSIC-STATE.json missing.\n";
    exit(1);
}

$authState = json_decode(file_get_contents($authJsonPath), true);
if (!$authState || !isset($authState['metrics'])) {
    echo "[-] FATAL: Invalid JSON structure in AUTHORITATIVE-FORENSIC-STATE.json.\n";
    exit(1);
}

$expected = $authState['metrics'];

// 2. Verify PHP File Counts
$srcPhp = glob($srcDir . '/**/*.php');
$srcPhpCount = count(glob_recursive($srcDir, '*.php'));
$testsPhpCount = count(glob_recursive($testsDir, '*.php'));
$rootPhpCount = 0;
if (file_exists($pluginDir . '/apexseo.php')) $rootPhpCount++;
if (file_exists($pluginDir . '/uninstall.php')) $rootPhpCount++;
$totalPhpCount = $srcPhpCount + $testsPhpCount + $rootPhpCount;

if ($srcPhpCount !== $expected['src_php_files']) {
    $errors[] = "src/ PHP files mismatch: Found {$srcPhpCount}, expected {$expected['src_php_files']}";
} else {
    $checks[] = "[+] src/ PHP files verified: {$srcPhpCount}";
}

if ($testsPhpCount !== $expected['test_php_files']) {
    $errors[] = "tests/ PHP files mismatch: Found {$testsPhpCount}, expected {$expected['test_php_files']}";
} else {
    $checks[] = "[+] tests/ PHP files verified: {$testsPhpCount}";
}

if ($totalPhpCount !== $expected['total_php_files']) {
    $errors[] = "Total PHP files mismatch: Found {$totalPhpCount}, expected {$expected['total_php_files']}";
} else {
    $checks[] = "[+] Total PHP files verified: {$totalPhpCount}";
}

// 3. Verify Database Tables in Migration
$migrationFile = $srcDir . '/Core/Database/Migrations/Migration_1_0_0_CreateLockedTables.php';
$migrationContent = file_get_contents($migrationFile);
preg_match_all('/CREATE TABLE IF NOT EXISTS `?\{\$prefix\}apex_([a-zA-Z0-9_]+)`?/', $migrationContent, $tableMatches);
$tableCount = count($tableMatches[1]);

if ($tableCount !== $expected['database_tables']) {
    $errors[] = "Database table count mismatch: Found {$tableCount}, expected {$expected['database_tables']}";
} else {
    $checks[] = "[+] Locked database tables verified: {$tableCount}";
}

// 4. Verify REST Route Count
$restRoutesJson = json_decode(file_get_contents($docsDir . '/REST-ROUTE-MATRIX-AUTHORITATIVE.json'), true);
$restCount = count($restRoutesJson);
if ($restCount !== $expected['rest_routes']) {
    $errors[] = "REST routes count mismatch: Found {$restCount}, expected {$expected['rest_routes']}";
} else {
    $checks[] = "[+] REST routes verified: {$restCount}";
}

// 5. Verify WP-CLI Commands Count
$cliJson = json_decode(file_get_contents($docsDir . '/WPCLI-MATRIX-AUTHORITATIVE.json'), true);
$cliCount = count($cliJson);
if ($cliCount !== $expected['wp_cli_commands']) {
    $errors[] = "WP-CLI commands count mismatch: Found {$cliCount}, expected {$expected['wp_cli_commands']}";
} else {
    $checks[] = "[+] WP-CLI commands verified: {$cliCount}";
}

// 6. Verify Schema Types Count
$schemaFiles = glob($srcDir . '/Schema/Types/*Schema.php');
$schemaMediaFiles = glob($srcDir . '/Schema/Media/*Schema.php');
$schemaCount = 0;
foreach (array_merge($schemaFiles, $schemaMediaFiles) as $sf) {
    if (strpos($sf, 'Abstract') === false) {
        $schemaCount++;
    }
}
if ($schemaCount !== $expected['schema_types']) {
    $errors[] = "Schema types count mismatch: Found {$schemaCount}, expected {$expected['schema_types']}";
} else {
    $checks[] = "[+] Schema types verified: {$schemaCount}";
}

// 7. Verify Feature Counts Math (Must equal 198)
$featureCounts = $expected['feature_counts'];
$sumFeatures = array_sum($featureCounts);
if ($sumFeatures !== 198) {
    $errors[] = "Feature totals do not equal 198: Sum is {$sumFeatures}";
} else {
    $checks[] = "[+] 198-Feature sum verified: {$sumFeatures} (Implemented: {$featureCounts['IMPLEMENTED']}, Partial: {$featureCounts['PARTIAL']}, Spec-Only: {$featureCounts['SPEC_ONLY']})";
}

// Output Results
foreach ($checks as $c) {
    echo $c . "\n";
}

if (!empty($errors)) {
    echo "\n[-] VERIFICATION FAILED WITH " . count($errors) . " ERRORS:\n";
    foreach ($errors as $e) {
        echo "  - " . $e . "\n";
    }
    exit(1);
}

echo "\n[SUCCESS] ALL FORENSIC STATE METRICS VERIFIED AND MATHEMATICALLY CONSISTENT.\n";
exit(0);

/**
 * Recursive glob helper
 */
function glob_recursive($dir, $pattern) {
    $files = glob($dir . '/' . $pattern);
    foreach (glob($dir . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $subDir) {
        $files = array_merge($files, glob_recursive($subDir, $pattern));
    }
    return $files;
}
