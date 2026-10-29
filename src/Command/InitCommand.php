<?php

declare(strict_types=1);

namespace CodeArchaeology\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'init', description: 'Create archaeology.php and the .archaeology/ folder in this project')]
final class InitCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // shortcut: placeholder until 1.3 (ProjectLocator) and 1.6 (real init) land
        $output->writeln('Dig site: ' . getcwd());

        return Command::SUCCESS;
    }
}
