<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use ArrayAccess;

class Configuration implements ArrayAccess {
	const DEFAULT_CHECK_PERIOD = 10;

	const DEFAULT_CHECK_TIME_OUT = 3;

	const DEFAULT_EMAIL_PERIOD = 300;

	protected $config;

	public function __construct($config) {
		if (file_exists($config)) {
			$all_config = json_decode(file_get_contents($config), true);
			$this->config = isset($all_config['configuration']) ? static::updateConfiguration($all_config['configuration']) : static::getDefaultConfig();
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
			'checkTimeOut' => static::DEFAULT_CHECK_TIME_OUT,
			'emailPeriod' => static::DEFAULT_EMAIL_PERIOD,
			'email' => false,
		];
	}

	/**
	 * Method conaining all cumulative changes made in configuration.
	 * @since 0.0.6
	 */
	static public function updateConfiguration(array $configuration) {
		// 0.0.6 timeOut -> checkTimeOut
		if (isset($configuration['timeOut'])) {
			if (!isset($configuration['checkTimeOut']))
				$configuration['checkTimeOut'] = $configuration['timeOut'];
			unset($configuration['timeOut']);
		}

		// 0.0.6 + emailPeriod
		if (!isset($configuration['emailPeriod']))
			$configuration['emailPeriod'] = static::DEFAULT_EMAIL_PERIOD;

		return $configuration;
	}
}
