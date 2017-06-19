<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class ServerLogCommand extends Command {

	protected function configure() {
		$this
		// the name of the command (the part after "bin/console")
		->setName('log')

		// the short description shown while running "php bin/console list"
		->setDescription('Log viewer of specific server.')

		// the full command description shown when running the command with
		// the "--help" option
		->setHelp('This command allows you to view log of check results of a server.')

		->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())
		->addOption('log', 'l', InputOption::VALUE_REQUIRED, 'The location of log-file', Logger::getDefaultLogLocation())

		->addOption('short', 's', InputOption::VALUE_NONE, 'Show results in short form')

		->addOption('all-years', 'Y', InputOption::VALUE_NONE, 'Show results for all years')
		->addOption('year', 'y', InputOption::VALUE_OPTIONAL, 'The year of log to view', date('Y'))
		->addOption('all-months', 'M', InputOption::VALUE_NONE, 'Show results for all months in the year')
		->addOption('month', 'm', InputOption::VALUE_OPTIONAL, 'The month of log to view', date('m'))
		->addOption('all-days', 'D', InputOption::VALUE_NONE, 'Show results for all days in the month')
		->addOption('day', 'd', InputOption::VALUE_OPTIONAL, 'The day of log to view', date('d'))

		->addArgument('server', InputArgument::OPTIONAL, 'Name of the server')
	;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
		$servers_list = new ServersList($config_file);
		$logger = new Logger($input->getOption('log'));

		$helper = $this->getHelper('question');

		$name = $input->getArgument('server');

		$server_names = $servers_list->getServerNames();
		if (empty($server_names)) {
			$output->writeln('<info>Firstly, add servers via manage:add command.</info>');
			return true;
		}

		if (empty($name) || !isset($servers_list[$name])) {
			$question = new ChoiceQuestion('Please provide name of server to view log: ', $server_names);
			while (true) {
				$name = $helper->ask($input, $output, $question);
				if (!empty($name) && isset($servers_list[$name]))
					break;
			}
		}

		$servers_list->initializeServers();
		$server = $servers_list->getServer($name);

		$year = $input->getOption('year');
		$month = $input->getOption('month');
		$day = $input->getOption('day');

		// show summary for all years
		if ($input->getOption('all-years') || !$input->getOption('short')) {
			$all_checks = array_map(function($year_checks) {
				foreach ($year_checks as $month_checks) {
					foreach ($month_checks as $day_check) {
						if ($day_check !== 0)
							return false;
					}
				}
				return true;
			}, $logger->extractServerLog($server->getServerHash()) ?: []);
			$table = new Table($output);
			$this->renderLogTable($input->getOption('short'), $table, 'All log', $name, $all_checks);
		}

		// show summary for all months
		if ($input->getOption('all-months') || !$input->getOption('short')) {
			$year_checks = array_map(function($month_checks) {
				foreach ($month_checks as $day_check) {
					if ($day_check !== 0)
						return false;
				}
				return true;
			}, $logger->extractServerLog($server->getServerHash(), $year) ?: []);
			$table = new Table($output);
			$this->renderLogTable($input->getOption('short'), $table, 'Log for '.$year, $name, $year_checks);
		}

		// show summary for all days
		if ($input->getOption('all-days') || !$input->getOption('short')) {
			$month_checks = array_map(function($day_check) { return $day_check === 0; }, $logger->extractServerLog($server->getServerHash(), $year, $month) ?: []);
			$table = new Table($output);
			$this->renderLogTable($input->getOption('short'), $table, 'Log for '.$year.'-'.$month, $name, $month_checks);
		}

		if (!$input->getOption('short') || (!$input->getOption('all-days') && !$input->getOption('all-months') && !$input->getOption('all-years'))) {
			// show report for specific date
			$day_check = $logger->extractServerLog($server->getServerHash(), $year, $month, $day);
			if ($day_check === false)
				$check_results = [];
			else
				$check_results = $logger->extractHoursResults($day_check['results']);
			// simple check for nonexistent results for current date
			if (strtotime(date('Y-m-d')) == strtotime($year.'-'.$month.'-'.$day)) {
				$hour = (int)date('G');
				$check_results = array_filter($check_results, function ($check_hour) use ($hour) { return ($hour >= $check_hour); }, ARRAY_FILTER_USE_KEY);
			}

			$table = new Table($output);
			$this->renderLogTable($input->getOption('short'), $table, 'Log for '.$year.'-'.$month.'-'.$day, $name, $check_results);
		}
	}

	protected function renderLogTable($isShortForm, Table $table, $title, $server, $check_results) {
		if ($isShortForm) {
			$headers = ([$server, $title]);

			$results_row = null;
			$success_checks = 0;
			foreach ($check_results as $hour => $result) {
				$results_row .= ($result) ? '<info>+</info>' : '<error>-</error>';
				if ($result) $success_checks++;
			}
			$rows = [[($success_checks == count($check_results) ? 'All checks passed' :
				$success_checks.' of '.count($check_results).' checks passed'
				), $results_row]];
			$this->renderTable($table, $headers, $rows);
		} else {
			$row = [$server];
			$success_checks = 0;
			foreach ($check_results as $hour => $result) {
				if ($result) $success_checks++;
				$row[] = ($result) ? '<info>+</info>' : '<error>-</error>';
			}

			$headers = array_keys($check_results);
			array_unshift($headers, $title);
			$rows = [
				$row,
				new TableSeparator(),
				[new TableCell($success_checks == count($check_results)
					? 'All checks passed'
					: $success_checks.' of '.count($check_results).' checks passed', ['colspan' => count($check_results) + 1])]
			];
			$this->renderTable($table, $headers, $rows);
		}
	}

	protected function renderTable(Table $table, array $headers, array $rows) {
		$table->setHeaders($headers);
		$table->setRows($rows);
		$table->render();
	}
}
