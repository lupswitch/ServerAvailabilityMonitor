<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use ArrayAccess;

class Configuration implements ArrayAccess {
	const DEFAULT_CHECK_PERIOD = 10;

	const DEFAULT_TIME_OUT = 3;

	protected $config;

	public function __construct($config) {
		if (file_exists($config)) {
			$all_config = json_decode(file_get_contents($config), true);
			$this->config = isset($all_config['configuration']) ? $all_config['configuration'] : static::getDefaultConfig();
		}
		else
			$this->config = static::getDefaultConfig();
	}

	public function save($config) {
		if (file_exists($config)) {
			$all_config = json_decode(file_get_contents($config), true);
			$all_config['configuration'] = $this->config;
		} else {
			$all_config = ['configuration' => $this->config];
		}
		return file_put_contents($config, json_encode($all_config)) !== false;
	}

	public function offsetExists($offset) {
		return isset($this->config[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->config[$offset]) ? $this->config[$offset] : false;
	}

	public function offsetSet($offset, $value) {
		$this->config[$offset] = $value;
	}

	public function offsetUnset($offset) {
		if (isset($this->config[$offset])) unset($this->config[$offset]);
	}

	static public function getDefaultConfig() {
		return [
			'checkPeriod' => static::DEFAULT_CHECK_PERIOD,
			'timeOut' => static::DEFAULT_TIME_OUT,
			'email' => false,
		];
	}
}
