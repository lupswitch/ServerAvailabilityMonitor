<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @since 0.0.8
 */
class SelfUpdateCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('self-update')

		// the short description shown while running "php bin/console list"
		->setDescription('Update sam to newer version.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to update sam to newer version of fall back to previous one.')

		->addOption('desired_version', null, InputOption::VALUE_REQUIRED, 'The version to use', null)

		->addOption('rollback', 'r', InputOption::VALUE_NONE, 'Rollback to previous version')
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (basename(dirname(__DIR__)) != 'sam.phar') {
			$output->writeln('<comment>Run this command only from phar-file</comment>');
			return false;
		}

		$updater = new Updater(null, false, Updater::STRATEGY_GITHUB);

		if ($input->getOption('rollback')) {
			try {
				$result = $updater->rollback();
				if (!$result) {
					$output->writeln('<error>Errored</error>');
					return false;
				}
				$old_real = trim(file_get_contents(__DIR__.'/../bin/version.txt'));
				$output->writeln('<info>Rolled back to '.$old_real.' version.</info>');
			} catch (\Exception $e) {
				$output->writeln('<error>Well, something happened! Either an oopsie or something involving hackers.</error>');
			    $output->writeln('<error>'.$e->getMessage().'</error>');
			}
		}

		else {
			$version = $input->getOption('desired_version');
			if ($version === null) {

			}
			if (file_exists(__DIR__.'/../bin/version.txt')) {
				$current_version = trim(file_get_contents(__DIR__.'/../bin/version.txt'));
				$output->writeln('<comment>sam version: '.$current_version.'</comment>');
			}
			$updater->getStrategy()->setPackageName('wapmorgan/server-availability-monitor');
			$updater->getStrategy()->setPharName('sam.phar');
			if (isset($current_version))
				$updater->getStrategy()->setCurrentLocalVersion($current_version);

			try {
			    $result = $updater->update();
			    if ($result) {
			    	$new = $updater->getNewVersion();
					$old = $updater->getOldVersion();
					$new_real = trim(file_get_contents(__DIR__.'/../bin/version.txt'));
					$output->writeln('<info>Updated to '.$new_real.' version. To rollback to '.$old.' invoke with --rollback option.</info>');
    			}
			} catch (\Exception $e) {
			    $output->writeln('<error>Well, something happened! Either an oopsie or something involving hackers.</error>');
			    $output->writeln('<error>'.$e->getMessage().'</error>');
			}
		}
	}
}
