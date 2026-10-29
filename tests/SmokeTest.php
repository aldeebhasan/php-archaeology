<?php

declare(strict_types=1);

namespace CodeArchaeology\Tests;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testRuntimeRequirementsAreAvailable(): void
    {
        self::assertTrue(extension_loaded('pdo_sqlite'));
        self::assertTrue(class_exists(\PhpParser\ParserFactory::class));
        self::assertTrue(class_exists(\Symfony\Component\Console\Application::class));
        self::assertTrue(class_exists(\Symfony\Component\Process\Process::class));
    }
}
