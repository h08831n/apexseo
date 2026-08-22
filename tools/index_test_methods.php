<?php
$pluginDir = __DIR__ . '/../wp-content/plugins/apexseo';
$testsDir = $pluginDir . '/tests';

$testFiles = glob($testsDir . '/*Test.php');
$testIndex = [];

foreach ($testFiles as $tf) {
    $content = file_get_contents($tf);
    $relName = basename($tf);
    
    // match public function testSomething(...)
    preg_match_all('/public\s+function\s+(test\w+)\s*\([^)]*\)\s*\{/m', $content, $matches, PREG_OFFSET_CAPTURE);
    
    for ($i = 0; $i < count($matches[1]); $i++) {
        $methodName = $matches[1][$i][0];
        $startPos = $matches[0][$i][1];
        
        // Find closing brace
        $braceCount = 0;
        $body = '';
        $len = strlen($content);
        $started = false;
        for ($pos = $startPos; $pos < $len; $pos++) {
            if ($content[$pos] === '{') {
                $braceCount++;
                $started = true;
            } elseif ($content[$pos] === '}') {
                $braceCount--;
                if ($started && $braceCount === 0) {
                    $body = substr($content, $startPos, $pos - $startPos + 1);
                    break;
                }
            }
        }
        
        // Extract instantiated classes and method calls
        preg_match_all('/new\s+([A-Za-z0-9_\\\\]+)/', $body, $instClasses);
        preg_match_all('/->([a-zA-Z0-9_]+)\s*\(/', $body, $calls);
        preg_match_all('/(assert[A-Za-z0-9_]+)\s*\(/', $body, $assertions);
        
        $testIndex[$relName . '::' . $methodName] = [
            'file' => $relName,
            'method' => $methodName,
            'classes_instantiated' => array_unique($instClasses[1] ?? []),
            'methods_called' => array_unique($calls[1] ?? []),
            'assertions' => array_unique($assertions[1] ?? []),
            'body_snippet' => substr($body, 0, 300)
        ];
    }
}

echo "Indexed " . count($testIndex) . " physical test methods.\n\n";
foreach ($testIndex as $key => $info) {
    echo "=== $key ===\n";
    echo "  Classes: " . implode(', ', $info['classes_instantiated']) . "\n";
    echo "  Methods: " . implode(', ', array_slice($info['methods_called'], 0, 6)) . "\n";
    echo "  Assertions: " . implode(', ', $info['assertions']) . "\n";
}
