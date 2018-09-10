<?php
namespace wapmorgan\ServerAvailabilityMonitor\Servers;

use mysqli;
use PDO;
use PDOException;

class MysqlServer extends BaseServer {
	const DEFAULT_PORT = '3306';
	public $username;
	public $password;

	public function getRules() {
		return parent::getRules() + [
			'username' => array('required', 'question', 'Username to access DB: ', null, function ($value) {
				$value = trim($value);
				if (empty($value))
					throw new \RuntimeException('A valid username should be a string');
				return $value;
			}),
			'password' => array('required', 'question', 'Password for username to access DB: ', null, function ($value) {
				$value = trim($value);
				if (empty($value))
					throw new \RuntimeException('A valid password should be a string');
				return $value;
			})
		];
	}

    /**
     * @param $timeOut
     * @return bool|\RuntimeException
     */
    public function checkAvailability($timeOut) {
		if (extension_loaded('pdo')) {
			return $this->checkPdo($timeOut);
		}

        if (extension_loaded('mysqli')) {
            return $this->checkMysqli($timeOut);
        }

        return new \RuntimeException('No available mysql connectors found.');
	}

    /**
     * @param $timeOut
     * @return bool|\RuntimeException
     */
    protected function checkPdo($timeOut) {
		try {
			$pdo = new PDO('mysql:host='.$this->hostname.';port='.$this->port, $this->username, $this->password, [
				PDO::ATTR_TIMEOUT => $timeOut,
			]);
		} catch (PDOException $e) {
			return new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
		return true;
	}

    /**
     * @param $timeOut
     * @return bool|\RuntimeException
     */
    protected function checkMysqli($timeOut) {
		$mysqli = new mysqli($this->hostname, $this->username, $this->password, null, $this->port);
		$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeOut);
		if ($mysqli->connect_error) {
			return new \RuntimeException($mysqli->connect_error, $mysqli->connect_errno);
		}
		return true;
	}

    /**
     * @return string
     */
    public function getServerHash() {
		return md5($this->hostname.':'.$this->port.'@'.$this->username.':'.$this->password);
	}
}
