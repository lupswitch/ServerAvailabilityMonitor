<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ManageCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('manage')

		// the short description shown while running "php bin/console list"
		->setDescription('Manages the list of servers.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to add/edit and delete servers for monitoring.')

		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$servers_list = new ServersList($config_file);

		$table = new Table($output);
		$table
			->setHeaders(['Name', 'Type', 'Ip', 'Port']);
		$rows = [];
		foreach ($servers_list->getServerNames() as $server_name) {
			$server_config = $servers_list[$server_name];
			$rows[] = [$server_name, $server_config['type'], $server_config['ip'], $server_config['port']];
		}
		$table->setRows($rows);
		$table->render();
	}
}
