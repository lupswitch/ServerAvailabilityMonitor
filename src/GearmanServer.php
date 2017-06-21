<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class GearmanServer extends BaseServer {
	const DEFAULT_PORT = '4730';

	public function checkAvailability($timeOut) {
		if (extension_loaded('gearman')) {
			return $this->checkGearman($timeOut);
		}
		return new \RuntimeException('No available Gearman connectors found.');
	}

	protected function checkGeaman($timeOut) {
		$gearman = new \GearmanClient();
		$gearman->setTimeout($timeOut);
		if (!$gearman->ping('test'))
			return new \RuntimeException($gearman->error, $gearman->getErrno());
		return true;
	}

	public function getServerHash() {
		return md5($this->hostname.':'.$this->port);
	}
}
