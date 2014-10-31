<?php
/*
Plugin Name: WatuPRO Bridge for Woocommerce
Plugin URI: 
Description: This bridge allows you to sell access to your premium quizzes through Woocommerce 
Author: Kiboko Labs
Version: 0.7
Author URI: http://calendarscripts.info/
License: GPLv2 or later
*/

define( 'WW_BRIDGE_PATH', dirname( __FILE__ ) );
// register_activation_hook(__FILE__, 'pdf_bridge_init');

add_action('init', 'ww_bridge_init');

function ww_bridge_init() {
	global $wpdb;
	
	// catch the woocommerce action
	add_action('woocommerce_order_status_completed', 'ww_bridge_order_complete');
	add_action('template_redirect', 'ww_bridge_template_redirect');
}

// complete Woocommerce order
function ww_bridge_order_complete($order_id) {
	global $wpdb;
		
	update_option('ww_bridge_last_order_id', $order_id);
	
	// select line items
	$items = $wpdb->get_results($wpdb->prepare("SELECT tI.*, tM.meta_value as product_id 
			FROM {$wpdb->prefix}woocommerce_order_items tI JOIN {$wpdb->prefix}woocommerce_order_itemmeta tM
			ON tM.order_item_id = tI.order_item_id AND tM.meta_key='_product_id'
			WHERE tI.order_id = %d AND tI.order_item_type = 'line_item'", $order_id));
	$quiz_ids = array(); // quiz IDs to process
	
	// now for each $item select the product, and check in the meta whether it's watupro quiz
	foreach($items as $item) {
		$product = get_post($item->product_id);
		update_option('ww_bridge_last_product_title', $product->post_title);		
		// get meta
		$atts = get_post_meta($product->ID, '_product_attributes', true);
		
		foreach($atts as $key=>$att) {		
			if($att['name'] == 'watupro' and !empty($att['value']) and is_numeric($att['value'])) $quiz_ids[] = $att['value'];
		}
	}	// end foreach item	
	
	
	// if there are quiz ids we'll activate them but first need to ensure there is user ID
	if(!empty($quiz_ids)) {
		// select order  meta
		$user_id = get_post_meta($order_id, "_customer_user", true);
		
		if(empty($user_id)) {
			$password = wp_generate_password( 12, true );
			$user_email = get_post_meta($order_id, "_billing_email", true);
			
			// email exists?
			$user = get_user_by('email', $user_email);
			if(empty($user->ID)) {
				$user_id = wp_create_user( $user_email, $password, $user_email );
				wp_update_user( array ('ID' => $user_id, 'role' => 'student' ) ) ;
			}
			else $user_id = $user->ID;
		}
		
		// now insert payments for this user ID and the given quiz IDs
		foreach($quiz_ids as $quiz_id) {
			$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET exam_id=%d, user_id=%d, date=CURDATE(),
				amount=1, status='completed', method='woocommerce'", $quiz_id, $user_id));
		}
	}
} // end ww_bridge_order_complete

// test it by just passing order ID
// if you want to test you have to change "and false" to "and true" in the code below
function ww_bridge_template_redirect() {
	if(!empty($_GET['wwbridge_order_id']) and false) {
		ww_bridge_order_complete($_GET['wwbridge_order_id']);
		exit;
	}	
}