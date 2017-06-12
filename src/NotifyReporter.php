<?php
namespace wapmorgan\ServerAvailabilityMonitor;

class NotifyReporter {
    const COMMAND = 'notify-send';
    // 20 sec
    const DEFAULT_EXPIRE_TIME = 20000;

    const URGENCY_LEVEL = 'critical';

    const ICON = 'openterm';

    static public $expireTime = false;

    static protected $notifyPath;
    static public function checkAvailability() {
        if (strncasecmp(PHP_OS, 'win', 3) === 0)
            return false;
        static::$notifyPath = exec('command -v '.static::COMMAND, $output, $returnCode);
        return $returnCode === 0;
    }

    public function sendReport(array $failedServices, $checkTime, $debug = false) {
        $expire_time = static::$expireTime ?: static::DEFAULT_EXPIRE_TIME;
        exec(static::COMMAND.' --icon='.static::ICON.' --expire-time='.$expire_time.' --urgency='.static::URGENCY_LEVEL.' "Failed Services" "These sevice failed during check at '.date('r', $checkTime).':'.PHP_EOL.'- '.implode(PHP_EOL.'- ', array_keys($failedServices)).'"', $output, $resultCode);
        return $resultCode === 0;
    }
}
