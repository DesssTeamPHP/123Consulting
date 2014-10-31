<?php
// advanced exam settings
function watupro_advanced_exam_settings() {
	global $wpdb;
	
	// select exam
	$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_GET['exam_id']));
	
	if(empty($exam->ID)) {
		echo "<div class='inside'><p>".sprintf(__('This tab will become available after the %s is created.', 'watupro'), __('quiz', 'watupro'))."</p></div>"; 
		return false;
	}	

	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
	$exam_id = empty($exam->reuse_questions_from) ? $exam->ID : $exam->reuse_questions_from;
	
	// select question categories
	$qcats = $wpdb->get_results("SELECT tC.* FROM ".WATUPRO_QCATS." tC
		JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.cat_id = tC.ID
		JOIN ".WATUPRO_EXAMS." tE ON tE.ID = tQ.exam_id AND tE.ID IN ($exam_id)
		GROUP BY tC.ID ORDER BY tC.name");
		
	// any uncategorized questions?
	$num_uncategozied = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_QUESTIONS."
		WHERE exam_id=%d", $exam->ID));	
	if($num_uncategozied) $qcats[] = (object)array("ID"=>0, "name"=>__('Uncategorized', 'watupro'));	
	
	if(!empty($_POST['ok'])) {
		// save advanced config
		unset($_POST['ok']);
		
		// add sorted categories
		$sorted_cats = array();
		foreach($qcats as $qcat) {
			$sorted_cats[$qcat->name] = $_POST['qcat_order_'.$qcat->ID];
		}
		$advanced_settings['sorted_categories'] = $sorted_cats;
		$advanced_settings['confirm_on_submit'] = @$_POST['confirm_on_submit'];	
		$advanced_settings['no_checkmarks'] = @$_POST['no_checkmarks'];
		$advanced_settings['no_checkmarks_unresolved'] = @$_POST['no_checkmarks_unresolved'];
		$advanced_settings['reveal_correct_gaps'] = @$_POST['reveal_correct_gaps'];
		$advanced_settings['dont_prompt_unanswered'] = @$_POST['dont_prompt_unanswered'];		
		$advanced_settings['dont_load_inprogress'] = @$_POST['dont_load_inprogress'];
		$advanced_settings['email_not_required'] = @$_POST['email_not_required'];
		$advanced_settings['show_only_snapshot'] = @$_POST['show_only_snapshot'];
		$advanced_settings['answered_paginator_color'] = @$_POST['answered_paginator_color'];
		$advanced_settings['unanswered_paginator_color'] = @$_POST['unanswered_paginator_color'];
		$advanced_settings['enumerate_choices'] = @$_POST['enumerate_choices'];
		$advanced_settings['enumerate_choices'] =@$_POST['enumerate_choices'];
		$advanced_settings['dont_scroll'] =@$_POST['dont_scroll'];
		foreach($qcats as $cnt=>$qcat) {
			$advanced_settings['qcat_order_'.$qcat->ID]  = @$_POST['qcat_order_'.$qcat->ID];
			$advanced_settings['random_per_'.$qcat->ID]  = @$_POST['random_per_'.$qcat->ID];
		}
		
		$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_EXAMS." SET advanced_settings=%s WHERE ID=%d",
			serialize($advanced_settings), $exam->ID));
		return true; // becuse $_POST['ok'] is now called from the WatuPRO edit exam page, we'll return here instead of displaying anything	
	}	
	
	// select exam
	$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $exam->ID));
	$exam_id = empty($exam->reuse_questions_from) ? $exam->ID : $exam->reuse_questions_from;
		
	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
	
	// add sort order 
	$sorted_cats = @$advanced_settings['sorted_categories'];	
	// print_r($sorted_cats);
	foreach($qcats as $cnt=>$qcat) {
		$def_order = $cnt+1;
		if(isset($sorted_cats[$qcat->name])) $qcats[$cnt]->sort_order = intval($sorted_cats[$qcat->name]);
		else $qcats[$cnt]->sort_order = $def_order;
	}	
	
	if(@file_exists(get_stylesheet_directory().'/watupro/i/advanced-settings.html.php')) require get_stylesheet_directory().'/watupro/i/advanced-settings.html.php';
	else require WATUPRO_PATH."/i/views/advanced-settings.html.php";
}

// adds optional info to show_exam
function watuproi_show_exam_js($exam) {
	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));	
	
	if(!empty($advanced_settings['email_not_required'])) {
		 echo 'WatuPRO.emailIsNotRequired = 1; ';
	}
}

// enhanced show_exam
function watuproi_show_exam($view, $exam) {
	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));	
	
	if(@file_exists(get_stylesheet_directory().'/watupro/i/show-exam.html.php')) return get_stylesheet_directory().'/watupro/i/show-exam.html.php';
	else return WATUPRO_PATH."/i/views/show-exam.html.php";
}