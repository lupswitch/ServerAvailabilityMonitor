<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class HttpServer extends BaseServer {
	public $ip;
	public $port;
	public $resultCode;

	public function getRules() {
		return [
			'ip' => array('required', 'question', 'Provide IP-address of server to monitor: ', '127.0.0.1', function ($value) {
				if (!filter_var($value, FILTER_VALIDATE_IP))
					throw new \RuntimeException('A valid Ip should be x.x.x.x, where x is a digit between 0 and 255');
				return $value;
			}),
			'port' => array('required', 'question', 'Provide port of server: ', '80', function ($value) {
				$value = (int)$value;
				if ($value < 1 || $value > 65536)
					throw new \RuntimeException('A valid port should be in range from 1 to 65536');
				return $value;
			}),
			'resultCode' => array('optional', 'question', 'If you need to be sure that server works successfully, specify the result code to check: ', null, function ($value) {
				$value = (int)$value;
				if ($value !== 0) {
					if ($value < 1 || $value > 1000)
						throw new \RuntimeException('A valid resultCode should be in range from 1 to 1000');
				}
				return $value;
			})
		];
	}

	public function checkAvailability() {
		if (rand(0, 3) > 1)
			return new \RuntimeException('Some message');
		return true;
	}
}
