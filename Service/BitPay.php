<?php

namespace Calitarus\BitPayBundle\Service;

use Monolog\Logger;

class BitPay {

	private $logger;

	// configuration

	// Please look carefully through these options and adjust according to your installation.  
	// Alternatively, most of these options can be dynamically set

	private $bpOptions = array(
	// REQUIRED Api key you created at bitpay.com
	// example: $apiKey = 'L21K5IIUG3IN2J3';
		$apiKey = '',

	// whether to verify POS data by hashing above api key.  If set to false, you should
	// have some way of verifying that callback data comes from bitpay.com
	// note: this option can only be changed here.  It cannot be set dynamically. 
		$verifyPos = true,

	// email where invoice update notifications should be sent
		$notificationEmail = '',

	// url where bit-pay server should send update notifications.  See API doc for more details.
	# example: $bpNotificationUrl = 'http://www.example.com/callback.php';
		$notificationURL = '',

	// url where the customer should be directed to after paying for the order
	# example: $redirectURL = 'http://www.example.com/confirmation.php';
		$redirectURL = '',

	// This is the currency used for the price setting.  A list of other pricing
	// currencies supported is found at bitpay.com
		$currency = 'BTC',

	// Indicates whether anything is to be shipped with
	// the order (if false, the buyer will be informed that nothing is
	// to be shipped)
		$physical = 'false',

	// If set to false, then notificaitions are only
	// sent when an invoice is confirmed (according the the
	// transactionSpeed setting). If set to true, then a notification
	// will be sent on every status change
		$fullNotifications = 'true',

	// transaction speed: low/medium/high.   See API docs for more details.
		$transactionSpeed = 'low'
	);

	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}



	// $orderId: Used to display an orderID to the buyer. In the account summary view, this value is used to 
	// identify a ledger entry if present.
	//
	// $price: by default, $price is expressed in the currency you set in bp_options.php.  The currency can be 
	// changed in $options.
	//
	// $posData: this field is included in status updates or requests to get an invoice.  It is intended to be used by
	// the merchant to uniquely identify an order associated with an invoice in their system.  Aside from that, Bit-Pay does
	// not use the data in this field.  The data in this field can be anything that is meaningful to the merchant.
	//
	// $options keys can include any of: 
	// ('itemDesc', 'itemCode', 'notificationEmail', 'notificationURL', 'redirectURL', 'apiKey'
	//		'currency', 'physical', 'fullNotifications', 'transactionSpeed', 'buyerName', 
	//		'buyerAddress1', 'buyerAddress2', 'buyerCity', 'buyerState', 'buyerZip', 'buyerEmail', 'buyerPhone')
	// If a given option is not provided here, the value of that option will default to what is found above
	public function bpCreateInvoice($orderId, $price, $posData, $options = array()) {	
		$options = array_merge($this->bpOptions, $options); // $options override any default options
		
		$pos = array('posData' => $posData);
		if ($bpOptions['verifyPos'])
			$pos['hash'] = bpHash(serialize($posData), $options['apiKey']);
		$options['posData'] = json_encode($pos);
		
		$options['orderID'] = $orderId;
		$options['price'] = $price;
		
		$postOptions = array('orderID', 'itemDesc', 'itemCode', 'notificationEmail', 'notificationURL', 'redirectURL', 
			'posData', 'price', 'currency', 'physical', 'fullNotifications', 'transactionSpeed', 'buyerName', 
			'buyerAddress1', 'buyerAddress2', 'buyerCity', 'buyerState', 'buyerZip', 'buyerEmail', 'buyerPhone');
		foreach($postOptions as $o)
			if (array_key_exists($o, $options))
				$post[$o] = $options[$o];
		$post = json_encode($post);
		
		$response = bpCurl('https://bitpay.com/api/invoice/', $options['apiKey'], $post);

		return $response;
	}

	// Call from your notification handler to convert $_POST data to an object containing invoice data
	public function bpVerifyNotification($apiKey = false) {
		if (!$apiKey) $apiKey = $this->options['apiKey'];
		
		$post = file_get_contents("php://input");
		if (!$post)
			return 'No post data';
			
		$json = json_decode($post, true);
		
		if (is_string($json))
			return $json; // error

		if (!array_key_exists('posData', $json)) 
			return 'no posData';
			
		$posData = json_decode($json['posData'], true);
		if ($this->verifyPos and $posData['hash'] != bpHash(serialize($posData['posData']), $apiKey))
			return 'authentication failed (bad hash)';
		$json['posData'] = $posData['posData'];
			
		return $json;
	}

	// $options can include ('apiKey')
	public function bpGetInvoice($invoiceId, $apiKey=false) {
		if (!$apiKey) $apiKey = $this->options['apiKey'];

		$response = bpCurl('https://bitpay.com/api/invoice/'.$invoiceId, $apiKey);
		if (is_string($response))
			return $response; // error
		$response['posData'] = json_decode($response['posData'], true);
		$response['posData'] = $response['posData']['posData'];

		return $response;	
	}

	// Generates a keyed hash.
	private function bpHash($data, $key) {
		$hmac = base64_encode(hash_hmac('sha256', $data, $key, TRUE));
		return strtr($hmac, array('+' => '-', '/' => '_', '=' => ''));
	}


	function bpCurl($url, $apiKey, $post = false) {
		$curl = curl_init($url);
		$length = 0;
		if ($post)
		{	
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			$length = strlen($post);
		}
		
		$uname = base64_encode($apiKey);
		$header = array(
			'Content-Type: application/json',
			"Content-Length: $length",
			"Authorization: Basic $uname",
			);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			
		$responseString = curl_exec($curl);
		
		if($responseString == false) {
			$response = array('error' => curl_error($curl));
		} else {
			$response = json_decode($responseString, true);
			if (!$response)
				$response = array('error' => 'invalid json: '.$responseString);
		}
		curl_close($curl);
		return $response;
	}

}
