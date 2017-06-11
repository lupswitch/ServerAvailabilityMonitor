<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class MysqlServer {
	public $ip;
	public $port;
	public $username;
	public $password;

	public function getRules() {
		return [
			'ip' => array('required', 'question', 'Provide IP-address of server to monitor: ', '127.0.0.1', function ($value) {
				if (!filter_var($value, FILTER_VALIDATE_IP))
					throw new \RuntimeException('A valid Ip should be x.x.x.x, where x is a digit between 0 and 255');
				return $value;
			}),
			'port' => array('required', 'question', 'Provide port of server: ', '3306', function ($value) {
				$value = (int)$value;
				if ($value < 1 || $value > 65536)
					throw new \RuntimeException('A valid port should be in range from 1 to 65536');
				return $value;
			}),
			'username' => array('required', 'question', 'Username to access DB: ', null, function ($value) {
				$value = trim($value);
				if (empty($value))
					throw new \RuntimeException('A valid username should be a string');
				return $value;
			}),
			'password' => array('required', 'question', 'Password for username to access DB: ', null, function ($value) {
				$value = trim($value);
				if (empty($value))
					throw new \RuntimeException('A valid password should be a string');
				return $value;
			})
		];
	}
}
