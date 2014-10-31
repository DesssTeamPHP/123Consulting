<?php
class WatuPROPayments {
	// view/add payments for exam
	static function manage() {
		global $wpdb;
		
		// select this exam
		$exam = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".WATUPRO_EXAMS." WHERE ID = %d", $_GET['exam_id']));
		if(empty($exam->ID)) wp_die(__('No exam with this ID', 'watupro'));
		
		// add payment manually
		if(!empty($_POST['add_payment'])) {
			// find the given user first
			$user = get_user_by('login', $_POST['user_login']);
			if(empty($user->user_login)) wp_die(__('Unrecognized user login', 'watupro'));
			
			// now insert the payment
			$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET
				exam_id=%d, user_id=%d, date=CURDATE(), amount=%s, status='completed', paycode='manual', method='manual'", 
				$exam->ID, $user->ID, $_POST['amount']));
				
			watupro_redirect("admin.php?page=watupro_payments&exam_id=".$exam->ID);	
		}
		
		$offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
		
		// delete payment
		if(!empty($_GET['delete'])) {
			$wpdb->query( $wpdb->prepare("DELETE FROM ".WATUPRO_PAYMENTS." WHERE id=%d", $_GET['id']));
			watupro_redirect("admin.php?page=watupro_payments&exam_id=".$exam->ID."&offset=$offset");
		}
		
		// approve/unapprove payment
		if(!empty($_GET['change_status'])) {
			$status = empty($_GET['status']) ? 'pending' : 'completed';
			$wpdb->query( $wpdb->prepare("UPDATE ".WATUPRO_PAYMENTS." SET status='$status' WHERE id=%d", $_GET['id']));
			watupro_redirect("admin.php?page=watupro_payments&exam_id=".$exam->ID."&offset=$offset");
		}
		
		// select payments made		
		$payments = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS tP.*, tU.user_login as user_login 
			FROM ".WATUPRO_PAYMENTS." tP LEFT JOIN {$wpdb->users} tU ON tU.ID = tP.user_id
			WHERE tP.exam_id=%d ORDER BY tP.ID DESC LIMIT $offset, 100", $exam->ID));
			
		$count = $wpdb->get_var("SELECT FOUND_ROWS()");	
		
		$currency = get_option('watupro_currency');
		$paypoints_price = get_option('watupro_paypoints_price');
			
		if(@file_exists(get_stylesheet_directory().'/watupro/i/payments.html.php')) require get_stylesheet_directory().'/watupro/i/payments.html.php';
		else require WATUPRO_PATH."/i/views/payments.html.php";
	}
	
	// handle the ajax request of the payment with points
	static function pay_with_points() {
		global $wpdb, $user_ID;
		
		if(!is_user_logged_in()) die("ERROR: Not logged in");
		
		// payment with points accepted at all?
		$accept_paypoints = get_option('watupro_accept_paypoints');
		if(empty($accept_paypoints)) die("ERROR: points not accepted as payment method."); 
		
		// enough points to pay?
		$paypoints_price = get_option('watupro_paypoints_price');
		if(empty($_POST['is_bundle'])) {
			$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_POST['id']));
			$fee = $exam->fee;
			$cost_in_points = $exam->fee * $paypoints_price;
		}
		else {
			// bundle
			$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d", $_POST['id']));
			$fee = $bundle->price;
			$cost_in_points = $bundle->price * $paypoints_price;
		}
		
		$user_points = get_user_meta($user_ID, 'watuproplay-points', true);	
		if($user_points < $cost_in_points) die("ERROR: Not enough points");
		
		// now make payment
		$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET 
			exam_id=%d, user_id=%s, date=CURDATE(), amount=%s, status='completed', paycode=%s, 
			method='points', bundle_id=%d", 
			@$exam->ID, $user_ID, $fee, '', @$bundle->ID));
				
		// deduct user points
		$user_points -= $cost_in_points;
		update_user_meta($user_ID, 'watuproplay-points', $user_points);	
			
		echo "SUCCESS";
		exit;
	}
	
	// display and create buttons for buying quiz bundles
	static function bundles() {
		global $wpdb;
		$currency = get_option('watupro_currency');
		$accept_stripe = get_option('watupro_accept_stripe');
		$accept_points = get_option('watupro_accept_paypoints');
		$do = empty($_GET['do']) ? 'list' : $_GET['do'];
		
		// select all quizzes
		$quizzes = $wpdb->get_results("SELECT ID, name FROM ".WATUPRO_EXAMS." WHERE fee > 0 ORDER BY name");
		
		// select quiz cats
		$cats = $wpdb->get_results("SELECT ID, name FROM ".WATUPRO_CATS." ORDER BY name");
		
		switch($do) {
			case 'add':
				if(!empty($_POST['ok'])) {
					$cat_id = ($_POST['bundle_type'] == 'quizzes') ? 0 : $_POST['cat_id'];
					$quiz_ids = ($_POST['bundle_type'] == 'quizzes') ? implode(",", @$_POST['quizzes']) : '';
					$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_BUNDLES." SET
						price=%f, bundle_type=%s, quiz_ids=%s, cat_id=%d, redirect_url=%s",
						$_POST['price'], $_POST['bundle_type'], $quiz_ids, $cat_id, $_POST['redirect_url']));
						
					watupro_redirect("admin.php?page=watupro_bundles");	
				}
				
				if(@file_exists(get_stylesheet_directory().'/watupro/i/bundle.html.php')) require get_stylesheet_directory().'/watupro/i/bundle.html.php';
		else require WATUPRO_PATH."/i/views/bundle.html.php";
			break;		
			
			case 'edit':
				if(!empty($_POST['ok'])) {
					$cat_id = ($_POST['bundle_type'] == 'quizzes') ? 0 : $_POST['cat_id'];
					$quiz_ids = ($_POST['bundle_type'] == 'quizzes') ? implode(",", @$_POST['quizzes']) : '';
					$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_BUNDLES." SET
						price=%f, bundle_type=%s, quiz_ids=%s, cat_id=%d, redirect_url=%s
						WHERE ID=%d",
						$_POST['price'], $_POST['bundle_type'], $quiz_ids, $cat_id, $_POST['redirect_url'], $_GET['id']));
						
					watupro_redirect("admin.php?page=watupro_bundles");	
				}
				
				$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d", $_GET['id']));
				$qids = explode(",", $bundle->quiz_ids);
				
				if(@file_exists(get_stylesheet_directory().'/watupro/i/bundle.html.php')) require get_stylesheet_directory().'/watupro/i/bundle.html.php';
		else require WATUPRO_PATH."/i/views/bundle.html.php";
			break;				
			
			case 'list':
			default:
				if(!empty($_GET['del'])) {
					$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPRO_BUNDLES." WHERE ID=%d", $_GET['id']));
					watupro_redirect("admin.php?page=watupro_bundles");
				}			
			
				// select current bundles left join by cat
				$bundles = $wpdb->get_results("SELECT tB.*, tC.name as cat 
					FROM ".WATUPRO_BUNDLES." tB LEFT JOIN ".WATUPRO_CATS." tC
					ON tC.ID = tB.cat_id
					ORDER BY tB.ID");
					
				// add quizzes to the bundles
				foreach($bundles as $cnt => $bundle) {
					$quiz_names = array();
					if($bundle->bundle_type == 'quizzes') {
						$qids = explode(",", $bundle->quiz_ids);
						foreach($quizzes as $quiz) {
							if(in_array($quiz->ID, $qids)) $quiz_names[] = $quiz->name;
						} // end foreach quiz
						
						$bundles[$cnt]->quizzes = implode(", ", $quiz_names);
					} // end if
				}	// end foreach bundle
				
				if(@file_exists(get_stylesheet_directory().'/watupro/i/bundles.html.php')) require get_stylesheet_directory().'/watupro/i/bundles.html.php';
		else require WATUPRO_PATH."/i/views/bundles.html.php";
			break;
		}
	} // end managing bundles
	
	// display payment bundle button
	static function bundle_button($atts) {
		global $wpdb, $post, $user_ID;
		watupro_vc_scripts();
		$mode = @$atts['mode'];
		$currency = get_option('watupro_currency');
		
		if(empty($user_ID)) return __('You need to be logged in.', 'watupro');
				
		// select this bundle
		$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d", $atts['id']));
		if(empty($bundle->ID)) return "<p>".__('This offer has been disabled.', 'watupro')."</p>";
		
		// if the user already paid for this bundle and the bundle has redirect URL defined, just go to it
		if(!empty($bundle->redirect_url)) {
			$valid_bundle_payment = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".WATUPRO_PAYMENTS."
				WHERE user_id=%d AND bundle_id=%d AND status='completed'", $user_ID, $bundle->ID));
			if($valid_bundle_payment) watupro_redirect($bundle->redirect_url);	
		}
		
		// prepare bundle name
		$bundle_name = '';
		if($bundle->bundle_type == 'category') $bundle_name = sprintf(__('Access to a category of %s', 'watupro'), __('quizzes', 'watupro'));
		else $bundle_name = sprintf(__('Access to a selection of %s', 'watupro'), __('quizzes', 'watupro')); 
		
		ob_start();
		switch($mode) {
			case 'paypoints':
				$paypoints_price = get_option('watupro_paypoints_price');
				$paypoints_button = get_option('watupro_paypoints_button');
				
				$cost_in_points = round($bundle->price * $paypoints_price);
				$user_points = get_user_meta($user_ID, 'watuproplay-points', true);	
				
				if($user_points < $cost_in_points) $paybutton = __('Not enough points.', 'watupro');
				else {
					$url = admin_url("admin-ajax.php?action=watupro_pay_with_points");
					$paybutton = "<input type='button' value='".sprintf(__('Pay %d points', 'watupro'), $cost_in_points)."' onclick='WatuPROPay.payWithPoints({$bundle->ID}, \"$url\", 1, \"{$bundle->redirect_url}\");'>";
				}
				
				// replace the codes in the design
				$paypoints_button = str_replace('{{{points}}}', $cost_in_points, $paypoints_button);
				$paypoints_button = str_replace('{{{user-points}}}', $user_points, $paypoints_button);
				$paypoints_button = str_replace('{{{button}}}', $paybutton, $paypoints_button);
				$paypoints_button = stripslashes($paypoints_button);
				echo do_shortcode($paypoints_button);
			break;			
			
			case 'stripe':
				include_once(WATUPRO_PATH.'/i/lib/Stripe.php');
 
				$stripe = array(
				  'secret_key'      => get_option('watupro_stripe_secret'),
				  'publishable_key' => get_option('watupro_stripe_public')
				);
				 
				Stripe::setApiKey($stripe['secret_key']);
				?>
				<form method="post">
					
				  <script src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
				          data-key="<?php echo $stripe['publishable_key']; ?>"
				          data-amount="<?php echo $bundle->price*100?>" data-description="<?php echo $bundle_name?>" data-currency="<?php echo $currency?>"></script>
				<input type="hidden" name="stripe_bundle_pay" value="1">
				<input type="hidden" name="bundle_id" value="<?php echo $bundle->ID?>">
				
				</form>
			<?php break;			
			
			case 'paypal':
			default:
			$paypal_email = get_option("watupro_paypal"); ?>
			<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="<?php echo $paypal_email?>">
				<input type="hidden" name="item_name" value="<?php echo $bundle_name?>">
				<input type="hidden" name="item_number" value="<?php echo $bundle->ID?>">
				<input type="hidden" name="amount" value="<?php echo $bundle->price?>">
				<input type="hidden" name="return" value="<?php echo $bundle->redirect_url ? $bundle->redirect_url : get_permalink( $post->ID );?>">
				<input type="hidden" name="notify_url" value="<?php echo site_url('?watupro=paypal_bundle&user_id='.$user_ID);?>">
				<input type="hidden" name="no_shipping" value="1">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="currency_code" value="<?php echo $currency;?>">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="bn" value="PP-BuyNowBF">
				<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-butcc.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form> 
			<?php break;
		}
		
		$contents = ob_get_clean();
		return $contents;		
	} // end bundle_button
}