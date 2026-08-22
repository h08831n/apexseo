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

foreach ($caps as $id => $c) {
    echo "$id | {$c['category']} | {$c['name']} | {$c['target_file']}\n";
}
