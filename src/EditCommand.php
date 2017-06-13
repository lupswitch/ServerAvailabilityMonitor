<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class EditCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('manage:edit')

		// the short description shown while running "php bin/console list"
		->setDescription('Edit server.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to update configuration of server.')

		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())

		->addArgument('name', InputArgument::OPTIONAL, 'Name of the server')
		->addArgument('property', InputArgument::OPTIONAL, 'Property of server')
		->addArgument('value', InputArgument::OPTIONAL, 'New value of property')
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$servers_list = new ServersList($config_file);

		$helper = $this->getHelper('question');

		$name = $input->getArgument('name');

		if (empty($name) || !isset($servers_list[$name])) {
			$question = new Question('Please provide name of server to update: ');
			while (true) {
				$name = $helper->ask($input, $output, $question);
				if (!empty($name) && isset($servers_list[$name]))
					break;
			}
		}

		$server_config = $servers_list[$name];
		$new_server = ServersList::getServerByType($server_config['type']);
		$available_parameters = array_unique(
			array_merge(
				array_keys(get_object_vars($new_server)),
				array_diff(array_keys($server_config), array('type'))
			)
		);

		$property = $input->getArgument('property');
		if (empty($property) || $property == 'type' || !isset($server_config[$property])) {
			$question = new ChoiceQuestion('Please provide property to update: ', $available_parameters);
			while (true) {
				$property = $helper->ask($input, $output, $question);
				if (!empty($property) && $property != 'type' && in_array($property, $available_parameters))
					break;
			}
		}

		$value = $input->getArgument('value');
		$property_rules = (ServersList::getServerByType($server_config['type'])->getRules())[$property];


		if (!empty($value)) {
			try {
				$value = $property_rules[4]($value);
			} catch (\RuntimeException $e) {
				$output->writeln('<error>'.$e->getMessage().'</error>');
			}
		}

		if (empty($value)) {
			$output->writeln('Current value: '.(array_key_exists($property, $server_config) ? $server_config[$property] : $new_server->{$property}));
			$question = new Question('Please provide new value: ', $property_rules[3]);
			$question->setValidator($property_rules[4]);
			$value = $helper->ask($input, $output, $question);
		}

		$server_config[$property] = $value;
		$servers_list[$name] = $server_config;

		if ($servers_list->save($config_file))
			$output->writeln('<info>Successfully updated</info>');
	}
}
