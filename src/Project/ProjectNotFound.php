<?php

declare(strict_types=1);

namespace CodeArchaeology\Project;

final class ProjectNotFound extends \RuntimeException
{
    public static function noComposerJson(string $cwd): self
    {
        return new self("No composer.json found in {$cwd} or any parent folder. Run Code Archaeology inside a PHP project.");
    }

    public static function noGit(): self
    {
        return new self('No git history found. Code Archaeology needs a git repository to dig.');
    }
}
