<?php
require_once(__DIR__.'/PayPal-PHP-SDK/autoload.php');

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\InputFields;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;
use PayPal\Api\WebProfile;

class PayPal {

	private $apiContext;

	function __construct() {
		$this->apiContext = new ApiContext(
			new OAuthTokenCredential(PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET)
		);
		if(PAYPAL_LIVE) {
			$this->apiContext->setConfig(
				['mode' => 'live']
			);
		}
	}

	function createTransaction($cartItems, $description, $custom=null) {
		$payer = new Payer();
		$payer->setPaymentMethod('paypal');

		$itemList = new ItemList();
		$totalPriceWithoutVat = 0;
		foreach($cartItems as $i) {
			$item = new Item();
			$item->setName($i['name'])
				->setCurrency('EUR')
				->setQuantity($i['amount'])
				->setSku($i['sku'])
				->setPrice($i['price']);
			$itemList->addItem($item);
			$totalPriceWithoutVat += $i['price']*$i['amount'];
		}

		$details = new Details();
		$details->setShipping(0)
			->setTax(0)
			->setSubtotal($totalPriceWithoutVat);

		$amount = new Amount();
		$amount->setCurrency('EUR')
			->setTotal($totalPriceWithoutVat)
			->setDetails($details);

		$transaction = new Transaction();
		$transaction->setAmount($amount)
			->setItemList($itemList)
			->setCustom($custom)
			->setDescription($description);
			//->setInvoiceNumber($invoiceNumber);

		$actualLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'].'/licenses.php';
		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl($actualLink.'?paypalresult=success')
			->setCancelUrl($actualLink.'?paypalresult=cancel');

		$inputFields = new InputFields();
		$inputFields->setNoShipping(1);
		$webProfile = new WebProfile();
		$webProfile
			->setName('webprofile-noshipping-'.uniqid())
			->setInputFields($inputFields)
			->setTemporary(true);
		$webProfileId = $webProfile->create($this->apiContext)->getId();

		$payment = new Payment();
		$payment->setIntent('sale')
			->setExperienceProfileId($webProfileId)
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));

		$payment->create($this->apiContext);
		$link = $payment->getApprovalLink();
		if(empty($link))
			throw new Exception('PayPal did not return an approval link.');

		return $link;
	}

	function completeTransaction($paymentId, $payerId) {
		$payment = Payment::get($paymentId, $this->apiContext);

		$execution = new PaymentExecution();
		$execution->setPayerId($payerId);

		$result = $payment->execute($execution, $this->apiContext);
		if($result->getState() != 'approved')
			throw new Exception('PayPal transaction was not approved.');

		return $result;
	}
}
