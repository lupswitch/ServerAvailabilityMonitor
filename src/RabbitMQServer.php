<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQServer extends BaseServer {
	const DEFAULT_PORT = '5672';
	public $username;
	public $password;

	public function getRules() {
		return parent::getRules() + [
			'username' => array('required', 'question', 'Username to access queue: ', null, function ($value) {
				$value = trim($value);
				if (empty($value))
					throw new \RuntimeException('A valid username should be a string');
				return $value;
			}),
			'password' => array('required', 'question', 'Password for username to access queue: ', null, function ($value) {
				$value = trim($value);
				if (empty($value))
					throw new \RuntimeException('A valid password should be a string');
				return $value;
			})
		];
	}

	public function checkAvailability($timeOut) {
		if (class_exists('\PhpAmqpLib\Connection\AMQPStreamConnection')) {
			return $this->checkAmqpLib($timeOut);
		}
		return new \RuntimeException('No available RabbitMQ connectors found. Install the "php-amqplib/php-amqplib" library.');
	}

	protected function checkAmqpLib($timeOut) {
		$rabbitmq = new AMQPStreamConnection($this->hostname, $this->port, $this->username, $this->password,
			// default values
			'/', false, 'AMQPLAIN', null, 'en_US',
			$timeOut);
		try {
			return $rabbitmq->isConnected() === true ? true : new \RuntimeException('RabbitMQ server is not available');
		} catch (\Exception $e) {
			return $e;
		}
	}

	public function getServerHash() {
		return md5($this->hostname.':'.$this->port.'@'.$this->username.':'.$this->password);
	}
}
