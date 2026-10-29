<?php

declare(strict_types=1);

namespace CodeArchaeology\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class BinTest extends TestCase
{
    public function testListShowsRegisteredCommands(): void
    {
        $process = new Process([PHP_BINARY, __DIR__ . '/../bin/archaeo', 'list', '--raw']);
        $process->mustRun();

        self::assertMatchesRegularExpression('/^init\s/m', $process->getOutput());
    }
}
