<?php

declare(strict_types=1);

namespace CodeArchaeology\Project;

use Symfony\Component\Process\Process;

/** The dig site: the project the package is installed in. */
final readonly class Project
{
    public string $dataDir;
    public string $dbPath;
    public string $configPath;

    public function __construct(public string $root)
    {
        $this->dataDir = $root . '/.archaeology';
        $this->dbPath = $this->dataDir . '/dig.sqlite';
        $this->configPath = $root . '/archaeology.php';
    }

    /** CI clones with --depth=1 have no history to dig. */
    public function isShallow(): bool
    {
        return trim($this->git('rev-parse', '--is-shallow-repository')) === 'true';
    }

    /** A dig analyzes HEAD (D5), so uncommitted changes are not part of it. */
    public function isDirty(): bool
    {
        return trim($this->git('status', '--porcelain')) !== '';
    }

    private function git(string ...$args): string
    {
        return (new Process(['git', ...$args], $this->root))->mustRun()->getOutput();
    }
}
