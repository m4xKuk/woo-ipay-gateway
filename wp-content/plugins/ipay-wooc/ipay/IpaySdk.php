<?php

namespace payment\ipay;

class IpaySdk {
	
	private const URL = 'https://api.ipay.ua';

	private const URL_TEST = 'https://sandbox-checkout.ipay.ua/api302';

	private $mch_id;

	private $sign_key;

	private $is_test;

	private $salt;

	public $data;

	public function __construct($setting_pay) {
		$this->mch_id = $setting_pay['mch_id'];
		$this->sign_key = $setting_pay['sign_key'];
		$this->is_test = $setting_pay['testmode'];

		$this->data['callback_url'] = $setting_pay['callback_url'];

		$this->salt = $this->getSalt();
		$this->sign = $this->getHashHmac($this->salt);
		
	}

	public function paymentCreate() {
		$xml = $this->getXmlPost();
		// echo $xml; exit;
		return $this->sendXmlPost($xml);
	}

	public function response() {
		$response = $this->paymentCreate();
		return simplexml_load_string($response);
	}

	public function responseStatus($pid) {
		$xml = $this->getStatusXml($pid);
		$response = $this->sendXmlPost($xml);
		return (array)simplexml_load_string($response);
	}

	public function sendXmlPost($xml) {

		$url = $this->is_test == 'yes' ? self::URL_TEST : self::URL;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml'));

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,  ['data' => $xml]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	private function getSalt() {
		return sha1(microtime(true));
	}

	private function getHashHmac($salt) {
		return hash_hmac('sha512', $salt, $this->sign_key);
	}

	private function getXmlPost() {

		$amount = $this->data['amount'];
		$form_data = $this->data['form_data'];
		$title_form = $form_data['pey_title'];
		$info = $this->getInfoJson();
$post =  <<<EOD
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<payment>
    <auth>
        <mch_id>$this->mch_id</mch_id>
        <salt>$this->salt</salt>
        <sign>$this->sign</sign>
    </auth>
    <urls>
        <good>%3\$s</good>
        <bad>%4\$s</bad>
    </urls>
    <transactions>
        <transaction>
        	<mch_id>$this->mch_id</mch_id>
			<type>11</type>
            <amount>$amount</amount>
            <currency>UAH</currency>
            <desc>$title_form</desc>
            <info>$info</info>
        </transaction>
    </transactions>
    <lifetime>24</lifetime>
    <trademark>{"ru":"%2\$s","ua":"%2\$s","en":"%2\$s"}</trademark>
    <lang>ua</lang>
</payment>
EOD;
	return sprintf($post, 
		$form_data['name'], 
		$this->data['info']['service_data']['Receiver'],
		$this->data['callback_url']['success_page'],
		$this->data['callback_url']['error_page'],
		);
	}

	private function setAmount($amount) {
		$this->data['amount'] = (float)$amount * 100;
	}

	public function setInfo($info_payment, $info_order) {

		$this->setAmount($info_order['amount']);

		$this->data['info'] = [
			'OrderID' => $info_order['OrderID'],
			'PersonID' => $info_order['PersonID'],
			'service_data' => [
				'Receiver' => $info_payment['service_data_receiver'],
                'ZKPO' => $info_payment['service_data_ZKPO'],
                'BankReciever' => $info_payment['service_data_bank'],
                'AccReceiver' => $info_payment['service_data_acc'],
                'PayText' => $info_payment['service_data_text'],
			],
		];

		$this->data['form_data'] = [
			'name' => $info_order['name'] ?? '',
			'last_name' => $info_order['last_name'] ?? '',
			'phone' => $info_order['phone'] ?? '',
			'email' => $info_order['email'] ?? '',
			'pey_title' => $info_order['pey_title'] ?? ''
		];
	}

	public function getInfoJson() {
		return json_encode($this->data['info'], JSON_UNESCAPED_UNICODE );
	}

	public function getStatusXml($pid) {
$post =  <<<EOD
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<payment>
    <auth>
        <mch_id>$this->mch_id</mch_id>
        <salt>$this->salt</salt>
        <sign>$this->sign</sign>
    </auth>
    <action>status</action>
    <pid>$pid</pid>
</payment>
EOD;
	return $post;
	}
}