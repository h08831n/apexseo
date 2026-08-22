<?php
$auditLines = file(__DIR__ . '/../docs/IMPLEMENTATION-AUDIT-198.md');
$caps = [];
$currentCategory = '';
foreach ($auditLines as $line) {
    if (preg_match('/## Category \d+: ([^\(]+)\s*\((APEX-\d+)\s*–\s*(APEX-\d+)\)/i', $line, $cm)) {
        $currentCategory = trim($cm[1]);
    }
    if (preg_match('/\|\s*\*\*APEX-(\d+)\*\*\s*\|\s*([^|]+)\|\s*`([^`]+)`\s*\|\s*([^|]+)\|\s*([^|]+)\|\s*([^|]+)\|\s*`?([A-Z_]+)`?\s*\|\s*([^|]+)\|/i', $line, $m)) {
        $id = sprintf('APEX-%03d', (int)$m[1]);
        $name = trim($m[2]);
        $targetFile = trim($m[3]);
        $hasSrc = trim($m[4]);
        $hasRuntime = trim($m[5]);
        $testStatus = trim($m[6]);
        $originalStatus = trim($m[7]);
        $notes = trim($m[8]);
        
        $caps[$id] = [
            'id' => $id,
            'name' => $name,
            'target_file' => $targetFile,
            'category' => $currentCategory,
            'original_audit_status' => $originalStatus,
            'notes' => $notes
        ];
    }
}

echo "Total capabilities parsed: " . count($caps) . "\n";

// Let us inspect the actual src files and methods
$pluginDir = __DIR__ . '/../wp-content/plugins/apexseo';
$srcDir = $pluginDir . '/src';
$testsDir = $pluginDir . '/tests';

// Let's create an accurate map of which APEX IDs are genuinely implemented in src/
// We can check each capability against real classes and methods in src.

