BitPay Symfony2 Bundle
======================

This is based on [the official client](https://github.com/bitpay/php-client) and provides the BitPay API as a Symfony2 service.

Requires PHP with the *curl extension* installed.


Installation
------------
This bundle is composer aware, so the below should work:

### composer.json
    "repositories": [
        { "type": "vcs", "url": "https://github.com/tvogt/bitpaybundle" }
    ],
    "require": {
    	...
        "tvogt/bitpaybundle": "master"


If it doesn't and you figure out what's wrong, let me know and I'll update this. :-)



Usage
-----
The bundle provides two forms you can use:

	// when you create a form at https://bitpay.com/create-donate, there is a hidden field at the end of the form named "data"
	// copy its value field into this variable:
	private $bitpay_donation = ""; 


	// when you create a button at https://bitpay.com/catalog-item-list, it has a hidden field named "data" containing a
	// unique item ID that you need to put here:
	private $bitpay_items = array(
		'item A' => "",
		'item B' => ""
	);

	/**
     * @Route("/payment")
     * @Template
     */
	public function paymentAction() {
		$bitpay_donation = $this->createForm(new DonationType(
			$this->getUser()->getEmail(), // or wherever you get the e-mail of your user from
			$this->bitpay_donation
		));

		$bitpay_items = $this->createForm(new ItemType($this->bitpay_items['item A']));

		return array(
			'bitpaydonation' => $bitpay_donation->createView(),
			'bitpayitems' => $bitpay_items->createView()
		);
	}


Then when a customer orders, you get a callback from the BitPay server, and handle it like this:

	/**
	  * @Route("/bitpay")
	  */
	public function bitpayAction(Request $request) {
		$data = $this->get('bitpay')->bpVerifyNotification($request, $this->bitpay_apikey);

		... do whatever you want with the result ...
		
	}
