<?php

declare(strict_types=1);

namespace CodeArchaeology\Command;

use CodeArchaeology\Project\ProjectLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'init', description: 'Create archaeology.php and the .archaeology/ folder in this project')]
final class InitCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // shortcut: only prints the root until 1.6 (real init) lands
        $output->writeln('Dig site: ' . (new ProjectLocator())->locate((string) getcwd())->root);

        return Command::SUCCESS;
    }
}
