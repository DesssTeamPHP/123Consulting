<p align="center"><strong><?php printf(__("There is a fee of %s %s to access this %s.", 'watupro'), $currency, $exam->fee, __('quiz', 'watupro'))?></strong></p>

<?php if($paypal_email): // generate Paypal button ?>
	<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
	<p align="center">
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="<?php echo $paypal_email?>">
		<input type="hidden" name="item_name" value="<?php echo __('Exam', 'watupro').' '.$exam->name?>">
		<input type="hidden" name="item_number" value="<?php echo $exam->ID?>">
		<input type="hidden" name="amount" value="<?php echo number_format($exam->fee,2,".","")?>">
		<input type="hidden" name="return" value="<?php echo get_permalink( $post->ID );?>">
		<input type="hidden" name="notify_url" value="<?php echo site_url('?watupro=paypal&user_id='.$user_ID);?>">
		<input type="hidden" name="no_shipping" value="1">
		<input type="hidden" name="no_note" value="1">
		<input type="hidden" name="currency_code" value="<?php echo $currency;?>">
		<input type="hidden" name="lc" value="US">
		<input type="hidden" name="bn" value="PP-BuyNowBF">
		<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-butcc.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
	</p>
	</form> 
<?php endif;
if($accept_stripe and !empty($stripe['secret_key'])): // generate stripe button?>
	<form method="post">
		<p align="center">
	  <script src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
	          data-key="<?php echo $stripe['publishable_key']; ?>"
	          data-amount="<?php echo $exam->fee*100?>" data-description="<?php echo __('Exam', 'watupro').' '.$exam->name?>" data-currency="<?php echo $currency?>"></script>
	<input type="hidden" name="stripe_pay" value="1">
	<input type="hidden" name="exam_id" value="<?php echo $exam->ID?>">
	</p>
	</form>
<?php endif;

if($accept_paypoints): echo $paypoints_button; endif;?>

<?php if(!empty($other_payments)): echo "<div align='center'>".wpautop(stripslashes($other_payments))."</div>"; endif;?>