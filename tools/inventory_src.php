<?php
$pluginDir = __DIR__ . '/../wp-content/plugins/apexseo';
$srcDir = $pluginDir . '/src';
$testsDir = $pluginDir . '/tests';

$srcFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $rel = str_replace($srcDir . '/', '', $f->getPathname());
        $content = file_get_contents($f->getPathname());
        
        // extract classes, methods
        preg_match_all('/class\s+(\w+)/', $content, $cm);
        preg_match_all('/function\s+(\w+)\s*\(/', $content, $fm);
        
        $srcFiles[$rel] = [
            'classes' => $cm[1] ?? [],
            'methods' => $fm[1] ?? [],
            'length' => strlen($content)
        ];
    }
}

echo "=== PHYSICAL SOURCE INVENTORY (" . count($srcFiles) . " files) ===\n";
foreach ($srcFiles as $file => $info) {
    echo "$file: Classes: [" . implode(', ', $info['classes']) . "] Methods: [" . implode(', ', array_slice($info['methods'], 0, 5)) . (count($info['methods']) > 5 ? "..." : "") . "]\n";
}
