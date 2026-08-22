<?php
$lines = file(__DIR__ . '/../docs/IMPLEMENTATION-AUDIT-198.md');
$caps = [];
foreach ($lines as $line) {
    if (preg_match('/\|\s*\*\*APEX-(\d+)\*\*\s*\|\s*([^|]+)\|\s*`([^`]+)`\s*\|/i', $line, $m)) {
        $id = sprintf('APEX-%03d', (int)$m[1]);
        $name = trim($m[2]);
        $target = trim($m[3]);
        $caps[$id] = ['name' => $name, 'target' => $target];
    }
}
echo "Parsed " . count($caps) . " capabilities.\n";
foreach (array_slice($caps, 0, 10, true) as $id => $d) {
    echo "$id: {$d['name']} -> {$d['target']}\n";
}
