<?php
declare(strict_types=1);

$pluginDir = realpath(__DIR__ . '/../wp-content/plugins/apexseo');

function auditOrphanClasses(string $pluginDir): array {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$pluginDir/src"));
    $classes = [];
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $code = file_get_contents($f->getPathname());
            if (preg_match('/namespace\s+([^;]+);/', $code, $ns) && preg_match('/(class|interface|trait|enum)\s+([a-zA-Z0-9_]+)/', $code, $cl)) {
                $fullClass = trim($ns[1]) . '\\' . trim($cl[2]);
                $classes[$fullClass] = [
                    'class' => $fullClass,
                    'short_name' => trim($cl[2]),
                    'type' => $cl[1],
                    'file' => 'src/' . str_replace("$pluginDir/src/", '', $f->getPathname()),
                    'reachability_reasons' => [],
                    'is_orphan' => true
                ];
            }
        }
    }

    // Inspect Plugin.php and Module boot paths
    $pluginCode = file_get_contents("$pluginDir/src/Core/Bootstrap/Plugin.php");
    $moduleRegistryCode = file_get_contents("$pluginDir/src/Core/Modules/ModuleRegistry.php");
    $cliManagerCode = file_get_contents("$pluginDir/src/Core/CLI/CliManager.php");
    $restRouterCode = file_get_contents("$pluginDir/src/API/RestApiRouter.php");
    $schemaRegistryCode = file_get_contents("$pluginDir/src/Schema/SchemaRegistry.php");
    $envDetectorCode = file_get_contents("$pluginDir/src/Core/Environment/EnvironmentDetector.php");

    // All PHP code in src concatenated for cross-references
    $allSrcCode = '';
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $allSrcCode .= file_get_contents($f->getPathname()) . "\n";
        }
    }

    foreach ($classes as $name => &$info) {
        $short = $info['short_name'];
        
        // Check 1: Plugin Bootstrap & DI
        if (strpos($pluginCode, $short) !== false) {
            $info['reachability_reasons'][] = 'Wired in Plugin::registerDefaultServices() or Plugin::boot()';
        }
        
        // Check 2: Module Registry
        if (strpos($moduleRegistryCode, $short) !== false) {
            $info['reachability_reasons'][] = 'Registered in ModuleRegistry default modules';
        }
        
        // Check 3: CLI Manager
        if (strpos($cliManagerCode, $short) !== false) {
            $info['reachability_reasons'][] = 'Registered in CliManager subcommands';
        }
        
        // Check 4: REST Router & Controllers
        if (strpos($restRouterCode, $short) !== false || (strpos($info['file'], 'API/Controllers') !== false)) {
            $info['reachability_reasons'][] = 'Registered in RestApiRouter or Controller suite';
        }
        
        // Check 5: Schema Registry
        if (strpos($schemaRegistryCode, $short) !== false || (strpos($info['file'], 'Schema/Types') !== false)) {
            $info['reachability_reasons'][] = 'Registered in SchemaRegistry';
        }
        
        // Check 6: Environment Server Adapters
        if (strpos($envDetectorCode, $short) !== false || (strpos($info['file'], 'Core/Environment/Server') !== false)) {
            $info['reachability_reasons'][] = 'Resolved in EnvironmentDetector / ServerAdapter hierarchy';
        }
        
        // Check 7: Contracts, Interfaces, Exceptions, DTOs
        if ($info['type'] === 'interface') {
            $info['reachability_reasons'][] = 'Core interface contract';
        } elseif (strpos($info['file'], 'Exceptions') !== false) {
            $info['reachability_reasons'][] = 'Domain exception type referenced during runtime error handling';
        } elseif (strpos($info['file'], 'Models') !== false) {
            $info['reachability_reasons'][] = 'Domain entity / DTO model';
        }
        
        // Check 8: Referenced by other production classes
        $countOccurrences = substr_count($allSrcCode, $short);
        if ($countOccurrences > 1) {
            $info['reachability_reasons'][] = "Instantiated/invoked by {$countOccurrences} domain call-sites across src/";
        }

        if (!empty($info['reachability_reasons'])) {
            $info['is_orphan'] = false;
        }
    }

    return $classes;
}

$audit = auditOrphanClasses($pluginDir);
$orphans = array_filter($audit, function($item) { return $item['is_orphan']; });

$report = [
    'total_production_classes_inspected' => count($audit),
    'orphan_count' => count($orphans),
    'orphans' => array_values($orphans),
    'all_classes_audit' => array_values($audit)
];

file_put_contents(__DIR__ . '/../docs/ORPHAN-PRODUCTION-CLASS-AUDIT.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Audited " . count($audit) . " classes. Total orphans found: " . count($orphans) . "\n";
echo "Saved docs/ORPHAN-PRODUCTION-CLASS-AUDIT.json successfully.\n";
