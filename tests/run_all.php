<?php
/**
 * Apex SEO Test Suite Runner
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

// Core test cases
require_once __DIR__ . '/AutoloaderTest.php';
require_once __DIR__ . '/ContainerTest.php';
require_once __DIR__ . '/CapabilityRegistryTest.php';
require_once __DIR__ . '/ConfigurationManagerTest.php';
require_once __DIR__ . '/EnvironmentDetectorTest.php';
require_once __DIR__ . '/ServerAdapterTest.php';
require_once __DIR__ . '/DatabaseMigrationTest.php';
require_once __DIR__ . '/MultisiteManagerTest.php';
require_once __DIR__ . '/BootstrapTest.php';
require_once __DIR__ . '/LifecycleTest.php';

// Subsystems test cases
require_once __DIR__ . '/SeoSubsystemTest.php';
require_once __DIR__ . '/AnalysisSubsystemTest.php';
require_once __DIR__ . '/SchemaSubsystemTest.php';
require_once __DIR__ . '/PerformanceSubsystemTest.php';
require_once __DIR__ . '/MediaSubsystemTest.php';
require_once __DIR__ . '/MediaFailureContractTest.php';
require_once __DIR__ . '/AiSubsystemTest.php';
require_once __DIR__ . '/AnalyticsSubsystemTest.php';
require_once __DIR__ . '/RestSubsystemTest.php';
require_once __DIR__ . '/CliSubsystemTest.php';

$testClasses = [
    ApexSEO\Tests\AutoloaderTest::class,
    ApexSEO\Tests\ContainerTest::class,
    ApexSEO\Tests\CapabilityRegistryTest::class,
    ApexSEO\Tests\ConfigurationManagerTest::class,
    ApexSEO\Tests\EnvironmentDetectorTest::class,
    ApexSEO\Tests\ServerAdapterTest::class,
    ApexSEO\Tests\DatabaseMigrationTest::class,
    ApexSEO\Tests\MultisiteManagerTest::class,
    ApexSEO\Tests\BootstrapTest::class,
    ApexSEO\Tests\LifecycleTest::class,
    ApexSEO\Tests\SeoSubsystemTest::class,
    ApexSEO\Tests\AnalysisSubsystemTest::class,
    ApexSEO\Tests\SchemaSubsystemTest::class,
    ApexSEO\Tests\PerformanceSubsystemTest::class,
    ApexSEO\Tests\MediaSubsystemTest::class,
    ApexSEO\Tests\MediaFailureContractTest::class,
    ApexSEO\Tests\AiSubsystemTest::class,
    ApexSEO\Tests\AnalyticsSubsystemTest::class,
    ApexSEO\Tests\RestSubsystemTest::class,
    ApexSEO\Tests\CliSubsystemTest::class,
];

echo "====================================================\n";
echo "  Apex SEO Architecture - Test Suite Runner\n";
echo "====================================================\n\n";

$totalPassed = 0;
$totalFailed = 0;
$allErrors = [];

foreach ($testClasses as $testClass) {
    if (!class_exists($testClass)) {
        echo "⚠️ Skipping class $testClass (not found)\n";
        continue;
    }

    $test = new $testClass();
    $res = $test->run();

    $totalPassed += $res['passed'];
    $totalFailed += $res['failed'];

    $shortName = basename(str_replace('\\', '/', $testClass));
    if ($res['failed'] === 0) {
        echo " [PASS] {$shortName} ({$res['passed']} tests)\n";
    } else {
        echo "❌ [FAIL] {$shortName} ({$res['passed']} passed, {$res['failed']} failed)\n";
        foreach ($res['errors'] as $err) {
            $allErrors[] = "[{$shortName}::{$err['method']}] " . $err['message'];
        }
    }
}

echo "\n----------------------------------------------------\n";
echo sprintf("Summary: %d Passed, %d Failed (Assertions: %d)\n", $totalPassed, $totalFailed, ApexSEO\Tests\TestCase::$assertionCount);
echo "----------------------------------------------------\n";

if ($totalFailed > 0) {
    echo "\nFailures:\n";
    foreach ($allErrors as $err) {
        echo " - $err\n";
    }
    exit(1);
} else {
    echo "🎉 ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
}
