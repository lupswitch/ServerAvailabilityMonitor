<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class HttpServer extends BaseServer {
	const DEFAULT_PORT = '80';

	public $resultCode;

	public function getRules() {
		return parent::getRules() + [
			'resultCode' => array('optional', 'question', 'If you need to be sure that server works successfully, specify the result code to check: ', null, function ($value) {
				$value = (int)$value;
				if ($value !== 0) {
					if ($value < 1 || $value > 1000)
						throw new \RuntimeException('A valid resultCode should be in range from 1 to 1000');
				}
				return $value;
			})
		];
	}

	public function checkAvailability($timeOut) {
		if (extension_loaded('curl')) {
			return $this->checkCurl($timeOut);
		} else {
			return $this->checkInternal($timeOut);
		}
	}

	protected function checkCurl($timeOut) {
		$curlInit = curl_init('http://'.$this->hostname.':'.$this->port);
		curl_setopt_array($curlInit, [
			CURLOPT_CONNECTTIMEOUT => $timeOut,
			CURLOPT_NOBODY => true,
			CURLOPT_RETURNTRANSFER => true
		]);
		$response = curl_exec($curlInit);
		if ($response === false) return new \RuntimeException('Http server is not available');

		if (!empty($this->resultCode)) {
			$result_code = curl_getinfo($curlInit, CURLINFO_HTTP_CODE);
			if ($result_code != $this->resultCode) return new \RuntimeException('Http server reports '.$result_code.' code when expecting '.$this->resultCode);
		}

		curl_close($curlInit);
		return true;
	}

	protected function checkInternal($timeOut) {
		$defaults = stream_context_set_default(
			[
				'http' => [
					'timeout' => $timeOut,
				]
			]
		);
		$headers = get_headers('http://'.$this->hostname.':'.$this->port);
		stream_context_set_default($defaults);

		if ($headers === false) return new \RuntimeException('Http server is not available');

		if (!empty($this->resultCode)) {
			$first_line = explode(' ', $headers[0]);
			$result_code = (int)$first_line[1];
			if ($result_code != $this->resultCode) return new \RuntimeException('Http server reports '.$result_code.' code when expecting '.$this->resultCode);
		}

		return true;
	}
}
