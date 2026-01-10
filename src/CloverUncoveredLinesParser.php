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
            throw new \RuntimeException('File not found: '.$cloverPath);
        }

        $content = @file_get_contents($cloverPath);
        if (false === $content) {
            throw new \RuntimeException('Failed to read file: '.$cloverPath);
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
        $projectRoot = '' === $this->projectRoot
            ? $this->detectProjectRoot($xml)
            : $this->projectRoot;

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
                // Line not covered
                if (0 === (int) $line['count']) {
                    $uncoveredLines[] = [
                        'num' => (int) $line['num'],
                        'type' => (string) $line['type'],
                    ];
                }
            }

            if ([] !== $uncoveredLines) {
                $uncoveredByFile[$filename] = $uncoveredLines;
            }
        }

        return $uncoveredByFile;
    }

    private function detectProjectRoot(\SimpleXMLElement $xml): string
    {
        $files = $xml->xpath('//file');
        if (!\is_array($files) || [] === $files) {
            return \dirname(__DIR__);
        }

        $allPaths = array_map(
            fn (\SimpleXMLElement $file): string => (string) $file['name'],
            $files
        );

        $commonPrefix = $allPaths[0];
        foreach ($allPaths as $allPath) {
            $len = min(\strlen($commonPrefix), \strlen($allPath));
            for ($i = 0; $i < $len; ++$i) {
                if ($commonPrefix[$i] !== $allPath[$i]) {
                    $commonPrefix = substr($commonPrefix, 0, $i);
                    break;
                }
            }

            $commonPrefix = substr($commonPrefix, 0, $len);
        }

        $lastSlash = strrpos($commonPrefix, '/');
        if (false !== $lastSlash) {
            $commonPrefix = substr($commonPrefix, 0, $lastSlash);
        }

        return '' !== $commonPrefix ? $commonPrefix : '/';
    }
}
