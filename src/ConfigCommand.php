<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class ConfigCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('report:config')

		// the short description shown while running "php bin/console list"
		->setDescription('Config the report rules.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to update configuration of report system. You should pass a param to configure. Possible values:'.PHP_EOL.
'- email - An email to send reports when one of services fails
- emailPeriod - Time until sending new email when services are still not working.
- checkPeriod - Time between checks of service availability.
- checkTimeOut - Time out for connection to services.
- log - Option to enable or disable logging.')

		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())

		->addArgument('param', InputArgument::REQUIRED, 'Parameter to configure.')
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$configuration = new Configuration($config_file);

		$helper = $this->getHelper('question');

		$param = $input->getArgument('param');

		if (!in_array($param, ['email', 'emailPeriod', 'checkPeriod', 'checkTimeOut', 'log'])) {
			$output->writeln('<error>Param should one of these: email, checkPeriod, checkTimeOut, log</error>');
			return false;
		}

		$input_macros = function ($currentValue, $defaultValue, $validator) use ($input, $output, $helper) {
			$output->writeln('Current value: '.$currentValue);
			$question = new Question('Please provide new value: ', $defaultValue);
			$question->setValidator($validator);
			return $helper->ask($input, $output, $question);
		};

		switch ($param) {
			case 'checkPeriod':
				$checkPeriod = $input_macros($configuration['checkPeriod'], $configuration['checkPeriod'], function ($value) {
					$value = (int)$value;
					if ($value <= 0)
						throw new \RuntimeException('Period should be positive integer');
					return $value;
				});
				$configuration['checkPeriod'] = $checkPeriod;
				break;

			case 'checkTimeOut':
				$checkTimeOut = $input_macros($configuration['checkTimeOut'], $configuration['checkTimeOut'], function ($value) {
					$value = (int)$value;
					if ($value <= 0)
						throw new \RuntimeException('Time out should be positive integer');
					return $value;
				});
				$configuration['checkTimeOut'] = $checkTimeOut;
				break;

			case 'emailPeriod':
				$emailPeriod = $input_macros($configuration['emailPeriod'], $configuration['emailPeriod'], function ($value) {
					$value = (int)$value;
					if ($value <= 0)
						throw new \RuntimeException('Period should be positive integer');
					return $value;
				});
				$configuration['emailPeriod'] = $emailPeriod;
				break;

			case 'email':
				$type_question = new ChoiceQuestion('Select transport system for email ('.($configuration['email'] === false ? 'disabled' : $configuration['email']['transport']).' now): ', ['disable', 'sendmail', 'SMTP'], ($configuration['email'] === false ? 'disable' : $configuration['email']['transport']));
				$email['transport'] = $helper->ask($input, $output, $type_question);
				if ($email['transport'] == 'disable') {
					$configuration['email'] = false;
					$output->writeln('<info>Disable email reports</info>');
					break;
				}
				// ask for auth information for SMTP
				if ($email['transport'] == 'SMTP') {
					$email['smtp']['host'] = $helper->ask($input, $output, (new Question('Provide SMTP host: ')));
					$email['smtp']['username'] = $helper->ask($input, $output, (new Question('Provide SMTP username: ')));
					$email['smtp']['password'] = $helper->ask($input, $output, (new Question('Provide SMTP password: ')));
					$email['smtp']['port'] = $helper->ask($input, $output, (new Question('Provide SMTP port (default 587): ', 587)));
					$email['smtp']['encryption'] = $helper->ask($input, $output, (new ChoiceQuestion('Select encryption (if used): ', ['none', 'ssl', 'tsl'], 0))->setValidator(function ($value) { return $value == 'none' ? false : $value; }));
				}
				$email['from'] = $helper->ask($input, $output, (new Question('Provide From field: '))->setValidator(function ($value) {
					if (!filter_var($value, FILTER_VALIDATE_EMAIL))
						throw new \RuntimeException('Invalid Email');
					return $value;
				}));
				$email['to'] = $helper->ask($input, $output, (new Question('Provide To field: '))->setValidator(function ($value) {
					if (!filter_var($value, FILTER_VALIDATE_EMAIL))
						throw new \RuntimeException('Invalid Email');
					return $value;
				}));

				if ($email['transport'] == 'SMTP') {
					$output->writeln('<info>Testing SMTP connection</info>');
				} else {
					$output->writeln('<info>Testing sending</info>');
				}
				$configuration['email'] = $email;
				$reporter = new EmailReporter($configuration);
				try {
					$reporter->testConfiguration();
				} catch (\RuntimeException $e) {
					$output->writeln('<error>Sending failed. Reason: '.$e->getMessage().'. Check your configuration and try again.</error>');
					return false;
				}
				break;

			case 'log':
				$log_question = new ChoiceQuestion('Enable or disable logging of check results ('.($configuration['log'] ? 'enabled' : 'disabled').' now): ', ['disable', 'enable'], $configuration['log']);
				$configuration['log'] = $helper->ask($input, $output, $log_question) == 'enable';
				break;
		}

		if ($configuration->save($config_file))
			$output->writeln('<info>Successfully updated</info>');
	}
}
