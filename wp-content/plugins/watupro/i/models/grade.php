<?php
// Intelligence module grade object
class WTPIGrade {
	// @param grade_id array - all the grade IDs collected
	// for personality quizzes
	static function calculate($grade_ids) {
		global $wpdb;		
		$grade = __('None', 'watupro');
		$grade_obj = (object)array("title"=>__('None', 'watupro'), "description"=>"");
		$do_redirect = false;
		$certificate_id=0;
		
		// from version 4.4.1 $grade_ids may contain arrays of multiple grade objects. Like this:
		// [5, 1|3, 1, 4|5|1] so we need to break it further
		$final_grade_ids = array();
		foreach($grade_ids as $grade_id) {
			if(strstr($grade_id, '|')) {
				$grids = explode('|', $grade_id);
				$final_grade_ids = array_merge($final_grade_ids, $grids);
			}			
			else $final_grade_ids[] = $grade_id;
		}
		
		$grade_ids = $final_grade_ids;
		
		// store the grade_ids in the DB. We may need this in the shortcode and elsehwere
		$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_TAKEN_EXAMS." SET personality_grade_ids=%s
			WHERE ID=%d", serialize($grade_ids), $GLOBALS['watupro_taking_id']));
		
		//print_r($grade_ids);
		// find the top grade
		if(sizeof($grade_ids)) {
			$grade_ids = array_count_values($grade_ids);				
			$grade_ids = array_flip($grade_ids);					
			krsort($grade_ids);			
			$grade_id = array_shift($grade_ids);
			
			// select the grade
			$grow = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_GRADES." WHERE ID=%d", $grade_id));
			list($grade, $grade_obj, $certificate_id, $do_redirect) = WTPGrade :: match_grade($grow); 					
		}		
		
		return array($grade, $certificate_id, $do_redirect, $grade_obj);
	}
	
	// this method loops through all personality grades in the given quiz
	// $atts['sort'] : best, worst, alphabetic, default (order of creation) 
	// $atts['empty'] : true to show types where you got 0, false to not show them. Default: true
	// $atts['limit'] : how many grades to show. Defaults to no limit 
	static function expand_personality_result($atts, $content = '') {
		global $wpdb;
		if(empty($content)) return '';
		
		$taking_id = intval($_POST['watupro_current_taking_id']);
		if(empty($taking_id)) return '';
		
		// now select grades
		$taking = $wpdb->get_row($wpdb->prepare("SELECT exam_id, personality_grade_ids FROM ".WATUPRO_TAKEN_EXAMS." WHERE ID=%d", $taking_id));
		
		// is personality at all?
		$is_personality = $wpdb->get_var($wpdb->prepare("SELECT is_personality_quiz FROM ".WATUPRO_EXAMS." WHERE ID=%d", $taking->exam_id));
		
		if(!$is_personality) return '';
		
		// now select grades
		$grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRO_GRADES."
			WHERE cat_id=0 AND exam_id=%d ORDER BY ID", $taking->exam_id)); 
			
		$grade_ids = unserialize($taking->personality_grade_ids);
		$grade_ids = array_count_values($grade_ids);			
		
		// fill the numbers
		foreach($grades as $cnt=>$grade) {
			$grades[$cnt]->count = 0;
			foreach($grade_ids as $grade_id => $count) {
				if($grade_id == $grade->ID) $grades[$cnt]->count = $count;
			}
			
			// omit grades that did not collect any points?
			if(!empty($atts['empty']) and $atts['empty']=='false' and empty($grades[$cnt]->count)) unset($grades[$cnt]); 
		}

		// sort
		if(!empty($atts['sort']) and $atts['sort'] != 'default') {
			if($atts['sort'] == 'best') uasort($grades, array(__CLASS__, 'sort_results_best'));
			if($atts['sort'] == 'worst') uasort($grades, array(__CLASS__, 'sort_results_worst'));
			if($atts['sort'] == 'alpha') uasort($grades, array(__CLASS__, 'sort_results_alpha'));
		}
		
		// limit
		if(!empty($atts['limit']) and is_numeric($atts['limit'])) {
			$grades = array_slice($grades, 0, $atts['limit']);
		}
				
		// and replace the texts
		$final_content = '';
		
		foreach($grades as $grade) {
			$grade_content = str_replace('{{{personality-type}}}', stripslashes($grade->gtitle), $content);
			$grade_content = str_replace('{{{personality-type-description}}}', wpautop(stripslashes($grade->gdescription)), $grade_content);
			$grade_content = str_replace('{{{num-answers}}}', $grade->count, $grade_content);
			$final_content .= wpautop($grade_content);
		}
		
		return $final_content;
	}
	
	// sort personality results by best on top
	static function sort_results_best($grade_a, $grade_b) {
		if($grade_a->count == $grade_b->count) return 0;
		return ($grade_a->count > $grade_b->count) ? -1 : 1;
	}
	
	// sort personality results by worst on top
	static function sort_results_worst($grade_a, $grade_b) {
		if($grade_a->count == $grade_b->count) return 0;
		return ($grade_a->count < $grade_b->count) ? -1 : 1;
	}
	
	// sort personality results by alpha
	static function sort_results_alpha($grade_a, $grade_b) {
		if($grade_a->gtitle == $grade_b->gtitle) return 0;
		return ($grade_a->gtitle < $grade_b->gtitle) ? -1 : 1;
	}
}