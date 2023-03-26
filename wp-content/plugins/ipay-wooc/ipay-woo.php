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

add_action( 'plugins_loaded', 'ipay_init_gateway_class' );
function ipay_init_gateway_class() {
    require PATH_BY_PLUGIN.'inc/wc-ipay-gateway.php';
}

//--> Крон костыль для проверки статуса заказа
function ipay_plugin_activate() {

    if( !wp_next_scheduled('hook_check_pay_order') ){
        wp_schedule_event( time(), '50_seconds', 'hook_check_pay_order');
    }
}
register_activation_hook( __FILE__, 'ipay_plugin_activate' );

// add custom interval
add_filter( 'cron_schedules', 'example_add_cron_interval' );
function example_add_cron_interval( $schedules ) { 
    $schedules['50_seconds'] = array(
        'interval' => 3600,
        'display'  => esc_html__( 'Every Five Seconds' ), );
    return $schedules;
}
// CRON
function check_pay_order() {
    require_once PATH_BY_PLUGIN.'inc/wc-ipay-cron-status.php';
}
add_action('hook_check_pay_order', 'check_pay_order');


function ipay_plugin_deactivate() {
    wp_clear_scheduled_hook('hook_check_pay_order');
}
register_deactivation_hook( __FILE__, 'ipay_plugin_deactivate');
//<--


add_filter( 'manage_edit-shop_order_columns', 'register_is_first_order_column', 10, 1 );
function register_is_first_order_column( $columns ) {
    $columns['is_first_order'] = apply_filters( 'order_table_title_ipay', 'Ipay: id платежа' );
    return $columns;
}
 
add_action( 'manage_shop_order_posts_custom_column', 'display_is_first_order_column', 10, 1 );
function display_is_first_order_column( $column ) {
    global $post;
 
    if ( 'is_first_order' === $column ) {
        $is_first_order = get_post_meta( $post->ID, 'pid', true );
        
        if ( false !== $is_first_order && strlen( $is_first_order ) > 0 ) {
            echo "✔️ipay: $is_first_order";
        }
    }
}