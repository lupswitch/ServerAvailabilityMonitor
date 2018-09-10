<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use ArrayAccess;
use wapmorgan\ServerAvailabilityMonitor\Servers\GearmanServer;
use wapmorgan\ServerAvailabilityMonitor\Servers\HttpServer;
use wapmorgan\ServerAvailabilityMonitor\Servers\MemcacheServer;
use wapmorgan\ServerAvailabilityMonitor\Servers\MysqlServer;
use wapmorgan\ServerAvailabilityMonitor\Servers\PostgreSqlServer;
use wapmorgan\ServerAvailabilityMonitor\Servers\RabbitMQServer;
use wapmorgan\ServerAvailabilityMonitor\Servers\RedisServer;

class ServersList implements ArrayAccess {

	const SUPPORTED_TYPES = [
		'http',
		'mysql',
		'postgresql',
		'memcache',
		'redis',
		'gearman',
		'rabbitmq'
	];

	protected $serversConfig;
	protected $servers;

    /**
     * ServersList constructor.
     * @param $config
     */
    public function __construct($config) {
		if (file_exists($config)) {
			$all_config = json_decode(file_get_contents($config), true);
			$this->serversConfig = isset($all_config['servers']) ? $all_config['servers'] : [];
		}
		else
			$this->serversConfig = [];
	}

    /**
     * @param $config
     * @return bool
     */
    public function save($config) {
		if (file_exists($config)) {
			$all_config = json_decode(file_get_contents($config), true);
			$all_config['servers'] = $this->serversConfig;
		} else {
			$all_config = ['servers' => $this->serversConfig];
		}
		return file_put_contents($config, json_encode($all_config)) !== false;
	}

    /**
     *
     */
    public function initializeServers() {
		foreach ($this->serversConfig as $server_name => $server_config) {
			$this->servers[$server_name] = static::getServerByType($server_config['type']);
			foreach ($server_config as $config_param => $param_value) {
				if (!$config_param != 'type') $this->servers[$server_name]->{$config_param} = $param_value;
			}
		}
	}

    /**
     * @return array
     */
    public function getServerNames() {
		return array_keys($this->serversConfig);
	}

    /**
     * @param $name
     * @return bool
     */
    public function getServer($name) {
		return isset($this->servers[$name]) ? $this->servers[$name] : false;
	}

    /**
     * @param $type
     * @return int
     */
    public function getNextTypeId($type) {
		$id = 1;
		while (isset($this->serversConfig[$type.$id])) {
			$id++;
		}
		return $id;
	}

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
		return isset($this->serversConfig[$offset]);
	}

    /**
     * @param mixed $offset
     * @return bool|mixed
     */
    public function offsetGet($offset) {
		return isset($this->serversConfig[$offset]) ? $this->serversConfig[$offset] : false;
	}

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
		$this->serversConfig[$offset] = $value;
	}

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
		if (isset($this->serversConfig[$offset])) unset($this->serversConfig[$offset]);
	}

    /**
     * @param $type
     * @return GearmanServer|HttpServer|MemcacheServer|MysqlServer|PostgreSqlServer|RabbitMQServer|RedisServer
     */
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
			case 'gearman':
				return new GearmanServer();
			case 'rabbitmq':
				return new RabbitMQServer();
		}
	}

    /**
     * @return string
     */
    static public function getDefaultConfigLocation() {
		if (strncasecmp(PHP_OS, 'win', 3) === 0) {
			if (isset($_SERVER['USERPROFILE']))
				return $_SERVER['USERPROFILE'].'\\.monitor.json';
			else
				return realpath(__DIR__.'/..').'\\.monitor.json';
		} else {
			if (function_exists('posix_geteuid') && posix_geteuid() === 0)
				return '/etc/monitor.json';
			else if (isset($_SERVER['HOME']))
				return $_SERVER['HOME'].'/.monitor.json';
			else
				return realpath(__DIR__.'/..').'/.monitor.json';
		}
	}
}
