<?php
// class handling payment restrictions, IPN etc
class WatuPROPayment {	
	// render payment button and info if any	
	static function render($exam) {
		global $post, $user_ID, $watupro_keep_chars;
		
		$paypal_email = get_option("watupro_paypal");
		$accept_stripe = get_option('watupro_accept_stripe');
		$accept_paypoints = get_option('watupro_accept_paypoints');
		$other_payments = get_option("watupro_other_payments");
		$currency = get_option('watupro_currency');
		$watupro_keep_chars = true;
		
		// setup Stripe
		if($accept_stripe) {
				require_once(WATUPRO_PATH.'/i/lib/Stripe.php');
 
				$stripe = array(
				  'secret_key'      => get_option('watupro_stripe_secret'),
				  'publishable_key' => get_option('watupro_stripe_public')
				);
				 
				Stripe::setApiKey($stripe['secret_key']);
		}		
		
		if(empty($paypal_email) and empty($other_payments) and empty($accept_stripe) and empty($accept_paypoints)) {
			echo "<!-- WATUPROCOMMENT: there is exam fee but no Paypal ID or other payment info has been set in WatuPRO Settings page -->";
			return false;
		}
		
		// replace shortcodes
		if(!empty($other_payments)) {
			$other_payments = str_replace("[AMOUNT]", $exam->fee, $other_payments);
			$other_payments = str_replace("[USER_ID]", $user_ID, $other_payments);
			$other_payments = str_replace("[EXAM_TITLE]", $exam->name, $other_payments);
			$other_payments = str_replace("[EXAM_ID]", $exam->ID, $other_payments);
		}
		
		// paypoints - render only if user balance allows it.
		if(!empty($accept_paypoints)) {
			$paypoints_price = get_option('watupro_paypoints_price');
			$paypoints_button = get_option('watupro_paypoints_button');
			
			$cost_in_points = round($exam->fee * $paypoints_price);
			$user_points = get_user_meta($user_ID, 'watuproplay-points', true);	
			
			if($user_points < $cost_in_points) $paybutton = __('Not enough points.', 'watupro');
			else {
				$url = admin_url("admin-ajax.php?action=watupro_pay_with_points");
				$paybutton = "<input type='button' value='".sprintf(__('Pay %d points', 'watupro'), $cost_in_points)."' onclick='WatuPROPay.payWithPoints({$exam->ID}, \"$url\");'>";
			}
			
			// replace the codes in the design
			$paypoints_button = str_replace('{{{points}}}', $cost_in_points, $paypoints_button);
			$paypoints_button = str_replace('{{{user-points}}}', $user_points, $paypoints_button);
			$paypoints_button = str_replace('{{{button}}}', $paybutton, $paypoints_button);
			$paypoints_button = stripslashes($paypoints_button);
		}
			
		if(@file_exists(get_stylesheet_directory().'/watupro/i/views/payment.php')) require get_stylesheet_directory().'/watupro/i/views/payment.php';
		else require WATUPRO_PATH."/i/views/payment.php";
		return true;
	}
	
	// check if there is payment made from this user for this exam
	static function valid_payment($exam) {
		global $wpdb, $user_ID;
		
		if(empty($user_ID) or !is_numeric($user_ID)) return false;
		
		// any bundles that contain this quiz and the user paid for them?
		$valid_bundle_payment = $wpdb->get_var("SELECT tP.ID FROM ".WATUPRO_PAYMENTS." tP
			JOIN ".WATUPRO_BUNDLES." tB ON tB.ID = tP.bundle_id 
			WHERE user_id={$user_ID} AND status='completed' AND bundle_id!=0 AND (
			  (tB.bundle_type = 'category' AND cat_id={$exam->cat_id}) 
			  OR
			  (tB.bundle_type = 'quizzes' AND (quiz_ids LIKE '{$exam->ID}' 
				  OR quiz_ids LIKE '%,{$exam->ID}' OR quiz_ids LIKE '%,{$exam->ID},%' OR quiz_ids LIKE '{$exam->ID},%') )
			)");
			
		if(!empty($valid_bundle_payment)) return true;	
		
		// if no bundles, check for quiz payment
		$payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_PAYMENTS."
			WHERE exam_id=%d AND user_id=%d ORDER BY ID DESC LIMIT 1", $exam->ID, $user_ID));
		if(empty($payment->ID) or $payment->status != 'completed') return false;
			
		return true;	
	}
	
	// handle query vars
	static function query_vars($vars) {
		// http://www.james-vandyne.com/process-paypal-ipn-requests-through-wordpress/
		$new_vars = array('watupro');
		$vars = array_merge($new_vars, $vars);
	   return $vars;
	} 
	
	// handle Paypal IPN request
	static function parse_request($wp) {
		// only process requests with "watupro=paypal"
	   if (array_key_exists('watupro', $wp->query_vars) 
	            && ($wp->query_vars['watupro'] == 'paypal' or $wp->query_vars['watupro'] == 'paypal_bundle')) {
	        self::paypal_ipn($wp);
	   }	
	}
	
	// process paypal IPN
	static function paypal_ipn($wp) {
		global $wpdb;
		echo "<!-- WATUPROCOMMENT paypal IPN -->";
		
	   $paypal_email = get_option("watupro_paypal");
		// print_r($_GET);
		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
		foreach ($_POST as $key => $value) { 
		  $value = urlencode(stripslashes($value)); 
		  $req .= "&$key=$value";
		}		
		
		// post back to PayPal system to validate
		$header="";
		$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n";
		$header .="Host: www.paypal.com\r\n"; 
		$header .="Connection: close\r\n\r\n";		
		$fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);
		
		if($fp) {
			fputs ($fp, $header . $req);
		   while (!feof($fp)) {
		      $res = fgets ($fp, 1024);
		     
		      if (strstr ($res, "200 OK")) {
		      	// check the payment_status is Completed
			      // check that txn_id has not been previously processed
			      // check that receiver_email is your Primary PayPal email
			      // process payment
				   $payment_completed = false;
				   $txn_id_okay = false;
				   $receiver_okay = false;
				   $payment_currency_okay = false;
				   $payment_amount_okay = false;
				   
				   if($_POST['payment_status']=="Completed") {
				   	$payment_completed = true;
				   } 
				   else self::log_and_exit("Payment status: $_POST[payment_status]");
				   
				   // check txn_id
				   $txn_exists = $wpdb->get_var($wpdb->prepare("SELECT paycode FROM {$wpdb->prefix}watupro_payments 
					   WHERE paycode=%s", $_POST['txn_id']));
					if(empty($txn_id)) $txn_id_okay = true; 
					else self::log_and_exit("TXN ID exists: $txn_id");  
					
					// check receiver email
					if($_POST['business']==$paypal_email or $_POST['receiver_id'] == $paypal_email) {
						$receiver_okay = true;
					}
					else self::log_and_exit("Business email is wrong: $_POST[business]");
					
					// check payment currency
					if($_POST['mc_currency']==get_option("watupro_currency")) {
						$payment_currency_okay = true;
					}
					else self::log_and_exit("Currency is $_POST[mc_currency]"); 
					
					// check amount					
					if($wp->query_vars['watupro'] == 'paypal_bundle') {
						$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}watupro_bundles WHERE ID=%d", $_POST['item_number']));
						$fee = $bundle->price;
					} 
					else {
						$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}watupro_master WHERE ID=%d", $_POST['item_number']));
						$fee = $exam->fee;
					}
					
					if($_POST['mc_gross']>=$fee ) {
						$payment_amount_okay = true;
					}
					else self::log_and_exit("Wrong amount: $_POST[mc_gross] when price is {$fee}"); 
					
					// everything OK, insert payment
					if($payment_completed and $txn_id_okay and $receiver_okay and $payment_currency_okay 
							and $payment_amount_okay) {						
						$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}watupro_payments SET 
							exam_id=%d, user_id=%s, date=CURDATE(), amount=%s, status='completed', paycode=%s, 
							method='paypal', bundle_id=%d", 
							@$exam->ID, $_GET['user_id'], $fee, $_POST['txn_id'], @$bundle->ID));
						exit;
					}
		     	}
		     	else self::log_and_exit("Paypal result is not 200 OK: $res");
		   }  
		   fclose($fp);  
		} 
		else self::log_and_exit("Can't connect to Paypal");
		
		exit;
	}
	
	// log paypal errors
	static function log_and_exit($msg) {
		// log
		$msg = "Payment error occured at ".date(get_option('date_format').' '.get_option('time_format'))." with message: ".$msg;
		$errorlog=get_option("watupro_errorlog");
		$errorlog = $msg."\n".$errorlog;
		update_option("watupro_errorlog",$errorlog);
		
		// throw exception as there's no need to contninue
		exit;
	}
	
	// process Stripe Payment
	static function Stripe($is_bundle = false) {
		global $wpdb, $user_ID;
		require_once(WATUPRO_PATH.'/i/lib/Stripe.php');
 
		$stripe = array(
		  'secret_key'      => get_option('watupro_stripe_secret'),
		  'publishable_key' => get_option('watupro_stripe_public')
		);
		 
		Stripe::setApiKey($stripe['secret_key']);		
		
		$token  = $_POST['stripeToken'];
		
		if($is_bundle) {
			$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d", $_POST['bundle_id']));
			$fee = $bundle->price;
		}
		else {
			$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_POST['exam_id']));
			$fee = $exam->fee;
		}
		$user = get_userdata($user_ID);
		$currency = get_option('watupro_currency');
			 
		try {
			 $customer = Stripe_Customer::create(array(
		      'email' => $user->user_email,
		      'card'  => $token
		  ));				
			
		  $charge = Stripe_Charge::create(array(
		      'customer' => $customer->id,
		      'amount'   => $fee*100,
		      'currency' => $currency
		  ));
		} 
		catch (Exception $e) {
			wp_die($e->getMessage());
		}	  
		
		// insert payment record
		$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET 
			exam_id=%d, user_id=%s, date=CURDATE(), amount=%s, status='completed', paycode=%s, 
			method='stripe', bundle_id=%d", 
			@$exam->ID, $user_ID, $fee, $customer->ID, @$bundle->ID));
			
		// redirect to self to avoid inserting again
		watupro_redirect($_SERVER['REQUEST_URI']);	
	}	
	
	// when paid exam is completed see whether we have to change the associated payment status
	static function completed_exam($taking_id, $exam) {
		global $wpdb, $user_ID;
		
		// update the last payment of this user to status "used"
		$payment_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".WATUPRO_PAYMENTS."
			WHERE exam_id=%d AND user_id=%d ORDER BY ID DESC LIMIT 1", $exam->ID, $user_ID));
		
		if(!empty($payment_id)) $wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_PAYMENTS." SET
			status='used' WHERE ID=%d AND user_id=%d", $payment_id, $user_ID));
	}
}