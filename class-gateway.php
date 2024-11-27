<?php
class WC_Paywithzero_Gateway extends WC_Payment_Gateway {
  
  public function __construct() 
  {
    $this->id                 = 'paywithzero_gateway';
    $this->method_title       = __('Pay With Zero Gateway', 'paywithzero-gateway');
    $this->method_description = __('Accept payments through ay With Zero Gateway', 'paywithzero-gateway'); 
	$this->title = __('Accept payments through Pay With Zero Gateway', 'paywithzero-gateway'); 
    $this->description = __('Accept payments through Pay With Zero Gateway', 'paywithzero-gateway'); 
    
    $this->init_form_fields();
    $this->init_settings();
	
	$this->title = $this->get_option('title');
	$this->description = $this->get_option('description');
	$this->user = $this->get_option('user');
	$this->password = $this->get_option('password');
	$this->token = $this->get_option('token');
	$this->tokenization = $this->get_option('tokenization');
	$this->rate = $this->get_option('rate');
    
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	add_action('woocommerce_cart_calculate_fees', array($this, 'cart_calculate_fees'));
  } 
  
  public function payment_fields() 
  {
	 $this->card_form();
  }
  
  public function card_form()
  {
	$rate = $this->rate * 0.01;
	$surcharge = "$" . round(WC()->cart->get_subtotal() * $rate, 2);
  ?>
        <div style="display: flex;">
			<span style="display: flex;"><?php echo $this->description; ?></span>
			<ul style="display: flex; list-style: none; padding: 0px; margin: 0px 0px 0px 20px;">
				<li><img style="width:34px;height: 24px;" src="<?php echo WC()->plugin_url(); ?>/assets/images/icons/credit-cards/visa.svg" alt="visa"></li>
				<li><img style="width:34px;height: 24px;" src="<?php echo WC()->plugin_url(); ?>/assets/images/icons/credit-cards/discover.svg" alt="discover"></li>
				<li><img style="width:34px;height: 24px;" src="<?php echo WC()->plugin_url(); ?>/assets/images/icons/credit-cards/mastercard.svg" alt="mastercard"></li>
				<li><img style="width:34px;height: 24px;" src="<?php echo WC()->plugin_url(); ?>/assets/images/icons/credit-cards/amex.svg" alt="amex"></li>
			</ul>
		</div>
		<div style="display: flex; list-style: none;">
			Please be aware that there will be a credit card fee of <?php echo $surcharge; ?> added to this payment. 
			This credit card fee is not greater than our total cost of accepting credit cards. 
			There is no fee for Debit Card Payments.
		</div>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<p class="form-row form-row-wide">
				<?php echo esc_html__( 'Card Number', 'woocommerce' ); ?>
				<label id="demoCcnumber"></label>
			</p>
			<p class="form-row form-row-first">
				<?php echo esc_html__( 'Card Expiration', 'woocommerce' ); ?>
				<label id="demoCcexp"></label>
			</p>
			<p class="form-row form-row-last">
			    <?php echo esc_html__( 'CVC', 'woocommerce' ); ?>
				<label id="demoCvv"></label>
			</p>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
			<input type="hidden" name="paymenttoken" id="paymenttoken" value="test" />
			<input type="hidden" name="cardnumber" id="cardnumber" value="test" />
		</fieldset>
		
 <script type="text/javascript">
    var strictly = {};
	strictly['woocommerce'] = {};
	strictly['woocommerce']['response'] = 0;
	
	jQuery(document).ready(function($) 
	{		
		CollectJS.configure(
		{
			"fields": 
			{
				"ccnumber": 
				{
					"selector": "#demoCcnumber",
					"title": "Card Number",
					"placeholder": "0000 0000 0000 0000"
				},
				"ccexp": 
				{
					"selector": "#demoCcexp",
					"title": "Card Expiration",
					"placeholder": "00 / 0000"
				},
				"cvv": 
				{
					"display": "show",
					"selector": "#demoCvv",
					"title": "CVV Code",
					"placeholder": "000"
				}
			},
			"currency": "USD",
			"variant": "inline",
			"callback": function (response) 
			{
				strictly.woocommerce.response = response;
				$('form.checkout').trigger("submit");
			},
			fieldsAvailableCallback: function ()
			{
			},
			timeoutCallback: function ()
			{
				strictly.woocommerce.response = 0;
			},
			timeoutDuration: 5000
		});
			
	    $(document.body).on("updated_checkout", function()
		{
		});
		
		$(document.body).on("update_checkout", function()
		{
		});
		
		$(document.body).on("checkout_error", function()
		{
		});
		
		$(document.body).on("init_checkout", function()
		{		
			$('form.checkout' ).on('checkout_place_order', function(form) 
			{
			   if(strictly.woocommerce.response == 0) CollectJS.startPaymentRequest();			   
			   if(strictly.woocommerce.response)
			   {
				  $("#paymenttoken").attr("value", strictly.woocommerce.response.token);
				  $("#cardnumber").attr("value", strictly.woocommerce.response.card.number);
			   }
			   return !(strictly.woocommerce.response == 0);
			});
		});		
	});		
 </script>
  <?php
  }
  
  function cart_calculate_fees($cart)
  { 
	if(is_checkout()) 
	{
	    $rate = 0.03;
		if($this->rate > 0) $rate = $this->rate * 0.01;
		$surcharge = round($cart->get_subtotal() * $rate, 2) ;
		$cart->add_fee("Credit Card Fee", $surcharge, true);
	}
  }
  
  public function init_form_fields() 
  {
    $this->form_fields = array(
      'enabled' => array(
        'title' => __('Enable/Disable', 'paywithzero-gateway'),
        'type'  => 'checkbox',
        'label' => __('Enable Pay With Zero Payment Gateway', 'paywithzero-gateway'),
        'default' => 'yes'
      ),
	  'title' => array(
	    'title' => __( 'Title', 'paywithzero-gateway' ),
		'type' => 'safe_text',
		'description' => __( 'Payment method description that the customer will see on your checkout.', 'paywithzero-gateway' ),
		'default' => __( 'Credit or Debit', 'paywithzero-gateway' ),
		'desc_tip' => true
	  ),
	  'description' => array(
		'title' => __( 'Description', 'paywithzero-gateway' ),
		'type' => 'textarea',
		'description' => __( 'Payment method description that the customer will see on your website.', 'paywithzero-gateway' ),
		'default' => __( 'Pay with cash upon delivery.', 'paywithzero-gateway' ),
		'desc_tip' => true
	 ),
	 'user' => array(
	    'title' => __( 'User', 'paywithzero-gateway' ),
		'type' => 'safe_text',
		'description' => __( 'Payment user', 'paywithzero-gateway' ),
		'default' => __( '', 'paywithzero-gateway' ),
		'desc_tip' => true
	  ),
	  'password' => array(
	    'title' => __( 'Password', 'paywithzero-gateway' ),
		'type' => 'safe_text',
		'description' => __( 'Payment password', 'paywithzero-gateway' ),
		'default' => __( '', 'paywithzero-gateway' ),
		'desc_tip' => true
	  ),
	  'token' => array(
	    'title' => __( 'Api key', 'paywithzero-gateway' ),
		'type' => 'safe_text',
		'description' => __( 'Payment api key', 'paywithzero-gateway' ),
		'default' => __( '', 'paywithzero-gateway' ),
		'desc_tip' => true
	  ),
	  'tokenization' => array(
	    'title' => __( 'Tokenization key', 'paywithzero-gateway' ),
		'type' => 'safe_text',
		'description' => __( 'Payment tokenization key', 'paywithzero-gateway' ),
		'default' => __( '', 'paywithzero-gateway' ),
		'desc_tip' => true
	  ),
	  'rate' => array(
	    'title' => __( '% Rate', 'paywithzero-gateway' ),
		'type' => 'safe_text',
		'description' => __( '% Rate', 'paywithzero-gateway' ),
		'default' => __( '3', 'paywithzero-gateway' ),
		'desc_tip' => true
	  )
    );
  }
  
  public function process_payment($order_id) 
  {
	$order = wc_get_order($order_id);
	$raw = array();
	$amount = round($order->get_total() * 100, 2);
	$email = $order->get_billing_email();
	$phone = $order->get_billing_phone();
	$address = $order->get_billing_address_1();
	$city = $order->get_billing_city();
	$country = $order->get_billing_country();
	$state = $order->get_billing_state();
	$zipCode = $order->get_billing_postcode();
	$poNumber = $order->get_transaction_id();
	$name = $order->get_formatted_billing_full_name();
	$paymentToken = $_POST["paymenttoken"];
	$number = $_POST["cardnumber"];
	
	$raw["amount"] = $amount;
	$raw["contact"] = array("email" => $email, "phone" => $phone);
	$raw["billingAddress"] = array("address" => $address, "city" => $city, "country" => $country, "state" => $state, "zipCode" => $zipCode);
	$raw["shippingAddress"] = new stdClass;
	$raw["order"] = array("amount" => $amount, "poNumber" => $poNumber, "shipping" => 0, "tax" => 0, "discount" => 0);
	$raw["capture"] = true;
	$raw["card"] = array("name" => $name, "paymentToken" => $paymentToken, "number" => $number);
		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.paywithzero.net/v1/public/payment/charge");    
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
	curl_setopt($ch, CURLOPT_POST, true);
	//curl_setopt($ch, CURLOPT_PROXY, 'localhost:54905');                                                                   
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($raw));                                                                  
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

	if($returnCode == 201)
	{
		$order->payment_complete();
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url($order)
		);
	}
	else 
	return array(
	    'result' => 'error'
	);
  }  
}
?>