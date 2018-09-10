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
		->setName('manage')
		->setDescription('Manages the list of servers.')
		->setHelp('This command allows you to add/edit and delete servers for monitoring.')
		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())
	;
	}

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$servers_list = new ServersList($config_file);

		$table = new Table($output);
		$table
			->setHeaders(['Name', 'Type', 'Host', 'Port']);
		$rows = [];
		foreach ($servers_list->getServerNames() as $server_name) {
			$server_config = $servers_list[$server_name];
			$rows[] = [$server_name, $server_config['type'], $server_config['hostname'], $server_config['port']];
		}
		$table->setRows($rows);
		$table->render();
	}
}
