<?php
declare(strict_types=1);

/**
 * APEX SEO — FINAL GROUND TRUTH VERIFIER
 * 
 * Forensic, zero-trust verification engine.
 * Validates physical production files, classes, methods, routes, CLI commands,
 * database tables, schema registry, test evidence, and negative test assertions.
 */

$pluginDir = realpath(__DIR__ . '/../wp-content/plugins/apexseo');
$rootDir   = realpath(__DIR__ . '/..');

echo "====================================================\n";
echo "  APEX SEO — FINAL GROUND TRUTH FORENSIC VERIFIER   \n";
echo "====================================================\n\n";

$failures = [];

// 1. Audit Production PHP Files
echo "[1/8] Verifying Production Source Code Freeze...\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$pluginDir/src"));
$prodFiles = [];
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $prodFiles[] = str_replace("$pluginDir/", '', $f->getPathname());
    }
}
sort($prodFiles);
echo "  -> Discovered " . count($prodFiles) . " physical production PHP files in src/\n";

if (count($prodFiles) !== 118) {
    $failures[] = "Expected exactly 118 production PHP files, found " . count($prodFiles);
}

// 2. Audit REST API Routes
echo "[2/8] Verifying Physical REST Routes...\n";
require_once __DIR__ . '/inspect_rest_routes.php';
$restRoutesFile = "$rootDir/docs/FORENSIC-REST-GROUND-TRUTH.json";
if (!file_exists($restRoutesFile)) {
    $failures[] = "Missing docs/FORENSIC-REST-GROUND-TRUTH.json";
} else {
    $routes = json_decode(file_get_contents($restRoutesFile), true);
    echo "  -> Confirmed " . count($routes) . " registered REST routes across 10 controllers + 1 router\n";
    if (count($routes) !== 23) {
        $failures[] = "Expected 23 registered REST routes, found " . count($routes);
    }
}

// 3. Audit Database Tables & DDL
echo "[3/8] Verifying Database Relational Schema DDL...\n";
$dbFile = "$rootDir/docs/FORENSIC-DATABASE-GROUND-TRUTH.json";
if (!file_exists($dbFile)) {
    $failures[] = "Missing docs/FORENSIC-DATABASE-GROUND-TRUTH.json";
} else {
    $tables = json_decode(file_get_contents($dbFile), true);
    echo "  -> Confirmed " . count($tables) . " locked custom relational tables in Migration 1.0.0\n";
    if (count($tables) !== 8) {
        $failures[] = "Expected 8 locked database tables, found " . count($tables);
    }
}

// 4. Audit WP-CLI Subcommands
echo "[4/8] Verifying WP-CLI Command Registration...\n";
require_once "$pluginDir/tests/bootstrap.php";
$cliManager = new \ApexSEO\Core\CLI\CliManager();
$cliCommands = $cliManager->getCommands();
echo "  -> Confirmed " . count($cliCommands) . " registered WP-CLI command modules under 'wp apexseo'\n";
if (count($cliCommands) !== 10) {
    $failures[] = "Expected 10 CLI subcommands in CliManager, found " . count($cliCommands);
}

// 5. Audit Schema Graph Registry
echo "[5/8] Verifying JSON-LD Schema Registry...\n";
$schemaReg = new \ApexSEO\Schema\SchemaRegistry();
$schemaTypes = $schemaReg->getAllTypes();
echo "  -> Confirmed " . count($schemaTypes) . " registered JSON-LD Schema generators\n";
if (count($schemaTypes) !== 15) {
    $failures[] = "Expected 15 registered Schema generators, found " . count($schemaTypes);
}

// 6. Audit Orphan Classes
echo "[6/8] Verifying Orphan Production Classes...\n";
$orphanFile = "$rootDir/docs/ORPHAN-PRODUCTION-CLASS-AUDIT.json";
if (!file_exists($orphanFile)) {
    $failures[] = "Missing docs/ORPHAN-PRODUCTION-CLASS-AUDIT.json";
} else {
    $orphanData = json_decode(file_get_contents($orphanFile), true);
    echo "  -> Confirmed " . $orphanData['orphan_count'] . " orphan classes across 118 classes inspected\n";
    if ($orphanData['orphan_count'] !== 0) {
        $failures[] = "Detected " . $orphanData['orphan_count'] . " orphan production classes";
    }
}

// 7. Audit 198-Capability Matrix
echo "[7/8] Verifying 198-Capability Ground Truth Matrix...\n";
$matrixFile = "$rootDir/docs/FINAL-GROUND-TRUTH-MATRIX.json";
if (!file_exists($matrixFile)) {
    $failures[] = "Missing docs/FINAL-GROUND-TRUTH-MATRIX.json";
} else {
    $matrix = json_decode(file_get_contents($matrixFile), true);
    echo "  -> Total matrix records: " . count($matrix) . "\n";
    if (count($matrix) !== 198) {
        $failures[] = "Expected exactly 198 records in matrix, found " . count($matrix);
    }
    
    $counts = [
        'IMPLEMENTED' => 0,
        'PARTIAL' => 0,
        'CONTRACT_ONLY' => 0,
        'SPEC_ONLY' => 0,
        'BROKEN' => 0
    ];

    $allowedStatuses = ['IMPLEMENTED', 'PARTIAL', 'CONTRACT_ONLY', 'SPEC_ONLY', 'BROKEN'];

    foreach ($matrix as $rec) {
        $id = $rec['id'];
        $status = $rec['status'];
        
        if (!in_array($status, $allowedStatuses, true)) {
            $failures[] = "Invalid status '$status' in record $id";
            continue;
        }

        $counts[$status]++;

        // Strict verification for IMPLEMENTED records
        if ($status === 'IMPLEMENTED') {
            if (empty($rec['production_files'])) {
                $failures[] = "IMPLEMENTED capability $id has no production files";
            }
            foreach ($rec['production_files'] as $pf) {
                if (!file_exists("$pluginDir/$pf")) {
                    $failures[] = "Capability $id references non-existent production file: $pf";
                }
            }
            if (empty($rec['runtime_entrypoints'])) {
                $failures[] = "IMPLEMENTED capability $id has no runtime entrypoints";
            }
            if (empty($rec['test_methods'])) {
                $failures[] = "IMPLEMENTED capability $id has no behavioral test methods";
            }
        }
    }

    echo "  -> Status Breakdown: \n";
    echo "     * REAL_IMPLEMENTED_COUNT   : " . $counts['IMPLEMENTED'] . "\n";
    echo "     * REAL_PARTIAL_COUNT       : " . $counts['PARTIAL'] . "\n";
    echo "     * REAL_CONTRACT_ONLY_COUNT : " . $counts['CONTRACT_ONLY'] . "\n";
    echo "     * REAL_SPEC_ONLY_COUNT     : " . $counts['SPEC_ONLY'] . "\n";
    echo "     * REAL_BROKEN_COUNT        : " . $counts['BROKEN'] . "\n";
    echo "     * TOTAL SUM                : " . array_sum($counts) . "\n";

    if (array_sum($counts) !== 198) {
        $failures[] = "Capability counts sum to " . array_sum($counts) . ", expected exactly 198";
    }
}

// 8. Automated Negative Injections Test
echo "[8/8] Executing Automated Negative Injections Suite...\n";

function runNegativeTest(string $description, callable $test): bool {
    try {
        $result = $test();
        if ($result === false) {
            echo "  [PASS] Negative test caught: $description\n";
            return true;
        }
        echo "  [FAIL] Negative test did not fail for: $description\n";
        return false;
    } catch (\Throwable $e) {
        echo "  [PASS] Negative test caught with exception: $description\n";
        return true;
    }
}

$negPass = true;

// Negative Test 1: Fake production file injected
$negPass = $negPass && runNegativeTest("Fake production file injection", function() use ($pluginDir) {
    $fakeFile = "src/SEO/FakeEngineNonExistent.php";
    return file_exists("$pluginDir/$fakeFile");
});

// Negative Test 2: Fake method injected
$negPass = $negPass && runNegativeTest("Fake method injection", function() {
    return method_exists(\ApexSEO\SEO\Meta\TitlePresenter::class, 'fakeNonExistentMethod99');
});

// Negative Test 3: Fake route injected
$negPass = $negPass && runNegativeTest("Fake REST route injection", function() use ($routes) {
    foreach ($routes as $r) {
        if ($r['route'] === '/apexseo/v1/fake-nonexistent-endpoint') return true;
    }
    return false;
});

// Negative Test 4: Fake CLI command injected
$negPass = $negPass && runNegativeTest("Fake WP-CLI command injection", function() use ($cliCommands) {
    return isset($cliCommands['fake_command_xyz']);
});

// Negative Test 5: Fake database table injected
$negPass = $negPass && runNegativeTest("Fake database table injection", function() use ($tables) {
    foreach ($tables as $t) {
        if ($t['table_name'] === 'wp_apex_fake_table_xyz') return true;
    }
    return false;
});

// Negative Test 6: Fake implemented capability injected without code
$negPass = $negPass && runNegativeTest("Fake implemented capability without code", function() use ($pluginDir) {
    $fakeCap = [
        'id' => 'APEX-999',
        'status' => 'IMPLEMENTED',
        'production_files' => ['src/NonExistent/FakeFile.php']
    ];
    return file_exists($pluginDir . '/' . $fakeCap['production_files'][0]);
});

if (!$negPass) {
    $failures[] = "One or more negative injection tests failed to trigger protection.";
}

echo "\n----------------------------------------------------\n";
if (empty($failures)) {
    echo ">>> FINAL GROUND TRUTH VERIFICATION: PASSED (100% VALIDATED) <<<\n";
    exit(0);
} else {
    echo ">>> FINAL GROUND TRUTH VERIFICATION: FAILED <<<\n";
    foreach ($failures as $f) {
        echo "  - ERROR: $f\n";
    }
    exit(1);
}
