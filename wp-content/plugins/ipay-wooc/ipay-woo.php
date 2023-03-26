<?php
/*
 * Plugin Name: WooCommerce iPay Gateway
 * Plugin URI: /
 * Description: https://checkout.ipay.ua/doc
 * Author: Maks
 * Author URI: http://
 * Version: 1.0.0
 */
define('PATH_BY_PLUGIN', plugin_dir_path(__FILE__));
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'ipay_add_gateway_class' );
function ipay_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Ipay_Gateway'; // your class name is here
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'ipay_init_gateway_class' );
function ipay_init_gateway_class() {
    require PATH_BY_PLUGIN.'inc/wc-ipay-gateway.php';
}
