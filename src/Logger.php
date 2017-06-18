<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use wapmorgan\BinaryStream\BinaryStream;

class Logger {
	protected $logFile;
	protected $log;
	protected $logServers = [];
	protected $isLogDataChanged = false;

	public function __construct($log) {
		$this->logFile = $log;
		// read log data
		if (file_exists($log)) {
			$this->log = new BinaryStream($this->logFile);
			$this->setupLogger();
			$this->readLog();
			unset($this->log);
		}
	}

	protected function setupLogger() {
		// 36 byte server header
		$this->log->saveGroup('server', [
			's:hash' => 32,
			'i:size' => 32,
		]);
		// 5 byte day results
		$this->log->saveGroup('dayResult', [
			'b:year' => 7,
			'b:month' => 4,
			'b:day' => 5,
			'i:results' => 24,
		]);
	}

	/**
	 * Reads all log
	 */
	protected function readLog() {
		while (!$this->log->isEnd()) {
			$server = $this->log->readGroup('server');
			$this->logServers[$server['hash']] = [
				'size' => $server['size'],
				'raw' => $this->log->readString($server['size']),
				'lastCheckResult' => false,
			];
			// decode last check
			if ($server['size'] > 0)
				$this->logServers[$server['hash']]['lastCheckResult'] = $this->decodeDayResult(substr($this->logServers[$server['hash']]['raw'], -5));
		}
	}

	/**
	 * @param string $binaryResult A binary string (5 chars) containing result data.
	 * @return array An array with elements: year (0-99), month (1-12), day (1-31), results (24 bit int)
	 */
	protected function decodeDayResult($binaryResult) {
		$result['year'] = 2000 + (ord($binaryResult[0]) >> 1);
		// need to add support for encoding years after 2100, then uncomment this line
		// if ($result['year'] < 2017) $result['year'] += 100;
		$result['month'] = ((ord($binaryResult[0]) & 1) << 3) + (ord($binaryResult[1]) >> 5);
		$result['day'] = ord($binaryResult[1]) & 0b11111;
		$result['results'] = (ord($binaryResult[2]) << 16) + (ord($binaryResult[3]) << 8) + ord($binaryResult[4]);
		return $result;
	}

	/**
	 * @param array $result An array with elements: year (0-99), month (1-12), day (1-31), results (24 bit int)
	 * @return string A binary string (5 chars) containing result data.
	 */
	public function encodeDayResult(array $result) {
		if ($result['year'] > 99) $result['year'] %= 100;
		$result = [
			(($result['year'] & 0b1111111) << 1) + ($result['month'] >> 3),
			(($result['month'] & 0b111) << 5) + ($result['day'] & 0b11111),
			($result['results'] >> 16) & 0xFF,
			($result['results'] >> 8) & 0xFF,
			$result['results'] & 0xFF,
		];
		$result = array_map(function ($int) { return chr($int); }, $result);
		return implode(null, $result);
	}

	/**
	 * Logs passed results of servers
	 * @param array $serverResults Indexes are server hashes, values are results (boolean)
	 */
	public function logCheckResults(array $serverResults, $checkTime) {
		$result_data = [
			'year' => date('Y', $checkTime) - 2000,
			'month' => (int)date('m', $checkTime),
			'day' => (int)date('d', $checkTime),
		];
		$check_hour = (int)date('G', $checkTime);

		foreach ($serverResults as $server_hash => $server_result) {

			// new server. create new log record
			if (!isset($this->logServers[$server_hash])) {
				$this->isLogDataChanged = true;
				$result_results = 0;
				if (!$server_result)
					$this->setHourStatus($result_results, $check_hour, false);
				$this->logServers[$server_hash] = [
					'size' => 5,
					'lastCheckResult' => $result_data + [
						'results' => $result_results,
					],
				];
				$this->logServers[$server_hash]['raw'] = $this->encodeDayResult($this->logServers[$server_hash]['lastCheckResult']);

			}
			// known server. update log record
			else {
				// if last record date coincides check date

				if ($this->logServers[$server_hash]['lastCheckResult'] !== false
					&& ($this->logServers[$server_hash]['lastCheckResult']['year'] % 100) == $result_data['year']
					&& $this->logServers[$server_hash]['lastCheckResult']['month'] == $result_data['month']
					&& $this->logServers[$server_hash]['lastCheckResult']['day'] == $result_data['day']) {
					// continue if server_result is false
					if (!$server_result) {
						// if this hour set (failed check)
						if ($this->getHourStatus($this->logServers[$server_hash]['lastCheckResult']['results'], $check_hour) === false) {
							// do nothing. this hour maked as failed
						} else {
							$this->isLogDataChanged = true;
							// if hour marked success, but check failed, mark it failed
							$this->setHourStatus($this->logServers[$server_hash]['lastCheckResult']['results'], $check_hour, false);
							// update raw field
							$this->logServers[$server_hash]['raw'] = substr($this->logServers[$server_hash]['raw'], 0, -5).$this->encodeDayResult($this->logServers[$server_hash]['lastCheckResult']);
						}
					}
				} else {
					// add new log record in server log for this date
					$this->isLogDataChanged = true;
					$result_results = 0;
					if (!$server_result)
						$this->setHourStatus($result_results, $check_hour, false);

					// update server log
					$this->logServers[$server_hash]['lastCheckResult'] = $result_data + [
						'results' => $result_results,
					];
					$this->logServers[$server_hash]['raw'] .= $this->encodeDayResult($this->logServers[$server_hash]['lastCheckResult']);
					$this->logServers[$server_hash]['size'] += 5;
				}
			}
		}

		// var_dump($this->logServers);
		// exit;

		// write log
		$this->writeLog();
	}

	/**
	 * @param integer $results A integer (24 bit) of check
	 * @param integer $hour    Hour of result
	 * @param boolean $result  New result of check in passed hour (true - success, false - failed)
	 * @return void
	 */
	protected function setHourStatus(&$results, $hour, $result) {
		// echo 'setHourStatus: results is ['.$results.'], hour - '.$hour.', result - '.(int)$result.', trace - '.print_r(debug_backtrace(0, 2), true).PHP_EOL;
		// failed result
		if (!$result) {
			$results |= 1 << (23 - $hour);
		}
		// echo 'results after set - '.print_r($results, true).PHP_EOL;
		// not need to set success status
		/* else {
			// only if bit is already set
			if (($results >> (23 - $hour) & 1))
				$results ^= 1 << (23 - $hour);
		}*/
	}

	/**
	 * @param integer $results A integer (24 bit) of check
	 * @param integer $hour    Hour of result
	 * @return boolean         Result of check (true - success, false -failed)
	 */
	protected function getHourStatus($results, $hour) {
		return !(boolean)(($results >> (23 - $hour)) & 1);
	}

	/**
	 * Recreates logs file with actual information
	 */
	protected function writeLog() {
		// if log data hasn't changed, don't rewrite log
		if (!$this->isLogDataChanged) {
			// var_dump('doesn\'t changed');
			return true;
		}

		if (file_exists($this->logFile))
			$this->log = new BinaryStream($this->logFile, BinaryStream::RECREATE);
		else
			$this->log = new BinaryStream($this->logFile, BinaryStream::CREATE);

		$this->setupLogger();

		foreach ($this->logServers as $server_hash => $server_log_data) {
			$this->log->writeString($server_hash);
			$this->log->writeInteger($server_log_data['size'], 32);
			$this->log->writeString($server_log_data['raw']);
		}
		unset($this->log);
	}

	public function extractServerLog($serverHash, $year = null, $month = null, $day = null) {
		if (!isset($this->logServers[$serverHash]))
			return false;

		$checks = [];

		foreach (str_split($this->logServers[$serverHash]['raw'], 5) as $check_result_raw) {
			$check_result = $this->decodeDayResult($check_result_raw);

			// filters
			if ($year !== null && $check_result['year'] != $year) continue;
			if ($month !== null && $check_result['month'] != $month) continue;
			if ($day !== null) {
				if ($check_result['day'] != $day)
					continue;
				// if used day filter, return just one log record
				else
					return $check_result;
			}

			$checks[$check_result['year']][$check_result['month']][$check_result['day']] = $check_result['results'];
		}

		if ($day !== null) {
			// we didn't returned log record in foreach, so there's no data for this date
			return false;
		}
		else if ($month !== null) {
			$month = (int)$month;
			return isset($checks[$year][$month]) ? $checks[$year][$month] : [];
		}
		else if ($year !== null)
			return isset($checks[$year]) ? $checks[$year] : [	];
		else
			return $checks;
	}

	public function extractHoursResults($resultsRaw) {
		$results = [];
		for ($i = 0; $i < 24; $i++)
			$results[$i] = !(boolean)(($resultsRaw >> (23 - $i)) & 1);
		return $results;
	}

	/**
	 * Returns default location of log file
	 */
	static public function getDefaultLogLocation() {
		if (strncasecmp(PHP_OS, 'win', 3) === 0) {
			if (isset($_SERVER['USERPROFILE']))
				return $_SERVER['USERPROFILE'].'\\.monitor.blog';
			else
				return realpath(__DIR__.'/..').'\\.monitor.blog';
		} else {
			if (isset($_SERVER['HOME']))
				return $_SERVER['HOME'].'/.monitor.blog';
			else
				return realpath(__DIR__.'/..').'/.monitor.blog';
		}
	}
}
