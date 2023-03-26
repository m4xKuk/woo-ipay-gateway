<?php

namespace payment\ipay;

require_once (__DIR__.'/IpaySdk.php');
require_once (__DIR__.'/LogToFile.php');

$data['name']  = clean($_POST['fio'] ?? '');
$data['phone']  = clean($_POST['bill_phone'] ?? '');
$data['policy'] = clean($_POST['description'] ?? '');
$data['email']  = clean($_POST['bill_mail'] ?? '');

$amount = clean($_POST['bill_amount'] ?? 10);

$ipay = new IpaySdk($amount);

$ipay->setInfo($data);

$payment = $ipay->response();

$log = new LogToFile($payment);
$log->savePayInfo();

if($payment->status == 1) {
	header('Location: '.$payment->url);
	exit();
}

function clean($value = "") {
	$value = trim($value);
	$value = stripslashes($value);
	$value = strip_tags($value);
	$value = htmlspecialchars($value);

	return $value;
}