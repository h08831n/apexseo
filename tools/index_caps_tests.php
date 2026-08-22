<?php
$lines = file(__DIR__ . '/../docs/IMPLEMENTATION-AUDIT-198.md');
$caps = [];
$cat = '';
foreach ($lines as $line) {
    if (preg_match('/## Category \d+: ([^\(]+)\s*\((APEX-\d+)\s*–\s*(APEX-\d+)\)/i', $line, $cm)) {
        $cat = trim($cm[1]);
    }
    if (preg_match('/\|\s*\*\*APEX-(\d+)\*\*\s*\|\s*([^|]+)\|\s*`([^`]+)`\s*\|\s*([^|]+)\|\s*([^|]+)\|\s*([^|]+)\|\s*`?([A-Z_]+)`?\s*\|\s*([^|]+)\|/i', $line, $m)) {
        $id = sprintf('APEX-%03d', (int)$m[1]);
        $caps[$id] = [
            'id' => $id,
            'name' => trim($m[2]),
            'target_file' => trim($m[3]),
            'category' => $cat,
            'status_note' => trim($m[8])
        ];
    }
}

$pluginDir = __DIR__ . '/../wp-content/plugins/apexseo';
$srcDir = $pluginDir . '/src';
$testsDir = $pluginDir . '/tests';

// Load test method bodies
$testMethods = [];
$testFiles = glob($testsDir . '/*Test.php');
foreach ($testFiles as $tf) {
    $content = file_get_contents($tf);
    $relName = basename($tf);
    preg_match_all('/public\s+function\s+(test\w+)\s*\(\s*\)\s*:\s*void\s*\{([\s\S]*?)\n\s*\}/m', $content, $tm, PREG_SET_ORDER);
    foreach ($tm as $match) {
        $testMethods[$relName . '::' . $match[1]] = [
            'file' => $relName,
            'method' => $match[1],
            'body' => $match[2]
        ];
    }
}

echo "Indexed " . count($testMethods) . " physical test methods.\n";

// Let us inspect every single APEX ID
$results = [];
foreach ($caps as $id => $cap) {
    $results[$id] = [
        'id' => $id,
        'name' => $cap['name'],
        'category' => $cap['category'],
        'target_file' => $cap['target_file'],
    ];
}

file_put_contents(__DIR__ . '/../tools/caps_parsed.json', json_encode($caps, JSON_PRETTY_PRINT));
echo "Saved caps_parsed.json\n";
