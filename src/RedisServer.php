<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class RedisServer extends BaseServer {
	const DEFAULT_PORT = '6379';

	public function checkAvailability($timeOut) {
		if (extension_loaded('redis')) {
			$redis = new \Redis();
			if ($redis->connect($this->hostname, $this->port, $timeOut) === false) return new \RuntimeException('Redis server is not available');
			try {
				$redis->ping();
			} catch (\RedisException $e) {
				return $e;
			}
			return true;
		}
		return new \RuntimeException('No available redis connectors found.');
	}

	public function getServerHash() {
		return md5($this->hostname.':'.$this->port);
	}
}
