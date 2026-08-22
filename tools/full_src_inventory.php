<?php
$pluginDir = __DIR__ . '/../wp-content/plugins/apexseo';
$srcDir = $pluginDir . '/src';

$srcFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $rel = str_replace($srcDir . '/', '', $f->getPathname());
        $content = file_get_contents($f->getPathname());
        
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $nm)) {
            $namespace = trim($nm[1]);
        }
        
        $classes = [];
        if (preg_match_all('/(?:abstract\s+class|class|interface|trait)\s+(\w+)/', $content, $cm)) {
            $classes = $cm[1];
        }
        
        $srcFiles[$rel] = [
            'ns' => $namespace,
            'classes' => $classes,
        ];
    }
}
ksort($srcFiles);
echo "Total source files: " . count($srcFiles) . "\n\n";
foreach ($srcFiles as $file => $info) {
    echo sprintf("%-45s | %-35s | %s\n", $file, $info['ns'], implode(', ', $info['classes']));
}
