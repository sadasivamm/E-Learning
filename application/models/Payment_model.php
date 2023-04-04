<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once(APPPATH."libraries/razorpay-php/Razorpay.php");
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class Payment_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    // VALIDATE PAYPAL PAYMENT AFTER PAYING
    function configure_course_payment(){
        $items = array();
        $total_payable_amount = 0;

        //item detail
        foreach ($this->session->userdata('cart_items') as $key => $cart_item):
            $course_details = $this->crud_model->get_course_by_id($cart_item)->row_array();
            $item_details['id'] = $cart_item;
            $item_details['title'] = $course_details['title'];
            $item_details['thumbnail'] = $this->crud_model->get_course_thumbnail_url($course_details['id']);
            $item_details['creator_id'] = $course_details['creator'];
            $item_details['discount_flag'] = $course_details['discount_flag'];
            $item_details['discounted_price'] = $course_details['discounted_price'];
            $item_details['price'] = $course_details['price'];

            $item_details['actual_price'] = ($course_details['discount_flag'] == 1) ? $course_details['discounted_price'] : $course_details['price'];
            $item_details['sub_items'] = array();

            $items[$key] = $item_details;
            $total_payable_amount += $item_details['actual_price'];
        endforeach;
        //ended item detail

        //if applied coupon
        $coupon_code = $this->session->userdata('applied_coupon');
        if($coupon_code){
            $total_payable_amount = $this->crud_model->get_discounted_price_after_applying_coupon($coupon_code);
        }
        //included tax
        $total_payable_amount = round($total_payable_amount + ($total_payable_amount/100) * get_settings('course_selling_tax'), 2);

        //common structure for all payment gateways and all type of payment
        $data['total_payable_amount'] = $total_payable_amount;
        $data['items'] = $items;
        $data['is_instructor_payout_user_id'] = false;
        $data['payment_title'] = get_phrase('pay_for_purchasing_course');
        $data['success_url'] = site_url('payment/success_course_payment');
        $data['cancel_url'] = site_url('payment');
        $data['back_url'] = site_url('home/shopping_cart');

        $this->session->set_userdata('payment_details', $data);
    }

    function configure_instructor_payment($is_instructor_payout_user_id = false){
        $payout_request = $this->db->where('user_id', $is_instructor_payout_user_id)->where('status', 0)->get('payout')->row_array();
        $amount = $payout_request['amount'];
        $items = array();
        $total_payable_amount = 0;
        $instructor_details = $this->user_model->get_all_user($is_instructor_payout_user_id)->row_array();
            
        //item detail
        $item_details['payout_id'] = $payout_request['id'];
        $item_details['title'] = get_phrase('pay_to').' '.$instructor_details['first_name'].' '.$instructor_details['last_name'];
        $item_details['thumbnail'] = '';
        $item_details['creator_id'] = '';
        $item_details['discount_flag'] = 0;
        $item_details['discounted_price'] = $amount;
        $item_details['price'] = $amount;
        $item_details['actual_price'] = $amount;
        $item_details['sub_items'] = array();
        $items[0] = $item_details;
        //ended item details

        //common structure for all payment gateways and all type of payment
        $data['total_payable_amount'] = $amount;
        $data['items'] = $items;
        $data['is_instructor_payout_user_id'] = $is_instructor_payout_user_id;
        $data['payment_title'] = get_phrase('pay_for_instructor_payout');
        $data['success_url'] = site_url('payment/success_instructor_payment');
        $data['cancel_url'] = site_url('payment');
        $data['back_url'] = site_url('admin/instructor_payout');

        $this->session->set_userdata('payment_details', $data);

    }



























    public function check_paypal_payment($identifier = "") {
      //start common code of all payment gateway
      $payment_details = $this->session->userdata('payment_details');
      $payment_gateway = $this->db->get_where('payment_gateways', ['identifier' => $identifier])->row_array();

      if($payment_details['is_instructor_payout_user_id'] > 0){
        $instructor_details = $this->user_model->get_all_user($payment_details['is_instructor_payout_user_id'])->row_array();
        $keys = json_decode($instructor_details['payment_keys'], true);
        $keys = $keys[$payment_gateway['identifier']];
      }else{
        $keys = json_decode($payment_gateway['keys'], true);
      }
      $test_mode = $payment_gateway['enabled_test_mode'];
      //ended common code of all payment gateway


      $paymentID = $_GET['payment_id'];
      $paymentToken = $_GET['payment_token'];
      $payerID = $_GET['payer_id'];
      if($test_mode == 1){
        $paypalURL       = 'https://api.sandbox.paypal.com/v1/';
        $paypalClientID = $keys['sandbox_client_id'];
        $paypalSecret = $keys['sandbox_secret_key'];
      } else {
        $paypalURL       = 'https://api.paypal.com/v1/';
        $paypalClientID = $keys['production_client_id'];
        $paypalSecret = $keys['production_secret_key'];
      }

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $paypalURL.'oauth2/token');
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERPWD, $paypalClientID.":".$paypalSecret);
      curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
      $response = curl_exec($ch);
      curl_close($ch);

      if(empty($response)){
          return false;
      }else{
          $jsonData = json_decode($response);
          $curl = curl_init($paypalURL.'payments/payment/'.$paymentID);
          curl_setopt($curl, CURLOPT_POST, false);
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($curl, CURLOPT_HEADER, false);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_HTTPHEADER, array(
              'Authorization: Bearer ' . $jsonData->access_token,
              'Accept: application/json',
              'Content-Type: application/xml'
          ));
          $response = curl_exec($curl);
          curl_close($curl);

          // Transaction data
          $result = json_decode($response);

          // CHECK IF THE PAYMENT STATE IS APPROVED OR NOT
          if($result && $result->state == 'approved'){
              return true;
          }else{
              return false;
          }
      }
    }

    public function check_stripe_payment($identifier = "") {
      //start common code of all payment gateway
      $payment_details = $this->session->userdata('payment_details');
      $payment_gateway = $this->db->get_where('payment_gateways', ['identifier' => $identifier])->row_array();

      if($payment_details['is_instructor_payout_user_id'] > 0){
        $instructor_details = $this->user_model->get_all_user($payment_details['is_instructor_payout_user_id'])->row_array();
        $keys = json_decode($instructor_details['payment_keys'], true);
        $keys = $keys[$payment_gateway['identifier']];
      }else{
        $keys = json_decode($payment_gateway['keys'], true);
      }
      $test_mode = $payment_gateway['enabled_test_mode'];
      //ended common code of all payment gateway

      if($test_mode == 1){
          $public_key = $keys['public_key'];
          $secret_key = $keys['secret_key'];
      } else {
          $public_key = $keys['public_live_key'];
          $secret_key = $keys['secret_live_key'];
      }
      define('STRIPE_PUBLISHABLE_KEY', $public_key);
      define('STRIPE_API_KEY', $secret_key);


      // Check whether stripe checkout session is not empty
      $session_id = $_GET['session_id'];
      if($session_id != ""){
          //$session_id = $_GET['session_id'];

          // Include Stripe PHP library
          require_once APPPATH.'libraries/Stripe/init.php';

          // Set API key
          \Stripe\Stripe::setApiKey(STRIPE_API_KEY);

          // Fetch the Checkout Session to display the JSON result on the success page
          try {
              $checkout_session = \Stripe\Checkout\Session::retrieve($session_id);
          }catch(Exception $e) {
              $api_error = $e->getMessage();
          }

          if(empty($api_error) && $checkout_session){
              // Retrieve the details of a PaymentIntent
              try {
                  $intent = \Stripe\PaymentIntent::retrieve($checkout_session->payment_intent);
              } catch (\Stripe\Exception\ApiErrorException $e) {
                  $api_error = $e->getMessage();
              }

              // // Retrieves the details of customer
              // try {
              //     // Create the PaymentIntent
              //     $customer = \Stripe\Customer::retrieve($checkout_session->customer);
              // } catch (\Stripe\Exception\ApiErrorException $e) {
              //     $api_error = $e->getMessage();
              // }

              //if(empty($api_error) && $intent){
              if($intent){
                  // Check whether the charge is successful
                  if($intent->status == 'succeeded'){
                      return true;
                  }else{
                      return false;
                  }
              }else{
                  $status_msg = get_phrase("Unable_to_fetch_the_transaction_details"). ' ' .$api_error;
              }

          }else{
              $status_msg = get_phrase("Transaction_has_been_failed").' '.$api_error;
          }
      }else{
          $status_msg = get_phrase("Invalid_Request");
      }
      return false;
    }


    public function check_razorpay_payment($identifier = ""){
      //start common code of all payment gateway
      $payment_details = $this->session->userdata('payment_details');
      $payment_gateway = $this->db->get_where('payment_gateways', ['identifier' => $identifier])->row_array();

      if($payment_details['is_instructor_payout_user_id'] > 0){
        $instructor_details = $this->user_model->get_all_user($payment_details['is_instructor_payout_user_id'])->row_array();
        $keys = json_decode($instructor_details['payment_keys'], true);
        $keys = $keys[$payment_gateway['identifier']];
      }else{
        $keys = json_decode($payment_gateway['keys'], true);
      }
      $test_mode = $payment_gateway['enabled_test_mode'];
      //ended common code of all payment gateway

      if($test_mode == 1){
        $key_id = $keys['key_id'];
        $secret_key = $keys['secret_key'];
      } else {
        $key_id = $keys['key_id'];
        $secret_key = $keys['secret_key'];
      }

      $api = new Api($key_id, $secret_key);
      
      try {
        $attributes = array(
          'razorpay_order_id' => $_GET['order_id'],
          'razorpay_payment_id' => $_GET['payment_id'],
          'razorpay_signature' => $_GET['signature']
        );
        $api->utility->verifyPaymentSignature($attributes);
      } catch(SignatureVerificationError $e) {
        $error = 'Razorpay_Error : ' . $e->getMessage();
        return false;
      }

      //getting payment details
      //$response = $api->payment->fetch($_GET['payment_id']);

      return true;
    }

    public function razorpayPrepareData($identifier = ""){
      //start common code of all payment gateway
      $payment_gateway = $this->db->get_where('payment_gateways', ['identifier' => $identifier])->row_array();
      $user_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
      $payment_details = $this->session->userdata('payment_details');

      if($payment_details['is_instructor_payout_user_id'] > 0){
        $instructor_details = $this->user_model->get_all_user($payment_details['is_instructor_payout_user_id'])->row_array();
        $keys = json_decode($instructor_details['payment_keys'], true);
        $keys = $keys[$payment_gateway['identifier']];
      }else{
        $keys = json_decode($payment_gateway['keys'], true);
      }
      $test_mode = $payment_gateway['enabled_test_mode'];
      //ended common code of all payment gateway

      if($test_mode == 1){
          $key_id = $keys['key_id'];
          $secret_key = $keys['secret_key'];
      } else {
          $key_id = $keys['key_id'];
          $secret_key = $keys['secret_key'];
      }


      
        $api = new Api($key_id, $secret_key);
        $_SESSION['payable_amount'] = $payment_details['total_payable_amount'];

        $razorpayOrder = $api->order->create(array(
          'receipt'         => rand(),
          'amount'          => $_SESSION['payable_amount'] * 100, // 2000 rupees in paise
          'currency'        => $payment_gateway['currency'],
          'payment_capture' => 1 // auto capture
        ));
        $amount = $razorpayOrder['amount'];
        $razorpayOrderId = $razorpayOrder['id'];
        $_SESSION['razorpay_order_id'] = $razorpayOrderId;

      $data = array(
        "key" => $key_id,
        "amount" => $amount,
        "name" => get_settings('system_title'),
        "description" => get_settings('about_us'),
        "image" => base_url('uploads/system/'.get_settings('favicon1')),
        "prefill" => array(
        "name"  => $user_details['first_name'].' '.$user_details['last_name'],
        "email"  => $user_details['email'],
      ),
        "notes"  => array(
        "merchant_order_id" => rand(),
      ),
        "theme"  => array(
        "color"  => json_decode($payment_gateway['keys'], true)['theme_color']
      ),
        "order_id" => $razorpayOrderId,
      );
      return $data;
    }

    function checkLogin($payment_info = ""){
      if($this->session->userdata('user_id') > 0)
      {
          return $this->session->userdata('payment_details');
      }else{
          $cart_items = array();
          $payment_info = base64_decode($payment_info);
          $payment_info = json_decode($payment_info, true);
          $user_id = $payment_info[0];
          $payment_details = $payment_info[1];
          // Checking login credential for admin
          $query = $this->db->get_where('users', array('id' => $user_id));
          if ($query->num_rows() > 0) {
              $row = $query->row();
              $this->session->set_userdata('custom_session_limit', (time()+604800));
              $this->session->set_userdata('user_id', $row->id);
              $this->session->set_userdata('role_id', $row->role_id);
              $this->session->set_userdata('role', get_user_role('user_role', $row->id));
              $this->session->set_userdata('name', $row->first_name . ' ' . $row->last_name);
              $this->session->set_userdata('is_instructor', $row->is_instructor);
              $this->session->set_userdata('user_login', '1');
          }

          if($payment_details['is_instructor_payout_user_id'] == false){
              foreach($payment_details['items'] as $item){
                  if(isset($item['id']) && $item['id'] > 0){
                      $cart_items[] = $item['id'];
                  }
              }
          }
          $this->session->set_userdata('cart_items', $cart_items);
          $this->session->set_userdata('applied_coupon', $payment_info[2]);
          $this->session->set_userdata('payment_details', $payment_details);
          $this->session->set_userdata('total_price_of_checking_out', $payment_details['total_payable_amount']);

          return $payment_details;
      }
    }



}


