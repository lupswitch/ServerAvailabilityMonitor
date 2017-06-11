<?php
namespace wapmorgan\ServerAvailabilityMonitor;

use PHPMailer;

class Reporter {
	protected $configuration;

	public function __construct(Configuration $configuration) {
		$this->configuration = $configuration;
	}

	public function sendReport(array $failedServices, $checkTime, $debug = false) {
		$email = $this->configuration['email'];

		$mail = new PHPMailer();

		if ($debug)
			$mail->SMTPDebug = 3;

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
		$mail->Subject = 'Services Monitoring Report';
		$mail->Body = 'At '.date('r', $checkTime).' following services are not available ('.count($failedServices).' total):'.PHP_EOL;
		foreach ($failedServices as $failedService => $reason) {
			$mail->Body .= '- '.$failedService.': '.$reason.PHP_EOL;
		}


		if (!$mail->send())
			throw new \RuntimeException($mail->ErrorInfo);
		return true;
	}
}
