<?php
declare(strict_types=1);

namespace Tests;

/**
 * Minimal xUnit-style base class (no Composer/PHPUnit needed). Methods starting with "test" are run.
 */
abstract class TestCase
{
    public int $assertions = 0;
    public array $failures = [];

    protected function assertSame($expected, $actual, string $msg = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new AssertionFailed(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    protected function assertTrue(bool $cond, string $msg = 'expected true'): void { $this->assertSame(true, $cond, $msg); }
    protected function assertFalse(bool $cond, string $msg = 'expected false'): void { $this->assertSame(false, $cond, $msg); }

    protected function assertEqualsFloat(float $expected, float $actual, string $msg = '', float $delta = 0.001): void
    {
        $this->assertions++;
        if (abs($expected - $actual) > $delta) {
            throw new AssertionFailed(($msg ? "$msg: " : '') . "expected $expected, got $actual");
        }
    }

    public function run(): array
    {
        $results = [];
        foreach (get_class_methods($this) as $m) {
            if (strpos($m, 'test') !== 0) {
                continue;
            }
            try {
                $this->$m();
                $results[$m] = null;
            } catch (\Throwable $e) {
                $results[$m] = $e->getMessage();
            }
        }
        return $results;
    }
}

final class AssertionFailed extends \Exception {}
