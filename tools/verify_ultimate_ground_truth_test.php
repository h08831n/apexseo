<?php
/**
 * APEX SEO — ULTIMATE ZERO-TRUST VERIFIER TEST SUITE
 *
 * Validates that the verifier itself operates on pure ground truth:
 * - Rejects any docs/* reading
 * - Dynamically derives evidence from AST, reflection, and live execution
 * - Passes all 15 negative mutation tests
 * - Executes in-memory capability downgrade self-test
 */

namespace ApexSEO\Tools\Tests;

require_once __DIR__ . '/verify_ultimate_ground_truth.php';

use ApexSEO\Tools\UltimateGroundTruthVerifier;
use Exception;

class UltimateVerifierTest {
    private $verifier;
    private $passCount = 0;
    private $failCount = 0;

    public function __construct() {
        $this->verifier = new UltimateGroundTruthVerifier(['--full']);
    }

    public function run(): int {
        echo "====================================================\n";
        echo "  APEX SEO — VERIFIER SELF-TEST & VALIDATION SUITE  \n";
        echo "====================================================\n";

        $this->testZeroTrustRuntimeGuard();
        $this->testCanonicalCatalogSpecification();
        $this->testNegativeVerificationSuite();
        $this->testFullVerifierExecution();

        echo "----------------------------------------------------\n";
        echo "Verifier Tests Passed: {$this->passCount}\n";
        echo "Verifier Tests Failed: {$this->failCount}\n";
        echo "----------------------------------------------------\n";

        return $this->failCount === 0 ? 0 : 1;
    }

    private function assert($condition, $message) {
        if ($condition) {
            echo "  [PASS] {$message}\n";
            $this->passCount++;
        } else {
            echo "  [FAIL] {$message}\n";
            $this->failCount++;
        }
    }

    private function testZeroTrustRuntimeGuard() {
        echo "\n[Test 1/4] Testing Zero-Trust Runtime File Read Guard...\n";
        $blocked = false;
        try {
            $this->verifier->readFile(__DIR__ . '/../docs/FINAL-PHYSICAL-IMPLEMENTATION-MATRIX.json');
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'ZERO-TRUST VIOLATION')) {
                $blocked = true;
            }
        }
        $this->assert($blocked, "Runtime guard strictly blocks reading any file under docs/");
    }

    private function testCanonicalCatalogSpecification() {
        echo "\n[Test 2/4] Testing Canonical 198 Catalog Invariants...\n";
        $catalogPath = __DIR__ . '/canonical_198_catalog.json';
        $json = file_get_contents($catalogPath);
        $catalog = json_decode($json, true);

        $this->assert(is_array($catalog) && count($catalog) === 198, "Canonical catalog contains exactly 198 capability records");

        $forbiddenFound = false;
        foreach ($catalog as $id => $item) {
            if (isset($item['status']) || isset($item['implemented']) || isset($item['evidence']) || isset($item['test_result'])) {
                $forbiddenFound = true;
                break;
            }
        }
        $this->assert(!$forbiddenFound, "Canonical catalog is completely free of pre-determined status or evidence fields");
    }

    private function testNegativeVerificationSuite() {
        echo "\n[Test 3/4] Testing 15-Vector Negative Verification Suite & Downgrade Self-Test...\n";
        $negativePassed = $this->verifier->runNegativeVerificationSuite();
        $this->assert($negativePassed, "All 15 negative mutations and in-memory downgrade self-tests passed");
    }

    private function testFullVerifierExecution() {
        echo "\n[Test 4/4] Testing Verifier Execution & Non-Zero Exit Guard...\n";
        ob_start();
        $exitCode = $this->verifier->run();
        $output = ob_get_clean();

        $this->assert($exitCode === 0, "Verifier executed successfully with exit code 0");
        $this->assert(str_contains($output, "FINAL VERDICT: PASS"), "Verifier produced FINAL VERDICT: PASS");
        $this->assert(str_contains($output, "PHYSICAL PRODUCTION FILES: 120"), "Verifier discovered 120 physical production files");
        $this->assert(str_contains($output, "IMPLEMENTED: 75"), "Verifier derived 75 IMPLEMENTED capabilities");
        $this->assert(str_contains($output, "SPEC_ONLY: 123"), "Verifier derived 123 SPEC_ONLY capabilities");
        $this->assert(str_contains($output, "PARTIAL: 0"), "Verifier derived 0 PARTIAL capabilities");
        $this->assert(str_contains($output, "BROKEN: 0"), "Verifier derived 0 BROKEN capabilities");
    }
}

$testRunner = new UltimateVerifierTest();
exit($testRunner->run());
