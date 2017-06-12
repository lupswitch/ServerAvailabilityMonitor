<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class MemcacheServer extends BaseServer {
	const DEFAULT_PORT = '11211';

	public function checkAvailability() {
		if (class_exists('\Memcache')) {
			$memcache = new \Memcache();
			if (@$memcache->connect($this->hostname, $this->port) === false) return new \RuntimeException('Memcache server is not available');
			else return true;
		} else if (class_exists('\Memcached')) {
			$memcached = new \Memcached();
			$memcached->addServer($this->hostname, $this->port);
			$version = @$memcached->getVersion();
			if (in_array(current($version), [false, '255.255.255'])) return new \RuntimeException('Memcache server is not available');
			else return true;
		}
		return new \RuntimeException('No available memcache connectors found.');
	}
}
