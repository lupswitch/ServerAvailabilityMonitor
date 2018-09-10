<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class DeleteCommand extends Command {

	protected function configure() {
		$this
		->setName('manage:delete')
		->setDescription('Delete server.')
		->setHelp('This command allows you to delete server from monitoring list.')
		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())
		->addArgument('name', InputArgument::OPTIONAL, 'Name of the server')
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

		$helper = $this->getHelper('question');

		$name = $input->getArgument('name');

		if (empty($name) || !isset($servers_list[$name])) {
			$question = new Question('Please provide name of server to delete: ');
			while (true) {
				$name = $helper->ask($input, $output, $question);
				if (!empty($name) && isset($servers_list[$name]))
					break;
			}
		}

		$question = new ConfirmationQuestion('Sure to delete this server?', false);

		if ($helper->ask($input, $output, $question)) {
			unset($servers_list[$name]);
			if ($servers_list->save($config_file))
				$output->writeln('<info>Successfully deleted</info>');
		}
	}
}
