<?php
require_once plugin_dir_path(__DIR__).'ipay/IpaySdk.php';

$args = array(
    'status' => array('on-hold'),
    'meta_key' => 'pid'
);

$orders = wc_get_orders( $args );

if(empty($orders)) wp_die();

$setting = get_option('woocommerce_ipay_gate_maks_settings');
$time = $setting['cron_time'];

foreach($orders as $order) {
	$ipay = new payment\ipay\IpaySdk($setting);
	$result = $ipay->responseStatus($order->get_meta('pid'));

	if($order->get_total() * 100 != $result['invoice']) {
		$note = __("Сумма не соответствует");
		$order->add_order_note( $note );
		continue;
	}

	switch($result['status']){
		case 1: 
			$order->add_order_note(__('Платеж зареестрирован'));
			break;
		case 4:
			$order->update_status('failed');
			$order->add_order_note(__('Платеж неуспешный'));
			break;
		case 5:
			$order->update_status('completed');
			$order->add_order_note(__('Платеж успешный'));
			break;
		case 9:
			$order->update_status('failed');
			$order->add_order_note(__('Платеж отменен'));
			break;
	}
}