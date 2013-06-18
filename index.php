<?php
/*
Plugin Name: WooCommerce Payza v 1.0
Plugin URI: http://www.zeashop.com
Description: Payza Payment gateway for woocommerce
Version: 1.0
Author: Ehtsham Zaheer
Author URI: http://www.zeashop.com
*/
/*
* Copyright 2013 Ehtsham Zaheer
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*       http://www.apache.org/licenses/LICENSE-2.0
*
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License.
*/
add_action('plugins_loaded', 'woocommerce_sham_payza_init', 0);
function woocommerce_sham_payza_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Sham_Payza extends WC_Payment_Gateway{
    
	  /**
     * The API's response variables
     */
    private $responseArray;

    /**
     * The server address of the SendMoney API
     */
    private $server = 'api.payza.com';

    /**
     * The exact URL of the SendMoney API
     */
    private $url = '/svc/api.svc/sendmoney';

    /**
     * Your Payza user name which is your email address
     */
    private $myUserName = '';

    /**
     * Your API password that is generated from your Payza account
     */
    private $apiPassword = '';

    /**
     * The data that will be sent to the SendMoney API
     */
    public $dataToSend = '';
	
	private $payza_args = '';
	  
	  
    public function __construct(){
      $this -> id = 'Payza';
      $this -> medthod_title = 'Payza';
      $this -> has_fields = false;

      $this -> init_form_fields();
      $this -> init_settings();

      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      $this -> merchant_id = $this -> settings['merchant_id'];
      $this -> security_code = $this -> settings['security_code'];
      $this -> redirect_page_id = $this -> settings['redirect_page_id'];
      $this -> payza_environment = $this -> settings['payza_environment'];
	  if($this -> payza_environment == '0') {
      	$this -> server = 'api.payza.com';
		$this -> url = '/svc/api.svc/sendmoney';
	  } else {
	  	$this -> server = 'api.payza.com';
		$this -> url = '/svc/api.svc/sendmoney';
	  }
      $this -> msg['message'] = "";
      $this -> msg['class'] = "";
	  


      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
  
   }
    function init_form_fields(){

       $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'sham'),
                    'type' => 'checkbox',
                    'label' => __('Enable Payza Payment Module.', 'sham'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'sham'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'sham'),
                    'default' => __('Payza', 'sham')),
                'description' => array(
                    'title' => __('Description:', 'sham'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'sham'),
                    'default' => __('Pay securely by Credit or Debit card or internet banking through Payza Secure Servers.', 'sham')),
                'merchant_id' => array(
                    'title' => __('Merchant ID', 'sham'),
                    'type' => 'text',
                    'description' => __('This is email id by Payza."')),
                'security_code' => array(
                    'title' => __('IPN Security Code (Optional)', 'sham'),
                    'type' => 'text',
                    'description' =>  __('IPN Security Code by Payza', 'sham'),
                ),
				'payza_environment' => array(
                    'title' => __('Payza Environment:'),
                    'type' => 'select',
                    'options' => array(
							            '0' => '0',
	          							'1' => '1'
							          ),
                    'description' => "0 – TEST MODE is OFF
1 – TEST MODE is ON"
                ),
                'redirect_page_id' => array(
                    'title' => __('Return Page'),
                    'type' => 'select',
                    'options' => $this -> get_pages('Select Page'),
                    'description' => "URL of success page"
                )
            );
    }

       public function admin_options(){
        echo '<h3>'.__('Payza Payment Gateway', 'sham').'</h3>';
        echo '<p>'.__('Payza is most popular payment gateway for online shopping in Pakistan').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

    /**
     *  There are no payment fields for Payza, but we want to show the description if set.
     **/
    function payment_fields(){
		include_once('form.payment.php');
        if($this -> description) echo wpautop(wptexturize($this -> description));
			         global $woocommerce;
    }
   
  
	
	
    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id){
        global $woocommerce;
		
    	$order = new WC_Order( $order_id );
		
		
		$this -> setServer($this -> server);
		$this -> setUrl($this -> url);
		
		$this->myUserName = $_POST['payza_email'];
        $this->apiPassword =  $_POST['payza_password'];
		
                                  /*  $order -> payment_complete();
                                    $woocommerce -> cart -> empty_cart();
                                
                            
 return array('result' => 'success', 'redirect' => $this->get_return_url( $order ));*/
		$this -> buildPostVariables( $order -> order_total,  get_option('woocommerce_currency'), $this -> merchant_id , $_POST['payza_email'], 0,  '',  $this -> payza_environment);
		
		$this -> parseResponse($this -> send());
		
	$myresponse = $this -> getResponse();
	
	if($myresponse['RETURNCODE'] == 100){
		
                                $transauthorised = true;
                                $this -> msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                $this -> msg['class'] = 'woocommerce_message';
                                
                                    $order -> payment_complete();
                                    $woocommerce -> cart -> empty_cart();
                                
                            
 return array('result' => 'success', 'redirect' => $this->get_return_url( $order ));
	} else if($myresponse['RETURNCODE'] ==201)	
			$woocommerce->add_error(__('Missing parameter USER in the request.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==202)	
	$woocommerce->add_error(__('Missing parameter PASSWORD in the request.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==203)	
	$woocommerce->add_error(__('Missing parameter RECEIVEREMAIL in the request.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==204)	
	$woocommerce->add_error(__('Missing parameter AMOUNT in the request.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==205)	
	$woocommerce->add_error(__('Missing parameter CURRENCY in the request.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==206)	
	$woocommerce->add_error(__('Missing parameter PURCHASETYPE in the request.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==211)	
	$woocommerce->add_error(__('Invalid format for parameter USER. Value must be a valid e-mail address in the following format: username@example.com', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==212)	
	$woocommerce->add_error(__('Invalid format for parameter PASSWORD. Value must be a 16 character alpha-numeric string.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==213)	
	$woocommerce->add_error(__('Invalid format for parameter AMOUNT. Value must be numeric.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==214)	
	$woocommerce->add_error(__('Invalid value for parameter CURRENCY. Value must be a three character string representing an ISO-4217 currency code accepted by Payza.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==215)	
	$woocommerce->add_error(__('Invalid format for parameter RECEIVEREMAIL. Value must be a valid e-mail address in the following format: username@example.com', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==216)	
	$woocommerce->add_error(__('The format for parameter NOTE is invalid.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==217)	
	$woocommerce->add_error(__('Invalid value for parameter TESTMODE. Value must be either 0 or 1.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==218)	
	$woocommerce->add_error(__('Invalid value for parameter PURCHASETYPE. Value must be an integer number between 0 and 3.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==219)	
	$woocommerce->add_error(__('Invalid format for parameter SENDEREMAIL. Value must be a valid e-mail address in the following format: username@example.com', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==221)	
	$woocommerce->add_error(__('Cannot perform the request. Invalid USER and PASSWORD combination.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==222)	
	$woocommerce->add_error(__('Cannot perform the request. API Status is disabled for this account.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==223)	
	$woocommerce->add_error(__('Cannot perform the request. Action cannot be performed from this IP address.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==224)	
	$woocommerce->add_error(__('Cannot perform the request. USER account is not active.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==225)	
	$woocommerce->add_error(__('Cannot perform the request. USER account is locked.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==226)	
	$woocommerce->add_error(__('Cannot perform the request. Too many failed authentications. The API has been momentarily disabled for your account. Please try again later.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==231)	
	$woocommerce->add_error(__('Incomplete transaction. Amount to be sent must be positive and greater than 1.00.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==232)	
	$woocommerce->add_error(__('Incomplete transaction. Amount to be sent cannot be greater than the maximum amount.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==233)	
	$woocommerce->add_error(__('Incomplete transaction. You have insufficient funds in your account.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==234)	
	$woocommerce->add_error(__('Incomplete transaction. You are attempting to send more than your sending limit.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==235)	
	$woocommerce->add_error(__('Incomplete transaction. You are attempting to send more than your monthly sending limit.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==236)	
	$woocommerce->add_error(__('Incomplete transaction. You are attempting to send money to yourself.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==237)	
	$woocommerce->add_error(__('Incomplete transaction. You are attempting to send money to an account that cannot accept payments.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==238)	
	$woocommerce->add_error(__('Incomplete transaction. The recipient of the payment does not accept payments from unverified members.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==239)	
	$woocommerce->add_error(__('Invalid value for parameter NOTE. The field cannot exceed 1000 characters.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==240)	
	$woocommerce->add_error(__('Error with parameter SENDEREMAIL. The specified e-mail is not associated with your account.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==241)	
	$woocommerce->add_error(__('Error with parameter SENDEREMAIL. The specified e-mail has not been validated.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==242)	
	$woocommerce->add_error(__('Incomplete transaction. The recipient’s account is temporarily suspended and cannot receive money.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==243)	
	$woocommerce->add_error(__('Incomplete transaction. The recipient only accepts funds from members in the same country.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==244)	
	$woocommerce->add_error(__('Incomplete transaction. The recipient cannot receive funds at this time, please try again later.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==245)	
	$woocommerce->add_error(__('Incomplete transaction. The amount you are trying to send exceeds your transaction limit as an Unverified Member.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==246)	
	$woocommerce->add_error(__('Incomplete transaction. Your account must be Verified in order to transact money.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==247)	
	$woocommerce->add_error(__('Unsuccessful refund. Transaction does not belong to this account.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==248)	
	$woocommerce->add_error(__('Unsuccessful refund. Transaction does not exist in our system.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==249)	
	$woocommerce->add_error(__('Unsuccessful refund. Transaction is no longer refundable.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==250)	
	$woocommerce->add_error(__('Unsuccessful cancellation. Subscription does not belong to this account.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==251)	
	$woocommerce->add_error(__('Unsuccessful cancellation. Subscription does not exist in our system.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==252)	
	$woocommerce->add_error(__('Unsuccessful cancellation. Subscription is already canceled.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==260)	
	$woocommerce->add_error(__('Unsuccessful query. The specified CURRENCY balance is NOT open in your account.', 'woothemes'));
	else if($myresponse['RETURNCODE'] ==299)	
	$woocommerce->add_error(__('An unexpected error occurred.', 'woothemes'));
	else
	$woocommerce->add_error(__($myresponse['RETURNCODE'].' : An unexpected error occurred. Inform to admin', 'woothemes'));
		
		
		
    }

  
	
	
	

	
	
	    public function validate_fields()
    {
        global $woocommerce;
		if(!isset($_POST['payza_email'])){
		 $woocommerce->add_error(__('Payza Email is not valid.', 'woothemes'));
		}
		//$woocommerce->add_error(__($_POST['payza_email'].'Payza Email is not valid.', 'woothemes'));
}
	

   
     // get all pages
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	    /**
     * SendMoneyClient::setServer()
     * 
     * Sets the $server variable
     * 
     * @param string $newServer New web address of the server.
     */
    public function setServer($newServer)
    {
        $this->server = $newServer;
    }


    /**
     * SendMoneyClient::getServer()
     * 
     * Returns the server variable
     * 
     * @return string A variable containing the server's web address.
     */
    public function getServer()
    {
        return $this->server;
    }


    /**
     * SendMoneyClient::setUrl()
     * 
     * Sets the $url variable
     * 
     * @param string $newUrl New url address.
     */
    public function setUrl($newUrl)
    {
        $this->url = $newUrl;
    }


    /**
     * SendMoneyClient::getUrl()
     * 
     * Returns the url variable
     * 
     * @return string A variable containing a URL address.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * SendMoneyClient::buildPostVariables()
     * 
     * Builds an URL encoded post string which contains the variables to be 
     * sent to the API in the correct format. 
     * 
     * @param int $amountPaid Payment amount.
     * @param string $currency 3 letter ISO-4217 currency code.
     * @param string $receiverEmail	Recipient's email address.
     * @param string $senderEmail Your secondary email (optional).
     * @param int $purchaseType A valid purchase type code.
     * @param string $note Note that you would like to send to the recipient.
     * @param int $testMode Test mode status.
     * 
     * @return string The URL encoded post string
     */
    public function buildPostVariables($amountPaid, $currency, $receiverEmail, $senderEmail, $purchaseType, $note, $testMode)
    {
        $this->dataToSend = sprintf("USER=%s&PASSWORD=%s&AMOUNT=%s&CURRENCY=%s&RECEIVEREMAIL=%s&SENDEREMAIL=%s&PURCHASETYPE=%s&NOTE=%s&TESTMODE=%s",
            urlencode($this->myUserName), urlencode($this->apiPassword), urlencode((string)$amountPaid), urlencode($currency), urlencode($receiverEmail), urlencode($senderEmail), 
			urlencode((string)$purchaseType), urlencode((string)$note), urlencode((string )$testMode));
        return $this->dataToSend;
    }


    /**
     * SendMoneyClient::send()
     * 
     * Sends the URL encoded post string to the SendMoney API 
     * using cURL and retrieves the response.
     * 
     * @return string The response from the SendMoney API.
     */
    public function send()
    {
        $response = '';
		 $woocommerce->add_error(__('Send not working.', 'woothemes'));
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->getServer() . $this->getUrl());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->dataToSend);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }


    /**
     * SendMoneyClient::parseResponse()
     * 
     * Parses the encoded response from the SendMoney API
     * into an associative array.
     * 
     * @param string $input The string to be parsed by the function.
     */
    public function parseResponse($input)
    {
        parse_str($input, $this->responseArray);
    }


    /**
     * SendMoneyClient::getResponse()
     * 
     * Returns the responseArray 
     * 
     * @return string An array containing the response variables.
     */
    public function getResponse()
    {
        return $this->responseArray;
    }


    /**
     * SendMoneyClient::__destruct()
     * 
     * Destructor of the SendMoneyClient object
     */
    public function __destruct()
    {
        unset($this->responseArray);
    }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_sham_Payza_gateway($methods) {
        $methods[] = 'WC_Sham_Payza';
        return $methods;
    }


    add_filter('woocommerce_payment_gateways', 'woocommerce_add_sham_Payza_gateway' );
}
