<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MonitorCommand extends Command {

	protected function configure() {
        $this
        // the name of the command (the part after "bin/console")
        ->setName('monitor')

        // the short description shown while running "php bin/console list"
        ->setDescription('Monitors all servers.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command allows you to monitor all configured servers.')
    ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        // ...
    }
}
