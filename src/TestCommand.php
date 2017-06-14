<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class TestCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('test')

		->setHidden(true)

		// the short description shown while running "php bin/console list"
		->setDescription('Test different functionality.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to test different functions.')

		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())

		->addArgument('test', InputArgument::REQUIRED, 'Function for test')
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$configuration = new Configuration($config_file);
		$servers_list = new ServersList($config_file);

		switch ($input->getArgument('test')) {
			case 'emailTest':
				$email_reporter = new EmailReporter($configuration);
				try {
					if ($email_reporter->testConfiguration())
						$output->writeln('<info>Sending successfull.</info>');
				} catch (\RuntimeException $e) {
					$output->writeln('<error>Sending failed. Reason: '.$e->getMessage().'. Check your configuration and try again.</error>');
					return false;
				}
				break;
			case 'email':
				$email_reporter = new EmailReporter($configuration);
				try {
					if ($email_reporter->sendReport(['a_dummy_server' => 'a dummy fail reason'], time()))
						$output->writeln('<info>Sending successfull.</info>');
				} catch (\RuntimeException $e) {
					$output->writeln('<error>Sending failed. Reason: '.$e->getMessage().'. Check your configuration and try again.</error>');
					return false;
				}
				break;
		}
	}
}
