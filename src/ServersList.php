<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use ArrayAccess;

class ServersList implements ArrayAccess {

	const SUPPORTED_TYPES = [
		'http',
		'mysql',
		'postgresql',
		'memcache',
		'redis',
	];

	protected $serversConfig;
	protected $servers;

	public function __construct($config) {
		if (file_exists($config)) {
			$all_config = json_decode(file_get_contents($config), true);
			$this->serversConfig = isset($all_config['servers']) ? $all_config['servers'] : [];
		}
		else
			$this->serversConfig = [];
	}

	public function save($config) {
		if (file_exists($config)) {
			$all_config = json_decode(file_get_contents($config), true);
			$all_config['servers'] = $this->serversConfig;
		} else {
			$all_config = ['servers' => $this->serversConfig];
		}
		return file_put_contents($config, json_encode($all_config)) !== false;
	}

	public function initializeServers() {
		foreach ($this->serversConfig as $server_name => $server_config) {
			$this->servers[$server_name] = static::getServerByType($server_config['type']);
			foreach ($server_config as $config_param => $param_value) {
				if (!$config_param != 'type') $this->servers[$server_name]->{$config_param} = $param_value;
			}
		}
	}

	public function getServerNames() {
		return array_keys($this->serversConfig);
	}

	public function getServer($name) {
		return isset($this->servers[$name]) ? $this->servers[$name] : false;
	}

	public function getNextTypeId($type) {
		$id = 1;
		while (isset($this->serversConfig[$type.$id])) {
			$id++;
		}
		return $id;
	}

	public function offsetExists($offset) {
		return isset($this->serversConfig[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->serversConfig[$offset]) ? $this->serversConfig[$offset] : false;
	}

	public function offsetSet($offset, $value) {
		$this->serversConfig[$offset] = $value;
	}

	public function offsetUnset($offset) {
		if (isset($this->serversConfig[$offset])) unset($this->serversConfig[$offset]);
	}

	static public function getServerByType($type) {
		switch ($type) {
			case 'http':
				return new HttpServer();
			case 'mysql':
				return new MysqlServer();
			case 'postgresql':
				return new PostgreSqlServer();
			case 'memcache':
				return new MemcacheServer();
			case 'redis':
				return new RedisServer();
		}
	}

	static public function getDefaultConfigLocation() {
		if (strncasecmp(PHP_OS, 'win', 3) === 0) {
			if (isset($_SERVER['USERPROFILE']))
				return $_SERVER['USERPROFILE'].'\\.monitor.json';
			else
				return realpath(__DIR__.'/..').'\\.monitor.json';
		} else {
			if (isset($_SERVER['HOME']))
				return $_SERVER['HOME'].'\\.monitor.json';
			else
				return realpath(__DIR__.'/..').'/.monitor.json';
		}
	}
}
