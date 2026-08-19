<?php

namespace Core\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Start the PHP server on 127.0.0.1:8888')
            ->setHelp('This command starts the PHP built-in server on 127.0.0.1:8888');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = '127.0.0.1';
        $port = 8888;

        $output->writeln("Starting PHP server on http://$host:$port");
        $output->writeln("Press Ctrl+C to stop the server");

        chdir(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public');
        passthru("php -S $host:$port");

        return Command::SUCCESS;
    }
}