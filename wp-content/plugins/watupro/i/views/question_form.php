<label>&nbsp;<input type='radio' name='answer_type' <?php if(!empty($ans_type) and $ans_type=='gaps') echo "checked"?> id="answer_type_g" value='gaps' onclick="selectAnswerType('gaps');" /> <?php _e('Fill The Gaps', 'watupro')?></label> &nbsp;&nbsp;&nbsp;
<label>&nbsp;<input type='radio' name='answer_type' <?php if(!empty($ans_type) and $ans_type=='sort') echo "checked"?> id="answer_type_s" value='sort' onclick="selectAnswerType('sort');" /> <?php _e('Sort the values', 'watupro')?></label> &nbsp;&nbsp;&nbsp;
<label>&nbsp;<input type='radio' name='answer_type' <?php if(!empty($ans_type) and $ans_type=='matrix') echo "checked"?> id="answer_type_m" value='matrix' onclick="selectAnswerType('matrix');" /> <?php _e('Matrix / Match values', 'watupro')?></label> &nbsp;&nbsp;&nbsp;

		<div id="fillTheGapsText" style="display:<?php echo ($ans_type == 'gaps')?'block':'none'?>;">
			<p><?php _e('For "fill the gaps" questions simply enter your <b>correct text</b> and place {{{ and }}} around the phrases that should turn into gaps (no spaces please). Example: "<b>The color of sun is {{{yellow}}}.</b>" The student will be shown input field instead of "{{{yellow}}}" in this case.', 'watupro')?><br>
			<?php _e('You can include multiple correct answers for each blank/gap if you separate them by |. For example: <b>"The color of a good apple can be {{{green|yellow|red}}}"</b>.','watupro')?></p>
			<p><?php _e('Points to assign for correctly filled gap:', 'watupro')?> <input type="text" name="correct_gap_points" value="<?php echo @$question->correct_gap_points?>" size="4"> &nbsp; <?php _e('Points to assign for incorrectly filled gap (optional):', 'watupro')?> <input type="text" name="incorrect_gap_points" value="<?php echo @$question->incorrect_gap_points?>" size="4"> <?php _e('(Decimals allowed)', 'watupro')?></p>
		</div>
		
		
		<div id="sortingText" style="display:<?php echo ($ans_type == 'sort' or $ans_type == 'matrix')?'block':'none'?>;">
			<p><input type="checkbox" name="calculate_whole" value="1" <?php if(!empty($question->calculate_whole)) echo "checked"?> onclick="watuproSortableAsWhole(this);"> <?php _e('Treat this question as a whole', 'watupro')?></p>
			<p><span id="sortingCorrectPointsText"><?php if(empty($question->calculate_whole)): _e('Points to assign for correctly matched position:', 'watupro'); else: _e('Points to assign for when all positions are matched correctly:', 'watupro'); endif;?></span> <input type="text" name="correct_sort_points" value="<?php echo @$question->correct_gap_points?>" size="4"> &nbsp; <span id="sortingIncorrectPointsText"><?php if(empty($question->calculate_whole)): _e('Points to assign for incorrectly matched position (optional):', 'watupro'); else: _e('Points to assign when there is a mistake in ordering the items (optional):'); endif;?></span> <input type="text" name="incorrect_sort_points" value="<?php echo @$question->incorrect_gap_points?>" size="4"> <?php _e('(Decimals allowed)', 'watupro')?></p>			
		</div>	

<script type="text/javascript" >
function watuproSortableAsWhole(chk) {
	if(chk.checked) {
		jQuery('#sortingCorrectPointsText').html("<?php _e('Points to assign for when all positions are matched correctly:', 'watupro')?>");
		jQuery('#sortingIncorrectPointsText').html("<?php _e('Points to assign when there is a mistake in ordering the items (optional):', 'watupro')?>");
	}
	else {
		jQuery('#sortingCorrectPointsText').html("<?php _e('Points to assign for correctly matched position:', 'watupro')?>");
		jQuery('#sortingIncorrectPointsText').html("<?php _e('Points to assign for incorrectly matched position (optional):', 'watupro')?>");
	}
}
</script>		