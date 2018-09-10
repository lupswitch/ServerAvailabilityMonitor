<?php
namespace wapmorgan\ServerAvailabilityMonitor\Servers;

use Redis;
use RedisException;
use RuntimeException;

class RedisServer extends BaseServer {
	const DEFAULT_PORT = '6379';

    /**
     * @param $timeOut
     * @return bool|\RuntimeException|RuntimeException
     */
    public function checkAvailability($timeOut) {
		if (extension_loaded('redis')) {
			$redis = new Redis();
			if ($redis->connect($this->hostname, $this->port, $timeOut) === false) return new \RuntimeException('Redis server is not available');
			try {
				$redis->ping();
			} catch (RedisException $e) {
				return new RuntimeException($e->getMessage(), $e->getCode(), $e);
			}
			return true;
		}
		return new \RuntimeException('No available redis connectors found.');
	}

	public function getServerHash() {
		return md5($this->hostname.':'.$this->port);
	}
}
