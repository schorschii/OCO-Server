<?php

class OcoPurchase {

	const PRICE = 2.99;
	const FREE_OBJECTS = 20;
	const SKU   = 'systems.sieber.oco.license';
	const DEBUG = false;

	public function startFlow($cartName, $itemName, $itemAmount) {
		$cart = [
			[
				'name' => $itemName,
				'sku' => self::SKU, 'amount' => $itemAmount, 'price' => self::PRICE
			]
		];
		$pp = new PayPal();
		return $pp->createTransaction($cart, $cartName, serialize(['amount'=>$itemAmount, 'company'=>$_POST['company']]));;
	}

	public function completeFlow($result, $paymentId, $payerId, $mailSubject, $mailBody) {
		$returnValues = ['email'=>'?'];
		if($result != 'success') {
			throw new PaymentCanceledException();
		}
		if(!empty($paymentId) && !empty($payerId)) {
			try {
				$pp = new PayPal();
				$result = $pp->completeTransaction($paymentId, $payerId);
				if(!$result) {
					throw new PaymentFailedException();
				}
				$email = $result->getPayer()->getPayerInfo()->getEmail();
				$returnValues['email'] = $email;

				$custom = unserialize($result->getTransactions()[0]->getCustom());
				$licenseFilePath = $this->generateLicenseFile($email, $custom['company'], $custom['amount']);

				$this->sendMail(PAYPAL_LIVE ? $email : MAIL_SENDER_ADDR, $mailSubject, $mailBody, $licenseFilePath);
			} catch(Exception $ex) {
				throw new PaymentFailedException();
			}
		}
		return $returnValues;
	}

	function generateLicenseFile($to, $company, $objects) {
		$valid_until = strtotime( (date('Y')+1).'-'.date('m').'-'.date('d') );
		$objects = $objects + self::FREE_OBJECTS;

		$data = md5($company.$objects.$valid_until);
		openssl_sign($data, $signature, OCO_SIGN_PRIV_KEY);

		$jsoncontent = [
			'company' => $company,
			'objects' => $objects,
			'valid_until' => $valid_until,
			'signature' => base64_encode($signature)
		];
		$tmpFilePath = sys_get_temp_dir().'/'.time().'.ocolicense';
		file_put_contents($tmpFilePath, json_encode($jsoncontent));

		return $tmpFilePath;
	}

	private function sendMail($to, $subject, $message, $tmpFilePath) {
		return Mailer::send($to, $subject, $message."\n", MAIL_SENDER_NAME, MAIL_SENDER_ADDR, MAIL_SENDER_ADDR, [$tmpFilePath]);
	}

}
