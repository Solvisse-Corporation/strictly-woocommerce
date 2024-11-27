<?php
/*
Plugin Name: Pay With Zero Gateway
Description: A Pay With Zero payment gateway for WooCommerce.
Version: 1.0.0
Author: Ismiberto Maicel
Author URI: solvisse.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: paywithzero-gateway
Domain Path: /languages
*/

add_action('plugins_loaded', 'woocommerce_plugin', 0);
function woocommerce_plugin()
{
    if (!class_exists('WC_Payment_Gateway')) return; 
    include(plugin_dir_path(__FILE__) . 'class-gateway.php');
}

add_filter('woocommerce_payment_gateways', 'add_paywithzero_gateway');
function add_paywithzero_gateway($gateways) 
{
  $gateways[] = 'WC_Paywithzero_Gateway';
  return $gateways;
}

function declare_cart_checkout_blocks_compatibility() 
{
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) 
	{
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

function paywithzero_register_order_approval_payment_method_type()
{
    if (!class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) 
	{
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry)
		{
            $payment_method_registry->register( new WC_Paywithzero_Gateway_Blocks);
        }
    );
}
add_action('woocommerce_blocks_loaded', 'paywithzero_register_order_approval_payment_method_type');
?>