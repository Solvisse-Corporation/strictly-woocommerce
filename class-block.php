<?php

/**
 * Copyright (C) 2024 Solvisse Corporation
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Paywithzero_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'paywithzero_gateway';

    public function initialize() 
	{
        $this->settings = get_option( 'woocommerce_paywithzero_gateway_settings', [] );
	    if(array_key_exists('paywithzero_gateway', WC()->payment_gateways()->get_available_payment_gateways()))
		{
		   $this->gateway = WC()->payment_gateways()->get_available_payment_gateways()['paywithzero_gateway'];
		}
		else $this->gateway = new WC_Paywithzero_Gateway();
    }

    public function is_active() 
	{
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() 
	{
		wp_enqueue_script('paywithzero','https://gateway.paywithzero.net/v1/gateway/ZeroGateway.js');
        wp_register_script(
            'paywithzero_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout-pay-gateway.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
				'paywithzero'
            ],
            null,
            true
        );
        if( function_exists('wp_set_script_translations') )
		{            
            wp_set_script_translations('paywithzero_gateway-blocks-integration');            
        }
        return ['paywithzero_gateway-blocks-integration'];
    }

	private function getRate()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.paywithzero.net/v1/public/payment/rate?amount=" . $amount . "&binCard=" . $binCard . "&tokenId=" . $paymentToken);    
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");  
		curl_setopt($ch, CURLOPT_POST, false);
		//curl_setopt($ch, CURLOPT_PROXY, 'localhost:54905');                                                                   
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
		curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->password);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
			'Content-Type: application/json',
			'key-hash: ' . $this->token)                                                           
		); 
																									   
		$result = curl_exec($ch);
		$errors = curl_error($ch);                                                                                                            
		$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$surcharge = 0;
		if($returnCode == 200)
		{
			$result = json_decode($result, true);
			$surcharge = $result["data"]["surcharge"] * 0.01;
		}
		
		return $surcharge;
	}
    public function get_payment_method_data() 
	{
		$surcharge = 0;
	    if(is_checkout() && WC()->cart)
		{
		   $rate = 0.03;
		   $rate = $this->gateway->rate * 0.01;
		   $surcharge = "$" . round(WC()->cart->get_subtotal() * $rate, 2) ;
		   $surcharge = "Please be aware that there will be a credit card fee of $surcharge added to this payment. This credit card fee is not greater than our total cost of accepting credit cards. There is no fee for Debit Card Payments.";	
		}
		
        return 
		[
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
			'surcharge' => $surcharge
        ];
    }
}

add_filter( 'wp_script_attributes', 'paywithzero_filter_script_loader_tag', 10, 1 );
function paywithzero_filter_script_loader_tag(array $attr)
{
	if($attr['id'] === "paywithzero-js") 
	{
		if(array_key_exists('paywithzero_gateway', WC()->payment_gateways()->get_available_payment_gateways()))
		$attr['data-tokenization-key'] = WC()->payment_gateways()->get_available_payment_gateways()['paywithzero_gateway']->tokenization;
	}
	return $attr;	
}
?>
