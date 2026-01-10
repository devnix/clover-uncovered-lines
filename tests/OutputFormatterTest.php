<?php

declare(strict_types=1);

namespace Devnix\CloverUncoveredLines\Tests;

use Devnix\CloverUncoveredLines\OutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OutputFormatter::class)]
final class OutputFormatterTest extends TestCase
{
    private OutputFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new OutputFormatter();
    }

    #[Test]
    public function formatsEmptyResultAsAllCovered(): void
    {
        $result = $this->formatter->format([]);

        self::assertSame("✓ All lines are covered!\n", $result);
    }

    #[Test]
    public function formatsSingleFileWithSingleLine(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'stmt'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('src/Example.php', $result);
        self::assertStringContainsString('Line 10', $result);
        self::assertStringContainsString('Summary: 1 uncovered lines in 1 file(s)', $result);
    }

    #[Test]
    public function formatsSingleFileWithMultipleNonConsecutiveLines(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'stmt'],
                ['num' => 15, 'type' => 'stmt'],
                ['num' => 20, 'type' => 'stmt'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('Line 10', $result);
        self::assertStringContainsString('Line 15', $result);
        self::assertStringContainsString('Line 20', $result);
        self::assertStringContainsString('Summary: 3 uncovered lines in 1 file(s)', $result);
    }

    #[Test]
    public function groupsConsecutiveLinesIntoRanges(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'stmt'],
                ['num' => 11, 'type' => 'stmt'],
                ['num' => 12, 'type' => 'stmt'],
                ['num' => 15, 'type' => 'stmt'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('Lines 10-12', $result);
        self::assertStringContainsString('Line 15', $result);
        self::assertStringNotContainsString('Line 10', $result);
        self::assertStringNotContainsString('Line 11', $result);
    }

    #[Test]
    public function doesNotGroupLinesWithDifferentTypes(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'stmt'],
                ['num' => 11, 'type' => 'method'],
                ['num' => 12, 'type' => 'stmt'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('Line 10', $result);
        self::assertStringContainsString('Line 11 (method)', $result);
        self::assertStringContainsString('Line 12', $result);
        self::assertStringNotContainsString('Lines 10-12', $result);
    }

    #[Test]
    public function addsMethodLabelForMethodType(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'method'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('Line 10 (method)', $result);
    }

    #[Test]
    public function addsMethodLabelForMethodRanges(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'method'],
                ['num' => 11, 'type' => 'method'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('Lines 10-11 (method)', $result);
    }

    #[Test]
    public function formatsMultipleFiles(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'stmt'],
            ],
            'src/Another.php' => [
                ['num' => 5, 'type' => 'stmt'],
                ['num' => 6, 'type' => 'stmt'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('src/Example.php', $result);
        self::assertStringContainsString('src/Another.php', $result);
        self::assertStringContainsString('Summary: 3 uncovered lines in 2 file(s)', $result);
    }

    #[Test]
    public function formatsComplexScenarioWithMixedRanges(): void
    {
        $uncoveredByFile = [
            'src/Example.php' => [
                ['num' => 10, 'type' => 'stmt'],
                ['num' => 11, 'type' => 'stmt'],
                ['num' => 12, 'type' => 'stmt'],
                ['num' => 15, 'type' => 'method'],
                ['num' => 16, 'type' => 'method'],
                ['num' => 20, 'type' => 'stmt'],
            ],
        ];

        $result = $this->formatter->format($uncoveredByFile);

        self::assertStringContainsString('Lines 10-12', $result);
        self::assertStringContainsString('Lines 15-16 (method)', $result);
        self::assertStringContainsString('Line 20', $result);
        self::assertStringContainsString('Summary: 6 uncovered lines in 1 file(s)', $result);
    }
}
