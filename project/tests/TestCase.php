<?php
declare(strict_types=1);

namespace App\Tests;

use AssertionError;

class TestCase
{
    private int $assertions = 0;
    private int $failures = 0;
    private array $tests = [];

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;

        if ($expected !== $actual) {
            $msg = $message ? " ({$message})" : '';
            throw new AssertionError(
                "Expected {$this->format($expected)}, got {$this->format($actual)}{$msg}"
            );
        }
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        $this->assertions++;

        if (!$condition) {
            $msg = $message ? " ({$message})" : '';
            throw new AssertionError("Expected true{$msg}");
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        $this->assertions++;

        if ($condition) {
            $msg = $message ? " ({$message})" : '';
            throw new AssertionError("Expected false{$msg}");
        }
    }

    protected function assertFloatEquals(float $expected, float $actual, float $delta = 0.01, string $message = ''): void
    {
        $this->assertions++;

        if (abs($expected - $actual) > $delta) {
            $msg = $message ? " ({$message})" : '';
            throw new AssertionError(
                "Expected {$expected}, got {$actual} (within ±{$delta}){$msg}"
            );
        }
    }

    public function run(): int
    {
        $refClass = new \ReflectionClass($this);
        $methods = $refClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (strpos($method->getName(), 'test') === 0) {
                $testName = $method->getName();

                try {
                    $method->invoke($this);
                    $this->tests[$testName] = '✓ PASS';
                } catch (AssertionError $e) {
                    $this->tests[$testName] = '✗ FAIL: ' . $e->getMessage();
                    $this->failures++;
                } catch (\Throwable $e) {
                    $this->tests[$testName] = '✗ ERROR: ' . $e->getMessage();
                    $this->failures++;
                }
            }
        }

        return $this->failures;
    }

    public function report(): void
    {
        $total = count($this->tests);
        $passed = $total - $this->failures;

        echo "\n" . str_repeat('=', 70) . "\n";
        echo "Test Results for " . get_class($this) . "\n";
        echo str_repeat('=', 70) . "\n\n";

        foreach ($this->tests as $testName => $result) {
            echo "  {$result}\n";
            echo "    {$testName}\n\n";
        }

        echo str_repeat('-', 70) . "\n";
        echo "Assertions: {$this->assertions} | Passed: {$passed} | Failed: {$this->failures}\n";
        echo str_repeat('=', 70) . "\n";
    }

    private function format(mixed $value): string
    {
        if (is_float($value)) {
            return sprintf('%.2f', $value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_array($value)) {
            return 'array[]';
        }

        return (string) $value;
    }
}
