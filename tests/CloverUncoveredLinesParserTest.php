<?php

declare(strict_types=1);

namespace Devnix\CloverUncoveredLines\Tests;

use Devnix\CloverUncoveredLines\CloverUncoveredLinesParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CloverUncoveredLinesParserTest extends TestCase
{
    private CloverUncoveredLinesParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CloverUncoveredLinesParser();
    }

    #[Test]
    public function throwsExceptionWhenFileNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->parser->parse('/non/existent/file.xml');
    }

    #[Test]
    public function throwsExceptionWhenFileCannotBeRead(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, 'content');
        chmod($tempFile, 0000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read file');

        try {
            $this->parser->parse($tempFile);
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    }

    #[Test]
    public function throwsExceptionWhenXmlIsInvalid(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, 'invalid xml content');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse XML file');

        try {
            $this->parser->parse($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function returnsEmptyArrayWhenAllLinesAreCovered(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0"?>
<coverage>
    <project>
        <file name="/project/src/Example.php">
            <line num="10" type="stmt" count="1"/>
            <line num="11" type="stmt" count="5"/>
            <line num="12" type="method" count="1"/>
        </file>
    </project>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, $xmlContent);

        try {
            $result = $this->parser->parse($tempFile);
            self::assertEmpty($result);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function parsesUncoveredLinesSuccessfully(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0"?>
<coverage>
    <project>
        <file name="/project/src/Example.php">
            <line num="10" type="stmt" count="1"/>
            <line num="11" type="stmt" count="0"/>
            <line num="12" type="method" count="0"/>
            <line num="13" type="stmt" count="1"/>
        </file>
    </project>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, $xmlContent);

        $parser = new CloverUncoveredLinesParser('/project');

        try {
            $result = $parser->parse($tempFile);

            self::assertArrayHasKey('src/Example.php', $result);
            self::assertCount(2, $result['src/Example.php']);
            self::assertSame(11, $result['src/Example.php'][0]['num']);
            self::assertSame('stmt', $result['src/Example.php'][0]['type']);
            self::assertSame(12, $result['src/Example.php'][1]['num']);
            self::assertSame('method', $result['src/Example.php'][1]['type']);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function parsesMultipleFilesWithUncoveredLines(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0"?>
<coverage>
    <project>
        <file name="/project/src/Example.php">
            <line num="10" type="stmt" count="0"/>
        </file>
        <file name="/project/src/Another.php">
            <line num="5" type="stmt" count="0"/>
            <line num="6" type="stmt" count="0"/>
        </file>
        <file name="/project/src/Covered.php">
            <line num="1" type="stmt" count="1"/>
        </file>
    </project>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, $xmlContent);

        $parser = new CloverUncoveredLinesParser('/project');

        try {
            $result = $parser->parse($tempFile);

            self::assertCount(2, $result);
            self::assertArrayHasKey('src/Example.php', $result);
            self::assertArrayHasKey('src/Another.php', $result);
            self::assertArrayNotHasKey('src/Covered.php', $result);
            self::assertCount(1, $result['src/Example.php']);
            self::assertCount(2, $result['src/Another.php']);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function usesCustomProjectRoot(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0"?>
<coverage>
    <project>
        <file name="/custom/path/src/Example.php">
            <line num="10" type="stmt" count="0"/>
        </file>
    </project>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, $xmlContent);

        $parser = new CloverUncoveredLinesParser('/custom/path');

        try {
            $result = $parser->parse($tempFile);

            self::assertArrayHasKey('src/Example.php', $result);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function returnsEmptyArrayWhenNoFilesInCoverage(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0"?>
<coverage>
    <project>
    </project>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, $xmlContent);

        try {
            $result = $this->parser->parse($tempFile);
            self::assertEmpty($result);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function autoDetectsProjectRootFromMultipleFiles(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0"?>
<coverage>
    <project>
        <file name="/var/www/project/src/Foo.php">
            <line num="10" type="stmt" count="0"/>
        </file>
        <file name="/var/www/project/tests/Bar.php">
            <line num="5" type="stmt" count="0"/>
        </file>
    </project>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, $xmlContent);

        try {
            $result = $this->parser->parse($tempFile);

            // Should strip /var/www/project prefix
            self::assertArrayHasKey('src/Foo.php', $result);
            self::assertArrayHasKey('tests/Bar.php', $result);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function handlesPathsWithDifferentPrefixes(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0"?>
<coverage>
    <project>
        <file name="/abc/def/file1.php">
            <line num="10" type="stmt" count="0"/>
        </file>
        <file name="/abc/xyz/file2.php">
            <line num="5" type="stmt" count="0"/>
        </file>
    </project>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'clover');
        file_put_contents($tempFile, $xmlContent);

        try {
            $result = $this->parser->parse($tempFile);

            // Should find common prefix /abc
            self::assertCount(2, $result);
        } finally {
            unlink($tempFile);
        }
    }
}
