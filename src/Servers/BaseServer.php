<?php
namespace wapmorgan\ServerAvailabilityMonitor\Servers;

use RuntimeException;

abstract class BaseServer {
	const DEFAULT_PORT = '80';

	/** @var string */
    public $port;

    /** @var string */
    public $hostname;

    public function getRules() {
		return [
			'hostname' => array('required', 'question', 'Provide IP-address or hostname of server to monitor: ', '127.0.0.1', function ($value) {
				if (!preg_match('~^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$~', $value) && !preg_match('~^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$~', $value))
					throw new \RuntimeException('A valid Ip should be x.x.x.x and a valid host name should be xxx.xxx');
				return $value;
			}),
			'port' => array('required', 'question', 'Provide port of server: ', static::DEFAULT_PORT, function ($value) {
				$value = (int)$value;
				if ($value < 1 || $value > 65536)
					throw new \RuntimeException('A valid port should be in range from 1 to 65536');
				return $value;
			})
		];
	}

    /**
	 * @param  integer Time out of check in seconds
	 * @return RuntimeException|boolean An exception if server is not available or true
	 */
	abstract public function checkAvailability($timeOut);

	/**
	 * @since 0.0.7
	 * @return string A 32-chars server hash. Used to identify server in log file.
	 */
	abstract public function getServerHash();
}
