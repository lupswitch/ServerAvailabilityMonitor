<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class RenameCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('manage:rename')

		// the short description shown while running "php bin/console list"
		->setDescription('Rename server.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to rename server for monitoring.')

		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())

		->addArgument('name', InputArgument::OPTIONAL, 'Name of the server')
		->addArgument('new_name', InputArgument::OPTIONAL, 'New name of the server')
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$servers_list = new ServersList($config_file);

		$helper = $this->getHelper('question');

		$name = $input->getArgument('name');
		$new_name = $input->getArgument('new_name');

		if (empty($name) || !isset($servers_list[$name])) {
			$question = new Question('Please provide name of server to rename: ');
			while (true) {
				$name = $helper->ask($input, $output, $question);
				if (!empty($name) && isset($servers_list[$name]))
					break;
			}
		}

		if (empty($new_name)) {
			$question = new Question('Please select new name of server: ');
			$question->setValidator(function ($new_name) use ($servers_list, $name) {
				$new_name = trim($new_name);
				if (empty($new_name))
					throw new \RuntimeException('Server name can not be empty');
				if ($new_name == $name)
					return $name;
				if (isset($servers_list[$new_name]))
					throw new \RuntimeException('Server with that name already exists!');
				return $new_name;
			});
			$new_name = $helper->ask($input, $output, $question);
		}

		$server_config = $servers_list[$name];
		unset($servers_list[$name]);
		$servers_list[$new_name] = $server_config;

		if ($servers_list->save($config_file))
			$output->writeln('<info>Successfully updated</info>');
	}
}
