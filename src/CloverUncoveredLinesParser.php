<?php

declare(strict_types=1);

namespace Devnix\CloverUncoveredLines;

final class CloverUncoveredLinesParser
{
    public function __construct(
        private readonly string $projectRoot = '',
    ) {
    }

    /**
     * @return array<string, list<array{num: int, type: string}>>
     */
    public function parse(string $cloverPath): array
    {
        if (!file_exists($cloverPath)) {
            throw new \RuntimeException("File not found: {$cloverPath}");
        }

        $content = @file_get_contents($cloverPath);
        if (false === $content) {
            throw new \RuntimeException("Failed to read file: {$cloverPath}");
        }

        $xml = @simplexml_load_string($content);
        if (false === $xml) {
            throw new \RuntimeException('Failed to parse XML file');
        }

        return $this->extractUncoveredLines($xml);
    }

    /**
     * @return array<string, list<array{num: int, type: string}>>
     */
    private function extractUncoveredLines(\SimpleXMLElement $xml): array
    {
        $uncoveredByFile = [];
        $projectRoot = $this->projectRoot;

        // Auto-detect project root from XML if not provided
        if ('' === $projectRoot) {
            $projectRoot = $this->detectProjectRoot($xml);
        }

        $files = $xml->xpath('//file');
        // @codeCoverageIgnoreStart
        if (!\is_array($files)) {
            return [];
        }
        // @codeCoverageIgnoreEnd

        foreach ($files as $file) {
            $filename = (string) $file['name'];

            // Make path relative to project root
            if (str_starts_with($filename, $projectRoot)) {
                $filename = substr($filename, \strlen($projectRoot) + 1);
            }

            $uncoveredLines = [];

            foreach ($file->line as $line) {
                $lineNum = (int) $line['num'];
                $count = (int) $line['count'];

                // Line not covered
                if (0 === $count) {
                    $type = (string) $line['type'];
                    $uncoveredLines[] = [
                        'num' => $lineNum,
                        'type' => $type,
                    ];
                }
            }

            if (\count($uncoveredLines) > 0) {
                $uncoveredByFile[$filename] = $uncoveredLines;
            }
        }

        return $uncoveredByFile;
    }

    private function detectProjectRoot(\SimpleXMLElement $xml): string
    {
        $files = $xml->xpath('//file');
        if (!\is_array($files) || 0 === \count($files)) {
            return \dirname(__DIR__);
        }

        // Collect all unique directory parts to find common ancestor
        $allPaths = [];
        foreach ($files as $file) {
            $allPaths[] = (string) $file['name'];
        }

        // Find common prefix of all paths
        $commonPrefix = $allPaths[0];
        foreach ($allPaths as $path) {
            $len = min(\strlen($commonPrefix), \strlen($path));
            for ($i = 0; $i < $len; ++$i) {
                if ($commonPrefix[$i] !== $path[$i]) {
                    $commonPrefix = substr($commonPrefix, 0, $i);
                    break;
                }
            }
            $commonPrefix = substr($commonPrefix, 0, $len);
        }

        // Ensure we end at a directory boundary
        $lastSlash = strrpos($commonPrefix, '/');
        if (false !== $lastSlash) {
            $commonPrefix = substr($commonPrefix, 0, $lastSlash);
        }

        return '' !== $commonPrefix ? $commonPrefix : '/';
    }
}
