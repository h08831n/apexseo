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

$pluginDir = __DIR__ . '/../wp-content/plugins/apexseo';
$srcDir = $pluginDir . '/src';
$testsDir = $pluginDir . '/tests';

// Load all src files and test files
$srcFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $rel = str_replace($srcDir . '/', '', $f->getPathname());
        $srcFiles[$rel] = file_get_contents($f->getPathname());
    }
}

$testFiles = [];
$itT = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));
foreach ($itT as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $rel = str_replace($testsDir . '/', '', $f->getPathname());
        $testFiles[$rel] = file_get_contents($f->getPathname());
    }
}

echo "Loaded " . count($srcFiles) . " source files and " . count($testFiles) . " test files.\n\n";

// Check specific keywords
$auditList = [
    'APEX-048' => 'TF-IDF / Content Analyzer',
    'APEX-028' => 'X-Robots-Tag / Robots.txt',
    'APEX-029' => 'Nofollow unpaginated / X-Robots',
    'APEX-030' => 'Hreflang / Special robots',
    'APEX-043' => 'News Sitemap',
    'APEX-044' => 'Video Sitemap',
    'APEX-046' => 'XSLT Sitemap',
    'APEX-047' => 'Search Engine Ping',
    'APEX-049' => 'Readability Formula',
    'APEX-050' => 'Heading Analyzer',
    'APEX-051' => 'Internal Link Graph',
    'APEX-058' => 'Fuzzy 404 Resolver',
    'APEX-060' => 'Redirect Exporter',
    'APEX-064' => 'CSV Redirect Import/Export',
    'APEX-101' => 'CSS Combiner',
    'APEX-102' => 'JS Combiner',
    'APEX-103' => 'Critical CSS',
    'APEX-104' => 'RUCSS (Unused CSS)',
    'APEX-109' => 'Font Downloader / Local Fonts',
    'APEX-119' => 'Picture Tag Rewriter'
];

foreach ($auditList as $id => $desc) {
    echo "Auditing $id ($desc) [Canonical: {$caps[$id]['name']} | Target: {$caps[$id]['target']}]:\n";
    // Search in srcFiles
    $foundIn = [];
    foreach ($srcFiles as $file => $content) {
        $targetKeyword = basename($caps[$id]['target'], '.php');
        if (stripos($content, $targetKeyword) !== false || stripos($file, $targetKeyword) !== false) {
            $foundIn[] = $file;
        }
    }
    echo "  Found in src: " . (empty($foundIn) ? "NONE" : implode(', ', $foundIn)) . "\n";
}
