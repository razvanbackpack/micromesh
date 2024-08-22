<?php

namespace Core\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends Command
{
    protected static $defaultName = 'init';
    protected static $defaultDescription = 'Initialize the .env file by copying env.example';

    protected function configure(): void
    {
        $this
            ->setHelp('This command copies the env.example file to .env if it doesn\'t already exist.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourceFile = 'env.example';
        $destinationFile = '.env';

        if (!file_exists($sourceFile)) {
            $io->error("The file {$sourceFile} does not exist.");
            return Command::FAILURE;
        }

        if (file_exists($destinationFile)) {
            $io->warning("The file {$destinationFile} already exists. Skipping copy operation.");
            return Command::SUCCESS;
        }

        try {
            if (copy($sourceFile, $destinationFile)) {
                $io->success("Successfully copied {$sourceFile} to {$destinationFile}.");
                return Command::SUCCESS;
            } else {
                $io->error("Failed to copy {$sourceFile} to {$destinationFile}.");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}