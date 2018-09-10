<?php
namespace wapmorgan\ServerAvailabilityMonitor\Servers;

use GearmanClient;
use RuntimeException;

class GearmanServer extends BaseServer {
	const DEFAULT_PORT = '4730';

    /**
     * @param $timeOut
     * @return bool|RuntimeException
     */
    public function checkAvailability($timeOut) {
		if (extension_loaded('gearman')) {
			return $this->checkGearman($timeOut);
		}
		return new RuntimeException('No available Gearman connectors found.');
	}

    /**
     * @param $timeOut
     * @return bool|RuntimeException
     */
    protected function checkGearman($timeOut) {
		$gearman = new GearmanClient();
		$gearman->setTimeout($timeOut);
		if (!$gearman->ping('test'))
			return new RuntimeException($gearman->error, $gearman->getErrno());
		return true;
	}

    /**
     * @return string
     */
    public function getServerHash() {
		return md5($this->hostname.':'.$this->port);
	}
}
