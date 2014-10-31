<div class="wrap">
	<h1><?php printf(__('View payments made for test "%s"', 'watupro'), $exam->name)?></h1>
	
	<p><a href="admin.php?page=watupro_takings&exam_id=<?php echo $exam->ID?>"><?php _e('Back to the record of users who submitted this exam.', 'watupro')?></a></p>
	
	<form method="post" action="#">
	<h2><?php _e('Manually add payment', 'watupro')?></h2>
	<p><?php _e('This will allow the user to take the exam like when they paid the fee through the website. You can use it to insert payments made by unsupported payment methods or just to allow someone take the exam for free.', 'watupro')?></p>
	<p><?php _e('Username:', 'watupro')?> <input type="text" name="user_login"> <?php _e('Amount paid:', 'watupro')?> <input type="text" size="6" name="amount">
	<input type="submit" name="add_payment" value="<?php _e('Add manual payment', 'watupro')?>"></p>
	</form>
	
	<hr>
	
	<?php if(!sizeof($payments)):?>
	<p><?php _e('No payments have been done yet for this exam', 'watupro')?></p>
	</div>
	<?php return true;
	endif;?>
	
	<table class="widefat">
		<tr><th><?php _e('User', 'watupro')?></th><th><?php _e('Date paid', 'watupro')?></th><th><?php _e('Amount', 'watupro')?></th>
		<th><?php _e('Status', 'watupro')?></th><th><?php _e('Payment method', 'watupro')?></th><th><?php _e('Delete', 'watupro')?></th></tr>
		<?php foreach($payments as $payment):?>
			<tr><td><?php echo $payment->user_login?></td><td><?php echo date(get_option('date_format'), strtotime($payment->date))?></td>
			<td><?php if($payment->method == 'points'): echo ($payment->amount * $paypoints_price).' '.__('points');
			else: echo $currency." ".$payment->amount; endif;?></td>
			<td><?php if($payment->status == 'completed'): _e('Completed', 'watupro');?>				
				<a href="#" onclick="WatuPROChangeStatus(0, <?php echo $payment->ID?>);return false;"><?php _e('Make Pending', 'watupro')?></a>
			<?php else: 
					if($payment->status == 'pending'): _e('Pending', 'watupro'); endif;
					if($payment->status == 'used'): _e('Used', 'watupro'); endif;?>
				<a href="#" onclick="WatuPROChangeStatus(1, <?php echo $payment->ID?>);return false;"><?php _e('Complete', 'watupro')?></a>
			<?php endif;?></td>
			<td><?php echo empty($payment->method) ? "Paypal" : $payment->method?></td>
			<td><a href="#" onclick="WatuPRODeletePayment(<?php echo $payment->ID?>);return false;"><?php _e('Delete', 'watupro')?></a></td></tr>
		<?php endforeach;?>	
	</table>
	
	<p align="center">
	<?php if($offset > 0):?>
		<a href="admin.php?page=watupro_payments&exam_id=<?php echo $exam->ID?>&offset=<?php echo $offset - 100?>"><?php _e('previous page', 'watupro');?></a>
	<?php endif;?>
	
	<?php if(($offset + 10) < $count):?>
		<a href="admin.php?page=watupro_payments&exam_id=<?php echo $exam->ID?>&offset=<?php echo $offset + 100?>"><?php _e('next page', 'watupro');?></a>
	<?php endif;?>
	</p>
</div>

<script type="text/javascript" >
function WatuPROChangeStatus(status, id) {
	if(confirm("<?php _e('Are you sure?', 'watupro')?>")) {
		window.location="admin.php?page=watupro_payments&exam_id=<?php echo $exam->ID?>&offset=<?php echo $offset?>&change_status=1&status="+status+"&id="+id;
	}
}

function WatuPRODeletePayment(id) {
	if(confirm("<?php _e('Are you sure?', 'watupro')?>")) {
		window.location="admin.php?page=watupro_payments&exam_id=<?php echo $exam->ID?>&offset=<?php echo $offset?>&delete=1&id="+id;
	}
}
</script>