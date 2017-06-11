<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class RedisServer extends BaseServer {
	const DEFAULT_PORT = '6379';

	public function checkAvailability() {
		if (extension_loaded('redis')) {
			$redis = new \Redis();
			if ($redis->connect($this->hostname, $this->port) === false) return new \RuntimeException('Redis server is not available');
			else return true;
		}
		return new \RuntimeException('No available redis connectors found.');
	}
}
