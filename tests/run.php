<?php
/**
 * Standalone Test Suite Runner for Apex SEO Platform.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

$testFiles = [
    __DIR__ . '/AutoloaderTest.php',
    __DIR__ . '/ContainerTest.php',
    __DIR__ . '/EnvironmentDetectorTest.php',
    __DIR__ . '/ServerAdapterTest.php',
    __DIR__ . '/CapabilityRegistryTest.php',
    __DIR__ . '/ConfigurationManagerTest.php',
    __DIR__ . '/MultisiteManagerTest.php',
    __DIR__ . '/DatabaseMigrationTest.php',
    __DIR__ . '/LifecycleTest.php',
    __DIR__ . '/BootstrapTest.php',
];

$totalPassed = 0;
$totalFailed = 0;
$allErrors = [];

echo "====================================================\n";
echo " Apex SEO Platform - Core Architecture Test Suite\n";
echo "====================================================\n\n";

foreach ($testFiles as $file) {
    if (!file_exists($file)) {
        echo "Skipping missing test file: {$file}\n";
        continue;
    }

    require_once $file;

    $className = 'ApexSEO\\Tests\\' . basename($file, '.php');
    if (!class_exists($className)) {
        continue;
    }

    /** @var \ApexSEO\Tests\TestCase $testInstance */
    $testInstance = new $className();
    $result = $testInstance->run();

    $totalPassed += $result['passed'];
    $totalFailed += $result['failed'];

    if ($result['failed'] > 0) {
        echo "❌ {$result['class']}: {$result['passed']} passed, {$result['failed']} FAILED\n";
        foreach ($result['errors'] as $err) {
            echo "   - {$err}\n";
            $allErrors[] = $err;
        }
    } else {
        echo "✅ {$result['class']}: {$result['passed']} passed\n";
    }
}

echo "\n----------------------------------------------------\n";
echo sprintf("Summary: %d Passed, %d Failed, %d Assertions\n", $totalPassed, $totalFailed, \ApexSEO\Tests\TestCase::$assertionCount);
echo "----------------------------------------------------\n";

if ($totalFailed > 0) {
    exit(1);
} else {
    echo "🎉 All tests passed successfully!\n";
    exit(0);
}
