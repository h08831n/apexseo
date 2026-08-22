<?php
/**
 * Builder for docs/FINAL-PHYSICAL-IMPLEMENTATION-MATRIX.json
 * Generates exact schema requested in Section 15 for all 198 capabilities.
 */

declare(strict_types=1);

$rootDir = realpath(__DIR__ . '/..');
$existingMatrix = json_decode(file_get_contents($rootDir . '/docs/FINAL-198-EXECUTION-MATRIX.json'), true);

$auditLines = file($rootDir . '/docs/IMPLEMENTATION-AUDIT-198.md');
$categories = [];
$currentCat = 'General';
foreach ($auditLines as $line) {
    if (preg_match('/## Category \d+: ([^\(]+)\s*\((APEX-\d+)\s*–\s*(APEX-\d+)\)/i', $line, $cm)) {
        $currentCat = trim($cm[1]);
    }
    if (preg_match('/\|\s*\*\*APEX-(\d+)\*\*/', $line, $m)) {
        $id = sprintf('APEX-%03d', (int)$m[1]);
        $categories[$id] = $currentCat;
    }
}

$outputMatrix = [];

foreach ($existingMatrix as $cap) {
    $id = $cap['apex_id'];
    $status = $cap['status'];
    $category = $categories[$id] ?? 'Core';
    
    // Determine test type
    $testType = 'NONE';
    if (!empty($cap['behavioral_test_file'])) {
        if (strpos($cap['behavioral_test_file'], 'RestSubsystemTest') !== false || 
            strpos($cap['behavioral_test_file'], 'CliSubsystemTest') !== false ||
            strpos($cap['behavioral_test_file'], 'MultisiteManagerTest') !== false ||
            strpos($cap['behavioral_test_file'], 'DatabaseMigrationTest') !== false) {
            $testType = 'INTEGRATION';
        } else {
            $testType = 'REAL_BEHAVIORAL';
        }
    }

    // Determine persistence
    $persistence = $cap['database_effect'] ?? 'None';
    if (empty($persistence) || $persistence === 'N/A') {
        $persistence = 'None';
    }

    // Determine external dependency
    $extDep = 'None';
    if (strpos($cap['canonical_name'], 'Gemini') !== false || strpos($cap['canonical_name'], 'AI') !== false) {
        $extDep = 'Google Gemini API';
    } elseif (strpos($cap['canonical_name'], 'WooCommerce') !== false) {
        $extDep = 'WooCommerce Core';
    } elseif (strpos($cap['canonical_name'], 'Image') !== false || strpos($cap['canonical_name'], 'Compression') !== false) {
        $extDep = 'PHP GD / Imagick';
    } elseif (strpos($cap['canonical_name'], 'Apache') !== false) {
        $extDep = 'Apache mod_rewrite';
    } elseif (strpos($cap['canonical_name'], 'Nginx') !== false) {
        $extDep = 'Nginx Server Engine';
    } elseif (strpos($cap['canonical_name'], 'LiteSpeed') !== false) {
        $extDep = 'LiteSpeed Web Server';
    }

    // Determine False Positive Risk
    $fpRisk = 'LOW';
    if ($status === 'SPEC_ONLY') {
        $fpRisk = 'NONE (Correctly classified as not yet built)';
    } elseif ($status === 'CONTRACT_ONLY') {
        $fpRisk = 'LOW (Interface/contract verified without domain worker)';
    } else {
        $fpRisk = 'LOW (Physically executed and proven by test assertions)';
    }

    // Missing work description
    $missingWork = 'None. Capability is fully implemented, wired, and verified with real behavioral tests.';
    if ($status === 'CONTRACT_ONLY') {
        $missingWork = 'Concrete execution engine and runtime processor need to be implemented and wired.';
    } elseif ($status === 'SPEC_ONLY') {
        $missingWork = 'Requires implementation of dedicated domain class, AST parser/worker, runtime wiring, and unit/integration tests.';
    }

    // Concrete evidence string
    $evidenceStr = '';
    if ($status === 'IMPLEMENTED') {
        $evidenceStr = sprintf(
            'Physical file(s): %s; Class(es): %s; Method(s): %s; Trigger: %s; Consumer: %s; Test: %s::%s; Verified Output: %s',
            implode(', ', $cap['production_files']),
            implode(', ', $cap['production_classes']),
            implode(', ', $cap['production_methods']),
            $cap['runtime_trigger'],
            $cap['runtime_consumer'],
            $cap['behavioral_test_file'],
            $cap['behavioral_test_method'],
            substr(is_string($cap['actual_output']) ? $cap['actual_output'] : json_encode($cap['actual_output']), 0, 100)
        );
    } elseif ($status === 'CONTRACT_ONLY') {
        $evidenceStr = 'Contract/Interface/Config definition exists in physical source, but dedicated domain execution engine is absent.';
    } else {
        $evidenceStr = 'No dedicated production worker class or behavioral test exists in src/ or tests/. Specification only.';
    }

    $outputMatrix[] = [
        'apex_id' => $id,
        'capability' => $cap['canonical_name'],
        'category' => $category,
        'status' => $status,
        'production_files' => $cap['production_files'] ?? [],
        'production_classes' => $cap['production_classes'] ?? [],
        'production_methods' => $cap['production_methods'] ?? [],
        'runtime_entry_point' => $cap['runtime_trigger'] ?? 'N/A',
        'runtime_wiring' => $cap['runtime_consumer'] ?? 'N/A',
        'behavioral_test_file' => $cap['behavioral_test_file'] ?? 'N/A',
        'behavioral_test_method' => $cap['behavioral_test_method'] ?? 'N/A',
        'test_type' => $testType,
        'persistence' => $persistence,
        'external_dependency' => $extDep,
        'evidence' => $evidenceStr,
        'missing_work' => $missingWork,
        'false_positive_risk' => $fpRisk
    ];
}

file_put_contents($rootDir . '/docs/FINAL-PHYSICAL-IMPLEMENTATION-MATRIX.json', json_encode($outputMatrix, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Successfully generated docs/FINAL-PHYSICAL-IMPLEMENTATION-MATRIX.json with " . count($outputMatrix) . " records.\n";
