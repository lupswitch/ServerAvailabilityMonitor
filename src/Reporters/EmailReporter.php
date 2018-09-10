<?php
namespace wapmorgan\ServerAvailabilityMonitor\Reporters;

use PHPMailer;
use wapmorgan\ServerAvailabilityMonitor\Configuration;

class EmailReporter {
	protected $configuration;
	protected $lastReportTime;

    /**
     * EmailReporter constructor.
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration) {
		$this->configuration = $configuration;
	}

    /**
     * @return bool
     * @throws \phpmailerException
     */
    public function testConfiguration() {
		$mailer = $this->initializeMailer();
		$mailer->SMTPDebug = 3;
		$mailer->Subject = 'Server Availability Monitor test message';
		$mailer->Body = 'This is a test message used to check email settings.';

		if (!$mailer->send())
			throw new \RuntimeException($mailer->ErrorInfo);
		return true;
	}

    /**
     * @param array $failedServices
     * @param $checkTime
     * @return bool
     * @throws \phpmailerException
     */
    public function sendReport(array $failedServices, $checkTime) {
		if ($this->lastReportTime !== false) {
			// don't send new messages for next `emailPeriod` seconds after previous message
			if ((time() - $this->lastReportTime) < $this->configuration['emailPeriod']) {
				return true;
			}
			$this->lastReportTime = time();
		}

		$mailer = $this->initializeMailer();
		$mailer->Subject = 'Services Monitoring Report';
		$mailer->Body = 'At '.date('r', $checkTime).' following services are not available ('.count($failedServices).' total):'.PHP_EOL;
		foreach ($failedServices as $failedService => $reason) {
			$mailer->Body .= '- '.$failedService.': '.$reason.PHP_EOL;
		}


		if (!$mailer->send())
			throw new \RuntimeException($mailer->ErrorInfo);
		return true;
	}

    /**
     * @return PHPMailer
     * @throws \phpmailerException
     */
    protected function initializeMailer() {
		$email = $this->configuration['email'];

		$mail = new PHPMailer();

		if ($email['transport'] == 'SMTP') {
			$mail->isSMTP();
			$mail->SMTPAuth = true;
			$mail->Host = $email['smtp']['host'];
			$mail->Username = $email['smtp']['username'];
			$mail->Password = $email['smtp']['password'];
			$mail->Port = $email['smtp']['port'];
			if ($email['smtp']['encryption'] !== false)
				$mail->SMTPSecure = $email['smtp']['encryption'];
		}

		$mail->setFrom($email['from'], 'Server Monitoring Service');
		$mail->addAddress($email['to']);

		return $mail;
	}
}
