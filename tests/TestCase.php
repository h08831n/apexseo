<?php
namespace ApexSEO\Tests;

use Exception;

if (class_exists('\PHPUnit\Framework\TestCase')) {
    abstract class TestCase extends \PHPUnit\Framework\TestCase {
        public function assertStringContains($needle, $haystack, string $message = ''): void {
            $this->assertStringContainsString((string)$needle, (string)$haystack, $message);
        }

        public function assertStringNotContains($needle, $haystack, string $message = ''): void {
            $this->assertStringNotContainsString((string)$needle, (string)$haystack, $message);
        }
    }
} else {
    /**
     * Lightweight Standalone Test Framework Base Class.
     */
    abstract class TestCase {
        /**
         * Number of assertions in the test suite.
         *
         * @var int
         */
        public static $assertionCount = 0;

        /**
         * Expected exception class name.
         *
         * @var string|null
         */
        protected $expectedException = null;

        /**
         * Setup before each test method.
         */
        public function setUp(): void {}

        /**
         * Teardown after each test method.
         */
        public function tearDown(): void {}

        /**
         * Run all test methods in the test class.
         *
         * @return array
         */
        public function run() {
            $results = [
                'class'  => get_class($this),
                'passed' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            $methods = get_class_methods($this);
            foreach ($methods as $method) {
                if (strpos($method, 'test') === 0) {
                    $this->setUp();
                    $this->expectedException = null;

                    try {
                        $this->$method();

                        if ($this->expectedException !== null) {
                            throw new Exception(sprintf('Expected exception [%s] was not thrown.', $this->expectedException));
                        }

                        $results['passed']++;
                    } catch (Exception $e) {
                        if ($this->expectedException !== null && is_a($e, $this->expectedException)) {
                            $results['passed']++;
                        } else {
                            $results['failed']++;
                            $results['errors'][] = sprintf('[%s::%s] %s (Line: %d)', get_class($this), $method, $e->getMessage(), $e->getLine());
                        }
                    } finally {
                        $this->tearDown();
                    }
                }
            }

            return $results;
        }

        /**
         * Assert that a condition is true.
         */
        public function assertTrue($condition, $message = 'Failed asserting that condition is true.') {
            self::$assertionCount++;
            if (!$condition) {
                throw new Exception($message);
            }
        }

        /**
         * Assert that a condition is false.
         */
        public function assertFalse($condition, $message = 'Failed asserting that condition is false.') {
            self::$assertionCount++;
            if ($condition) {
                throw new Exception($message);
            }
        }

        /**
         * Assert equality.
         */
        public function assertEquals($expected, $actual, $message = '') {
            self::$assertionCount++;
            if ($expected != $actual) {
                $msg = !empty($message) ? $message : sprintf('Failed asserting that [%s] equals expected [%s].', var_export($actual, true), var_export($expected, true));
                throw new Exception($msg);
            }
        }

        /**
         * Assert identity.
         */
        public function assertSame($expected, $actual, $message = '') {
            self::$assertionCount++;
            if ($expected !== $actual) {
                $msg = !empty($message) ? $message : sprintf('Failed asserting that [%s] is identical to expected [%s].', var_export($actual, true), var_export($expected, true));
                throw new Exception($msg);
            }
        }

        /**
         * Assert null.
         */
        public function assertNull($actual, $message = 'Failed asserting that value is null.') {
            self::$assertionCount++;
            if ($actual !== null) {
                throw new Exception($message);
            }
        }

        /**
         * Assert not null.
         */
        public function assertNotNull($actual, $message = 'Failed asserting that value is not null.') {
            self::$assertionCount++;
            if ($actual === null) {
                throw new Exception($message);
            }
        }

        /**
         * Assert instance of.
         */
        public function assertInstanceOf($expectedClass, $actual, $message = '') {
            self::$assertionCount++;
            if (!($actual instanceof $expectedClass)) {
                $actualClass = is_object($actual) ? get_class($actual) : gettype($actual);
                $msg = !empty($message) ? $message : sprintf('Failed asserting that instance of [%s] matches expected class [%s].', $actualClass, $expectedClass);
                throw new Exception($msg);
            }
        }

        /**
         * Assert array count.
         */
        public function assertCount($expectedCount, $array, $message = '') {
            self::$assertionCount++;
            $actualCount = count($array);
            if ($actualCount !== $expectedCount) {
                $msg = !empty($message) ? $message : sprintf('Failed asserting array count [%d] equals expected [%d].', $actualCount, $expectedCount);
                throw new Exception($msg);
            }
        }

        /**
         * Assert array has key.
         */
        public function assertArrayHasKey($key, $array, $message = '') {
            self::$assertionCount++;
            if (!is_array($array) || !array_key_exists($key, $array)) {
                $msg = !empty($message) ? $message : sprintf('Failed asserting that array has key [%s].', (string)$key);
                throw new Exception($msg);
            }
        }

        /**
         * Assert array does not have key.
         */
        public function assertArrayNotHasKey($key, $array, $message = '') {
            self::$assertionCount++;
            if (is_array($array) && array_key_exists($key, $array)) {
                $msg = !empty($message) ? $message : sprintf('Failed asserting that array does not have key [%s].', (string)$key);
                throw new Exception($msg);
            }
        }

        /**
         * Assert string contains.
         */
        public function assertStringContains($needle, $haystack, $message = '') {
            self::$assertionCount++;
            if (strpos((string)$haystack, (string)$needle) === false) {
                $msg = !empty($message) ? $message : sprintf('Failed asserting that [%s] contains [%s].', (string)$haystack, (string)$needle);
                throw new Exception($msg);
            }
        }

        /**
         * Assert string contains string.
         */
        public function assertStringContainsString($needle, $haystack, $message = '') {
            $this->assertStringContains($needle, $haystack, $message);
        }

        /**
         * Assert string does not contain.
         */
        public function assertStringNotContains($needle, $haystack, $message = '') {
            self::$assertionCount++;
            if (strpos((string)$haystack, (string)$needle) !== false) {
                $msg = !empty($message) ? $message : sprintf('Failed asserting that [%s] does not contain [%s].', (string)$haystack, (string)$needle);
                throw new Exception($msg);
            }
        }

        /**
         * Assert string does not contain string.
         */
        public function assertStringNotContainsString($needle, $haystack, $message = '') {
            $this->assertStringNotContains($needle, $haystack, $message);
        }

        /**
         * Expect an exception class.
         */
        public function expectException($exceptionClass) {
            $this->expectedException = $exceptionClass;
        }
    }
}
