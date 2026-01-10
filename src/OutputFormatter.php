<?php

declare(strict_types=1);

namespace Devnix\CloverUncoveredLines;

final class OutputFormatter
{
    /**
     * @param array<string, list<array{num: int, type: string}>> $uncoveredByFile
     */
    public function format(array $uncoveredByFile): string
    {
        if ([] === $uncoveredByFile) {
            return "✓ All lines are covered!\n";
        }

        $output = "Uncovered lines:\n\n";

        foreach ($uncoveredByFile as $filename => $lines) {
            $output .= $filename.\PHP_EOL;
            $output .= $this->formatLinesForFile($lines);
            $output .= "\n";
        }

        $totalFiles = \count($uncoveredByFile);
        $totalLines = array_sum(array_map('count', $uncoveredByFile));

        return $output."Summary: {$totalLines} uncovered lines in {$totalFiles} file(s)\n";
    }

    /**
     * @param list<array{num: int, type: string}> $lines
     */
    private function formatLinesForFile(array $lines): string
    {
        $output = '';
        $ranges = $this->groupConsecutiveLines($lines);

        foreach ($ranges as $range) {
            $lineRef = $range['start'] === $range['end']
                ? '  Line '.$range['start']
                : \sprintf('  Lines %d-%d', $range['start'], $range['end']);

            $typeLabel = 'method' === $range['type'] ? ' (method)' : '';
            $output .= $lineRef.$typeLabel.\PHP_EOL;
        }

        return $output;
    }

    /**
     * @param list<array{num: int, type: string}> $lines
     *
     * @return list<array{start: int, end: int, type: string}>
     */
    private function groupConsecutiveLines(array $lines): array
    {
        $ranges = [];
        $currentRange = null;

        foreach ($lines as $line) {
            if (null === $currentRange) {
                $currentRange = ['start' => $line['num'], 'end' => $line['num'], 'type' => $line['type']];
            } elseif ($currentRange['end'] + 1 === $line['num'] && $line['type'] === $currentRange['type']) {
                $currentRange['end'] = $line['num'];
            } else {
                $ranges[] = $currentRange;
                $currentRange = ['start' => $line['num'], 'end' => $line['num'], 'type' => $line['type']];
            }
        }

        if (null !== $currentRange) {
            $ranges[] = $currentRange;
        }

        return $ranges;
    }
}
