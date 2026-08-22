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

// Let us inspect the actual src classes and methods
$pluginDir = __DIR__ . '/../wp-content/plugins/apexseo';
$srcDir = $pluginDir . '/src';
$testsDir = $pluginDir . '/tests';

// Let's create an exhaustive map of all classes and methods in src
$classMap = [];
$fileMap = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $rel = str_replace($srcDir . '/', 'src/', $f->getPathname());
        $content = file_get_contents($f->getPathname());
        
        $ns = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $nm)) {
            $ns = trim($nm[1]);
        }
        
        if (preg_match_all('/(?:abstract\s+class|class|interface)\s+(\w+)/', $content, $cm)) {
            foreach ($cm[1] as $c) {
                $fqcn = $ns ? $ns . '\\' . $c : $c;
                $classMap[$fqcn] = [
                    'file' => $rel,
                    'class' => $c,
                    'fqcn' => $fqcn
                ];
                $fileMap[$rel][] = $fqcn;
            }
        }
    }
}

echo "Total classes/interfaces mapped in src: " . count($classMap) . "\n";
echo "Total files mapped: " . count($fileMap) . "\n";

// Let us list all genuinely implemented capabilities and their exact evidence
