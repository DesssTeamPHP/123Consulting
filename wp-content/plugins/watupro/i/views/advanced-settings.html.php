	<div class="inside">
			<h3><?php _e('Advanced Final Screen Settings', 'watupro') ?></h3>
			
			<p><input type="checkbox" name="confirm_on_submit" value="1" <?php if(!empty($advanced_settings['confirm_on_submit'])) echo 'checked'?>> <?php _e('Ask for confirmation when the "Submit" button is pressed.', 'watupro')?></p>		
			
			<p><input type="checkbox" name="no_checkmarks" value="1" <?php if(!empty($advanced_settings['no_checkmarks'])) echo 'checked'?>> <?php _e('Do not show correct / incorrect checkmarks at all.', 'watupro')?></p>		
			<p><input type="checkbox" name="no_checkmarks_unresolved" value="1" <?php if(!empty($advanced_settings['no_checkmarks_unresolved'])) echo 'checked'?>> <?php _e('Do not show correct / incorrect checkmarks on unresolved questions to avoid right answers being revealed.', 'watupro')?></p>	
			<p><input type="checkbox" name="reveal_correct_gaps" value="1" <?php if(!empty($advanced_settings['reveal_correct_gaps'])) echo 'checked'?>> <?php _e('Reveal the correct answers on unanswered and wrongly answered fields in "Fill the gaps" questions.', 'watupro')?></p>		
			
			<p>&nbsp;</p>	
	</div>
			
	<div class="inside">
			<h3><?php _e('Advanced Workflow Settings', 'watupro') ?></h3>
			<p><input type="checkbox" name="dont_prompt_unanswered" value="1" <?php if(!empty($advanced_settings['dont_prompt_unanswered'])) echo 'checked'?>> <?php _e('Do not prompt the user when a non-required question is not answered.', 'watupro')?></p>		
			
			<?php if($exam->single_page==0):?>
				<p><input type="checkbox" name="dont_load_inprogress" value="1" <?php if(!empty($advanced_settings['dont_load_inprogress'])) echo 'checked'?>> <?php _e("Don't load the unfinished quiz when user comes back to continue (Normally the software would let the user continue from where they were).", 'watupro')?></p>		
				<p><input type="checkbox" name="dont_scroll" value="1" <?php if(!empty($advanced_settings['dont_scroll'])) echo 'checked'?>> <?php _e("Don't auto-scroll the screen when user moves from page to page (Auto-scrolling happens to ensure user always sees the top of the page).", 'watupro')?></p>	
			<?php endif;?>
				
			<p>&nbsp;</p>
	</div>		
			
	<div class="inside">
			<h3><?php _e('Email Related Configuration', 'watupro') ?></h3>		
			<p><input type="checkbox" name="email_not_required" value="1" <?php if(!empty($advanced_settings['email_not_required'])) echo 'checked'?>> <?php _e('Entering email to receive quiz results is optional for non-logged in users. (Takes effect only when you have selected "Send email to the user with their results")', 'watupro')?></p>
		
			<p>&nbsp;</p>
	</div>
	<div class="inside">		
			
			<h3><?php _e('Student Dashboard Settings', 'watupro') ?></h3>
			
			<p><input type="checkbox" name="show_only_snapshot" value="1" <?php if(!empty($advanced_settings['show_only_snapshot'])) echo 'checked'?>> <?php _e('Show only snapshot when user opens taken quiz details pop-up. Admins/teachers will still be able to get the table format and CSV download.', 'watupro')?></p>	
			
			<p>&nbsp;</p>
	</div>		
	<div class="inside">			
			
			<h3><?php _e('Paginator Settings', 'watupro') ?></h3>
			
			<p><?php _e('This configuration takes effect for quizzes that use numbered pagination. For the colors below you can enter words like "red", "orange", etc, or HTML color value like "#FFCCAA".','watupro')?></p>
			
			<p><label><?php _e('Color of answered question number (defaults to green):','watupro')?></label> <input type="text" size="10" name="answered_paginator_color" value="<?php echo @$advanced_settings['answered_paginator_color']?>"></p>			
			<p><label><?php _e('Color of unanswered question number (defaults to red):','watupro')?></label> <input type="text" size="10" name="unanswered_paginator_color" value="<?php echo @$advanced_settings['unanswered_paginator_color']?>"></p>
			
			<p>&nbsp;</p>
	</div>
	<div class="inside">			
			
			<h3 class="hndle"><span><?php _e('Answers Enumeration', 'watupro') ?></span></h3>
			<p><?php _e('This lets you enumerate the answers to single-choice and multiple-choice questions with numbers, small letters, or capital letters.', 'watupro')?></p>
			<p><label><?php _e('Enumerator:', 'watupro')?></label> <select name="enumerate_choices">
				<option value="" <?php if(empty($advanced_settings['enumerate_choices'])) echo 'selected'?>><?php _e('None', 'watupro')?></option>
				<option value="number" <?php if(!empty($advanced_settings['enumerate_choices']) and $advanced_settings['enumerate_choices'] == 'number') echo 'selected'?>><?php _e('Numbers', 'watupro')?></option>
				<option value="cap_letter" <?php if(!empty($advanced_settings['enumerate_choices']) and $advanced_settings['enumerate_choices'] == 'cap_letter') echo 'selected'?>><?php _e('Capital letters', 'watupro')?></option>
				<option value="small_letter" <?php if(!empty($advanced_settings['enumerate_choices']) and $advanced_settings['enumerate_choices'] == 'small_letter') echo 'selected'?>><?php _e('Small letters', 'watupro')?></option>
			</select></p>
		
			<p>&nbsp;</p>
	</div>
	<div class="inside">			
			
    		<h3 class="hndle"><span><?php _e('Advanced Question Randomization', 'watupro') ?></span></h3>
    		<?php if(!$exam->random_per_category):?>
			<p><b><?php _e('Randomization currenty not in effect.', 'watupro');?></b> <?php _e('You need to pull random questions per category on the main page for this to have any effect.', 'watupro')?></p>
		<?php endif?>
    		
    		<?php if($exam->pull_random and $exam->random_per_category):?>
	    		<p><?php printf(__('You have chosen to pull %d random questions per category. Here you can elaborate by selecting specific random number for every question category.', 'watupro'), $exam->pull_random);?></p>
    		<?php endif;?>
    		
			<table cellpadding="8">
				<tr><th><?php _e('Order', 'watupro')?></th> <th><?php _e('Category', 'watupro')?></th> <th><?php _e('No. questions', 'watupro')?></th></tr>
				<?php foreach($qcats as $qcat):?>
					<tr><td><input type="text" size="3" name="qcat_order_<?php echo $qcat->ID?>" value="<?php echo $qcat->sort_order?>"></td><td><?php echo $qcat->name?></td><td><input type="text" size="4" name="random_per_<?php echo $qcat->ID?>" value="<?php echo isset($advanced_settings['random_per_'.$qcat->ID]) ? $advanced_settings['random_per_'.$qcat->ID] : $exam->pull_random?>"></td></tr>
				<?php endforeach;?>
			</table>
	</div>