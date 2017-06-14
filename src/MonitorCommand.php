<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MonitorCommand extends Command {

	protected function configure() {
        $this
        // the name of the command (the part after "bin/console")
        ->setName('monitor')

        // the short description shown while running "php bin/console list"
        ->setDescription('Monitors all servers.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command allows you to monitor all configured servers.')

        ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The location of config-file', ServersList::getDefaultConfigLocation())
        ->addOption('checkPeriod', null, InputOption::VALUE_REQUIRED, 'The period of checks', null)
        ->addOption('checkTimeOut', null, InputOption::VALUE_REQUIRED, 'The time out for checks', null)
    ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $config_file = $input->getOption('config') ?: ServersList::getDefaultConfigLocation();
        $servers_list = new ServersList($config_file);
        $configuration = new Configuration($config_file);

        $check_period = $input->getOption('checkPeriod') ?: $configuration['checkPeriod'];
        $time_out = $input->getOption('checkTimeOut') ?: $configuration['checkTimeOut'];

        $reporters = [];
        if ($configuration['email'] !== false)
            $reporters[] = new EmailReporter($configuration);
        if (NotifyReporter::checkAvailability()) {
            NotifyReporter::$expireTime = $check_period * 1000;
            $reporters[] = new NotifyReporter();
        }

        $servers_list->initializeServers();

        $server_names = $servers_list->getServerNames();
        if (empty($server_names)) {
            $output->writeln('<info>Firstly, add servers via manage:add command.</info>');
            return true;
        }

        while (true) {
            $check_time = time();
            $errors = [];
            foreach ($server_names as $server_name) {
                if ($output->isDebug())
                    $output->writeln('Checking '.$server_name);
                $server = $servers_list->getServer($server_name);

                $result = $server->checkAvailability($time_out);
                if ($result === true) {
                    if ($output->isDebug())
                        $output->writeln('<comment>Server '.$server_name.' is ok</comment>');
                } else {
                    $errors[$server_name] = $result;
                    if ($output->isVeryVerbose())
                        $output->writeln('<error>Server '.$server_name.' ['.$server->hostname.':'.$server->port.'] check failed</error>');
                }
            }
            if (empty($errors)) {
                if ($output->isVerbose())
                    $output->writeln('<info>Check at '.date('r', $check_time).': all servers successfull</info>');
            }
            else {
                // output errors in logs / on the terminal
                $output->writeln('<info>Check at '.date('r', $check_time).': '.count($errors).' error'.(count($errors) > 1 ? 's' : null).'</info>');
                $report = [];
                foreach ($errors as $server_name => $error) {
                    if ($output->isVerbose())
                        $output->writeln('<error>'.$server_name.' reported error: '.$error->getMessage().'</error>');
                    else
                        $output->writeln('<error>'.$server_name.' reported error</error>');
                    $report[$server_name] = $error->getMessage();
                }

                // send reports
                foreach ($reporters as $reporter) {
                    $reporter->sendReport($report, $check_time);
                }
            }
            sleep($check_period);
        }
    }
}
