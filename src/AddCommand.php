<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class AddCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('manage:add')

		// the short description shown while running "php bin/console list"
		->setDescription('Adds server to monitoring list.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to add servers for monitoring.')

		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())

		->addArgument('type', InputArgument::OPTIONAL, 'Type of the server')
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$helper = $this->getHelper('question');

		$type = $input->getArgument('type');

		if (empty($type) || !in_array($type, ServersList::SUPPORTED_TYPES)) {
			$question = new ChoiceQuestion(
				'Please select your type of server (defaults to http)',
				ServersList::SUPPORTED_TYPES,
				0
			);
			$question->setErrorMessage('Server type %s is invalid.');
			$type = $helper->ask($input, $output, $question);
		}

		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$servers_list = new ServersList($config_file);
		$server = ServersList::getServerByType($type);
		$server_rules = $server->getRules();

		foreach ($server_rules as $server_param => $param_rules) {
			switch ($param_rules[1]) {
				case 'question':
					$question = new Question($param_rules[2], $param_rules[3]);
					if (isset($param_rules[4]) && is_callable($param_rules[4]))
						$question->setValidator($param_rules[4]);
					while (true) {
						$answer = $helper->ask($input, $output, $question);
						if (strlen($answer) > 0 || $param_rules[0] == 'optional') {
							if (strlen($answer) > 0)
								$server->{$server_param} = $answer;
							break;
						}
					}
					break;
			}
		}

		$proposed_name = $type.($servers_list->getNextTypeId($type));

		$question = new Question('Please select name of server (defaults to '.$proposed_name.'): ', $proposed_name);
		$question->setValidator(function ($name) use ($servers_list) {
			$name = trim($name);
			if (empty($name))
				throw new \RuntimeException('Server name can not be empty');
			if (isset($servers_list[$name]))
				throw new \RuntimeException('Server with that name already exists!');
			return $name;
		});
		$name = $helper->ask($input, $output, $question);

		$servers_list[$name] = ((array)$server) + array('type' => $type);
		if ($servers_list->save($config_file))
			$output->writeln('<info>Successfully added to servers list</info>');
	}
}
