<?php

declare(strict_types=1);

namespace CodeArchaeology\Project;

use Symfony\Component\Process\Process;

final class ProjectLocator
{
    /** @throws ProjectNotFound */
    public function locate(string $cwd): Project
    {
        $dir = realpath($cwd);

        while ($dir !== false && !is_file($dir . '/composer.json')) {
            $parent = dirname($dir);
            $dir = $parent === $dir ? false : $parent;
        }

        if ($dir === false) {
            throw ProjectNotFound::noComposerJson($cwd);
        }

        // The git root may sit above composer.json (monorepo); any enclosing repo is fine.
        if ((new Process(['git', 'rev-parse', '--show-toplevel'], $dir))->run() !== 0) {
            throw ProjectNotFound::noGit();
        }

        return new Project($dir);
    }
}
