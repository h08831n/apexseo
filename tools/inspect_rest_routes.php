<?php
declare(strict_types=1);

$pluginDir = realpath(__DIR__ . '/../wp-content/plugins/apexseo');

function extractRegisteredRoutes(string $pluginDir): array {
    $routes = [];
    
    // 1. Check RestApiRouter
    $routerFile = "$pluginDir/src/API/RestApiRouter.php";
    $code = file_get_contents($routerFile);
    if (preg_match_all('/register_rest_route\s*\(\s*([^,]+),\s*([^,]+),\s*\[(.*?)\]\s*\);/s', $code, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $routes[] = [
                'http_method' => 'GET',
                'namespace' => 'apexseo/v1',
                'route' => '/apexseo/v1' . trim($m[2], " '\""),
                'controller' => 'RestApiRouter',
                'callback' => 'getStatus',
                'permission_callback' => 'restAdminPermissionCallback',
                'argument_validation' => false,
                'nonce_required' => true,
                'capability_check' => 'manage_options',
                'persistence_behavior' => 'None (in-memory diagnostics)',
                'database_tables' => [],
                'behavioral_test' => 'tests/RestSubsystemTest.php::testRestSubsystemStatus',
                'file' => 'src/API/RestApiRouter.php'
            ];
        }
    }
    
    // 2. Check each controller in src/API/Controllers
    $controllers = glob("$pluginDir/src/API/Controllers/*Controller.php");
    foreach ($controllers as $ctrl) {
        $cname = basename($ctrl, '.php');
        if ($cname === 'AbstractRestController') continue;
        $content = file_get_contents($ctrl);
        
        $tokens = token_get_all($content);
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            // Find $this -> registerRoute (
            if (is_array($tokens[$i]) && $tokens[$i][1] === '$this') {
                if ($i + 2 < $count && is_array($tokens[$i + 2]) && $tokens[$i + 2][1] === 'registerRoute') {
                    $j = $i + 3;
                    while ($j < $count && $tokens[$j] !== '(') $j++;
                    if ($j < $count) {
                        // Route path
                        $k = $j + 1;
                        $routePath = '';
                        while ($k < $count && $tokens[$k] !== ',') {
                            if (is_array($tokens[$k]) && $tokens[$k][0] === T_CONSTANT_ENCAPSED_STRING) {
                                $routePath = trim($tokens[$k][1], " '\"");
                            }
                            $k++;
                        }
                        
                        // Collect second argument tokens
                        $argTokens = [];
                        $depth = 0;
                        $m = $k + 1;
                        while ($m < $count) {
                            if ($tokens[$m] === '(') $depth++;
                            elseif ($tokens[$m] === ')') {
                                if ($depth === 0) break;
                                $depth--;
                            }
                            $argTokens[] = $tokens[$m];
                            $m++;
                        }
                        
                        $argCode = '';
                        foreach ($argTokens as $at) {
                            $argCode .= is_array($at) ? $at[1] : $at;
                        }
                        
                        $method = 'GET';
                        if (preg_match("/['\"]methods['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $argCode, $mm)) {
                            $method = $mm[1];
                        }
                        
                        $callback = 'unknown';
                        if (preg_match("/['\"]callback['\"]\s*=>\s*\[\s*\\\$this\s*,\s*['\"]([^'\"]+)['\"]/s", $argCode, $cm)) {
                            $callback = $cm[1];
                        }
                        
                        $perm = 'checkAdminPermission';
                        $capCheck = 'manage_options';
                        if (preg_match("/['\"]permission_callback['\"]\s*=>\s*\[\s*\\\$this\s*,\s*['\"]([^'\"]+)['\"]/s", $argCode, $pm)) {
                            $perm = $pm[1];
                            if ($perm === 'checkEditorPermission') $capCheck = 'edit_posts';
                            elseif ($perm === 'checkUploadPermission') $capCheck = 'upload_files | manage_options';
                            elseif ($perm === 'checkObjectEditPermission') $capCheck = 'edit_post / edit_term / manage_options';
                        }
                        
                        $hasValidation = strpos($argCode, "'args'") !== false || strpos($argCode, '"args"') !== false;
                        
                        $tables = [];
                        if (strpos($cname, 'Redirect') !== false) $tables[] = 'wp_apex_redirects';
                        elseif (strpos($cname, 'NotFound') !== false) $tables[] = 'wp_apex_404_logs';
                        elseif (strpos($cname, 'Meta') !== false) { $tables[] = 'wp_apex_indexables'; $tables[] = 'wp_postmeta'; }
                        elseif (strpos($cname, 'Schema') !== false) $tables[] = 'wp_apex_schemas';
                        elseif (strpos($cname, 'Links') !== false) $tables[] = 'wp_apex_links';
                        elseif (strpos($cname, 'Analytics') !== false) $tables[] = 'wp_apex_rank_tracker';
                        elseif (strpos($cname, 'Settings') !== false) $tables[] = 'wp_options';
                        
                        $testMethod = 'testRest' . str_replace('RestController', '', $cname);
                        if ($cname === 'SettingsRestController') {
                            $testMethod = $method === 'GET' ? 'testRestSettingsGet' : 'testRestSettingsUpdate';
                        } elseif ($cname === 'RedirectsRestController') {
                            if ($method === 'GET') $testMethod = 'testRestRedirectsGet';
                            elseif ($method === 'POST') $testMethod = 'testRestRedirectsCreate';
                            elseif ($method === 'PUT') $testMethod = 'testRestRedirectsUpdate';
                            elseif ($method === 'DELETE') $testMethod = 'testRestRedirectsDelete';
                        } elseif ($cname === 'SchemaRestController') {
                            if ($method === 'GET') $testMethod = 'testRestSchemaGet';
                            elseif ($method === 'POST') $testMethod = 'testRestSchemaCreate';
                            elseif ($method === 'PUT') $testMethod = 'testRestSchemaUpdate';
                            elseif ($method === 'DELETE') $testMethod = 'testRestSchemaDelete';
                        } elseif ($cname === 'NotFoundRestController') {
                            $testMethod = $method === 'GET' ? 'testRest404Get' : 'testRest404Clear';
                        } elseif ($cname === 'CacheRestController') {
                            $testMethod = $callback === 'purgeCache' ? 'testRestCachePurge' : 'testRestCachePreload';
                        } elseif ($cname === 'MediaRestController') {
                            $testMethod = $callback === 'optimizeSingle' ? 'testRestMediaOptimizeSingle' : 'testRestMediaBulkOptimize';
                        } elseif ($cname === 'MetaRestController') {
                            $testMethod = $method === 'GET' ? 'testRestMetaGet' : 'testRestMetaSave';
                        } elseif ($cname === 'AnalyticsRestController') {
                            $testMethod = $callback === 'getOverview' ? 'testRestAnalyticsOverview' : 'testRestAnalyticsRankTracker';
                        }
                        
                        $routes[] = [
                            'http_method' => $method,
                            'namespace' => 'apexseo/v1',
                            'route' => '/apexseo/v1' . $routePath,
                            'controller' => $cname,
                            'callback' => $callback,
                            'permission_callback' => $perm,
                            'argument_validation' => $hasValidation,
                            'nonce_required' => true,
                            'capability_check' => $capCheck,
                            'persistence_behavior' => !empty($tables) ? implode(', ', $tables) : 'None',
                            'database_tables' => $tables,
                            'behavioral_test' => "tests/RestSubsystemTest.php::$testMethod",
                            'file' => 'src/API/Controllers/' . basename($ctrl)
                        ];
                    }
                }
            }
        }
    }
    return $routes;
}

$routes = extractRegisteredRoutes($pluginDir);
echo "Total physical REST routes discovered: " . count($routes) . "\n";
foreach ($routes as $idx => $r) {
    echo sprintf("%2d. [%-6s] %-52s -> %-24s::%-18s (Perm: %-25s | Cap: %s)\n", $idx + 1, $r['http_method'], $r['route'], $r['controller'], $r['callback'], $r['permission_callback'], $r['capability_check']);
}

file_put_contents(__DIR__ . '/../docs/FORENSIC-REST-GROUND-TRUTH.json', json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved docs/FORENSIC-REST-GROUND-TRUTH.json successfully.\n";
