<?php
// Intelligence specific question queries
class WatuPROIQuestion {
	public static $advanced_settings = "";
	public static $personality_grades = null;
	
	static function edit($vars, $id) {
		 global $wpdb;
		 
		 if($vars['answer_type']=='sort' or $vars['answer_type']=='matrix') {
		 	// sorting questions use gaps fields to avoid adding unnecessary fields
		 	$vars['correct_gap_points'] = $vars['correct_sort_points'];
		 	$vars['incorrect_gap_points'] = $vars['incorrect_sort_points'];
		 }
		 
		 if(empty($vars['correct_gap_points'])) $vars['correct_gap_points'] = 0;
		 if(empty($vars['incorrect_gap_points'])) $vars['incorrect_gap_points'] = 0;
		 if(empty($vars['sorting_answers'])) $vars['sorting_answers'] = '';
		 
	   $sql = $wpdb->prepare("UPDATE ".WATUPRO_QUESTIONS." SET 
	   	correct_gap_points = %s, incorrect_gap_points=%s, sorting_answers=%s
	   	WHERE ID = %d", $vars['correct_gap_points'], $vars['incorrect_gap_points'], $vars['sorting_answers'], $id);
	   $wpdb->query($sql);	
	}
	
	// display a question like fill the gaps etc
	static function display($question, $qct, $question_count, $inprogress_details) {			
			// handle right to left languages		 
		  $dir = is_rtl() ? " dir='rtl' " : "";
		  $question_number = empty(self :: $advanced_settings['dont_display_question_numbers']) ? "<span class='watupro_num'>$qct. </span>"  : '';
			switch($question->answer_type) {
				case 'gaps':				
					// parse {{{xxxx}}} into input fields - pattern {{{([^}}}])*}}}
					$html = stripslashes($question->question);
					$matches = array();
					preg_match_all("/{{{([^}}}])*}}}/", $html, $matches);
					
					foreach($matches[0] as $cnt=>$match) {
						 $value = ""; // inprogress value
						 if(!empty($inprogress_details[$question->ID][$cnt])) $value = $inprogress_details[$question->ID][$cnt];						
						
						 $cnt++;
						 $input = '<input type="text" size="10" name="gap_'.$question->ID.'_'
						 	.$cnt.'" class="answer answerof-'.$question->ID.'" value="'.$value.'" '.$dir.'>';
						
						 $match = watupro_preg_escape($match);
						 $html = preg_replace("/".$match."/", $input, $html, 1);						
					}					
					
					echo wpautop(stripslashes( WTPQuestion :: flag_review($question, $qct) . $question_number . $html),0);
				break;	
				case 'sort':		
					$inprogress_values = array();
					if(!empty($inprogress_details[$question->ID][0])) {
						$inprogress_values = explode("|", urldecode(stripslashes($inprogress_details[$question->ID][0])));
						array_pop($inprogress_values);
					}

					if(empty($inprogress_values)) {					
						$sort_values = explode("\n",stripslashes($question->sorting_answers));
						shuffle($sort_values);
					} 
					else $sort_values = $inprogress_values;	
					
					$sorted_answers = ''; // let's initially record how they are sorted
					
					echo WTPQuestion :: flag_review($question, $qct);					
					echo $question_number . stripslashes($question->question);
					echo  "<!-- end question-content--></div>"; // end question-content
					echo "<div class='question-choices'>";					
					echo "<ul id='watuPROSortable".$question->ID."' class='watupro-sortable'>";
					foreach($sort_values as $ct=>$svalue):
							$ct++;
							$svalue = trim($svalue);
							$sorted_answers .= $svalue."|";?>
						<li id="watuPROSortable<?php echo $question->ID?>_<?php echo $ct?>"><?php echo $svalue?><!--|||<?php echo urlencode($svalue);?>--></li>
										
					<?php endforeach;
					echo "</ul>";
					echo '<input type="hidden" name="answer-'.$question->ID.'[]" id="watuPROSortableValue'.$question->ID.'" value="'.urlencode($sorted_answers).'" class="answer answerof-'.$question->ID.'">';
					?>					
					<script type="text/javascript">
					jQuery(function() {
					    jQuery( "#watuPROSortable<?php echo $question->ID?>" ).sortable({								
								stop: function(event, ui) { WatuPROSort.sortable(event, ui) }				    	
					    	});					    
					  });
					</script><?php echo "</div><!-- end question-choices-->";
				break;
				
				case 'matrix':
					$inprogress_values = @$inprogress_details[$question->ID];
												
					$matches = $question->q_answers;
					$lefts = array();
					$rights = array();
					
					foreach($matches as $match) {
						list($left, $right) = explode('{{{split}}}', $match->answer);
						$lefts[] = array("left"=>$left, "match_id"=>$match->ID);
						$rights[] = $right;
					}
					
					shuffle($rights);
					
					// display the rights div
					echo WTPQuestion :: flag_review($question, $qct);
					echo $question_number . stripslashes($question->question);
					
					echo "<div class='question-choices'>";		
					echo '<div class="watupro-matrix-right">';
					foreach($rights as $right) {
						// if we have it in $in_progress don't display it here
						if(is_array($inprogress_values) and in_array(md5($right), $inprogress_values)) continue;
						echo '<div class="watupro-matrix-draggable watuPRODraggable'.$question->ID.'">'.stripslashes($right).'<!--WTPMD5'.md5($right).'--></div>';
					}
					echo '</div>'; // end right matches					
					echo '</div>'; // end question choices
					
					// now create the droppable area
					echo '<table class="watupro-matrix-droppable">';
					
					foreach($lefts as $cnt=>$left) {
						$prefilled_class = empty($inprogress_values[$cnt]) ? '' : ' watupro-droppable-hover';
						echo '<tr><td class="watupro-matrix-left-cell">'.stripslashes($left['left']).'</td>
						<td class="watupro-matrix-right-cell watuPRODroppable'.$question->ID.$prefilled_class.'" id="watuPRODroppableCell'.$left['match_id'].'">';
						
						// anything from $inprogress?
						if(is_array($inprogress_values) and !empty($inprogress_values[$cnt])) {
							echo '<div class="watupro-matrix-draggable watuPRODraggable'.$question->ID.'">'.stripslashes(self :: unmd5($inprogress_values[$cnt], $question->q_answers)).'<!--WTPMD5'.$inprogress_values[$cnt].'--></div>';
						}						
						
						echo '<input type="hidden" class="answerof-'.$question->ID.'"  id="field-watuPRODroppableCell'.$left['match_id'].'" name="matrix-left-'.$left['match_id'].'" value="'.@$inprogress_values[$cnt].'"></td></tr>';
					}
						
					echo '</table>';
					?>
					<script type="text/javascript">
					jQuery(function() {
					    jQuery( ".watuPRODraggable<?php echo $question->ID?>" ).draggable({
							 revert: "invalid", // when not dropped, the item will revert back to its initial position
      					 containment: "document",						    	
					    });					    
						jQuery(".watuPRODroppable<?php echo $question->ID?>").droppable({
							accept: ".watuPRODraggable<?php echo $question->ID?>",
							greedy: true,
							hoverClass: 'watupro-droppable-hover',
							drop: function(event, ui) {
								WatuPROIDroppable.drop(event, ui);
								jQuery(this).droppable('option', 'accept', ui.draggable);
							},
							out: function(event, ui) {
								jQuery(this).droppable('option', 'accept', ".watuPRODraggable<?php echo $question->ID?>");
							}
						});    
					});
					</script>
					<?php
				break;			
			}
	}
	
	// processes specific types of questions (like gaps) on submit
	// fill the gaps will not take effect in personality quizzes (makes no sense)
	// sorting questions will assign points for each grade depending on the sorted position. 
	// For example if there are 3 items the top one gets 3 points, next one 2, the bottom one - 1
	// When the admin enters the positions they will be matched to the grades (ordered by ID) 
	static function process($question, $user_answers) {		
		global $wpdb;
		
		switch($question->answer_type) {
			case 'gaps':							
				$html = stripslashes($question->question);	
				$matches = array();
				preg_match_all("/{{{([^}}}])*}}}/", $html, $matches);
				$points = 0;	
				$max_points = sizeof($matches[0]) * $question->correct_gap_points;	
							
				foreach($matches[0] as $cnt=>$match) {					
					// in case we come from non-ajax there will be values like $_POST['gap_{questionID}_1'] etc
					if(isset($_POST['gap_'.$question->ID.'_'.($cnt+1)])) $user_answer = stripslashes($_POST['gap_'.$question->ID.'_'.($cnt+1)]);
					else $user_answer = stripslashes($user_answers[$cnt]);
					
					// avoid wrong "Question was not answered" text
					if(!empty($user_answer)) $_POST['answer-'.$question->ID][] = $user_answer;
					
					// compare to know if it's correct or not
					$match = trim($match);
					$compare_match = str_replace("{{{", "", $match);
					$compare_match = str_replace("}}}", "", $compare_match);
					
					// by parsing the match on possible parts we'll cover both simple and complex matches
					$parts = explode('|', $compare_match);
					$is_correct = 0;
					
					foreach($parts as $part) {
						if(strcasecmp(trim($user_answer), trim($part)) == 0) {
							$is_correct = 1;
							break;
						}
					}			
					
					// now add points and mark it
					if($is_correct) {
						 $img='<img src="'.plugins_url("watupro").'/correct.png" hspace="5">';
						 $points += $question->correct_gap_points;
					}	 
					else {
						// reveal correct?
						$revealed_answer = '';
						if(!empty(self :: $advanced_settings['reveal_correct_gaps'])) {
							$revealed_answer = preg_replace("/(\{\{\{|\}\}\})*/", '', $match);
							$revealed_answer = str_replace("|", ' '.__('or', 'watupro').' ', $revealed_answer);
							$revealed_answer = sprintf(__('(correct answer: %s)', 'watupro'), $revealed_answer);
						}						
						
						$img=$revealed_answer.'<img src="'.WATUPRO_URL.'/wrong.png" hspace="5">';
						if(empty($user_answer)) $user_answer = __('[no answer]', 'watupro');
						$points += $question->incorrect_gap_points;
					}
					
					$match = watupro_preg_escape($match);					
					$user_answer = watupro_preg_escape($user_answer);
					$html = preg_replace('/'.$match.'/', '<span class="user-answer">'.$user_answer.'</span>&nbsp;'.$img, $html, 1);
				}		
				
				$html = stripslashes($html);
				//."</div>"; // has to close the question contents div				
			break;
			
			case 'sort':			
				$s_values = explode("\n", stripslashes($question->sorting_answers));				
				$html = '';
				$max_points = sizeof($s_values) * $question->correct_gap_points;	
				$points = 0;
				// reconfigure user answer because in this question type it comes as a single value separated by |
				$user_answer = $user_answers[0];
				$user_answers = explode("|", urldecode($user_answer));
				array_pop($user_answers); // the last one doesn't play because the string ends with |
				$all_correct = true; // used when question is treated as a whole
				
				foreach($user_answers as $cnt=>$user_answer) {		
					$user_answer = stripslashes($user_answer);								
					foreach($s_values as $sct=>$s_value) {
						if($sct!=$cnt) continue;
						$s_value = stripslashes($s_value);
												
						if(strcmp(trim($s_value), trim($user_answer)) == 0) {
							$img='<img src="'.WATUPRO_URL.'/correct.png" hspace="5">';
						 	$points += $question->correct_gap_points;
						}
						else {
							$img='<img src="'.WATUPRO_URL.'/wrong.png" hspace="5">';
							$points += $question->incorrect_gap_points;
							$all_correct = false;
						} 
					}					
					if($question->is_survey) $img = '';
					$html .= "<li>".$user_answer.'&nbsp;'.$img."</li>";
				}
				
				// if we treat question as a whole, points are calculated in different way
				if(!empty($question->calculate_whole)) {
					$max_points = $question->correct_gap_points;
					$points = $all_correct ? $question->correct_gap_points : $question->incorrect_gap_points;
				}		
			break;
			
			case 'matrix':
				$all_correct = true; // used when question is treated as a whole
				$max_points = sizeof($question->q_answers) * $question->correct_gap_points;	
				$points = 0;
				
				$html = "<table class='watupro-matrix-table'>";
				foreach($question->q_answers as $cnt=>$match) {
					list($left, $right) = explode('{{{split}}}', $match->answer);
					$html .= "<tr><td>".stripslashes($left)."</td>";
					
					// when no_ajax
					if(isset($_POST['matrix-left-'.$match->ID]))  $user_answers[$cnt] = $_POST['matrix-left-'.$match->ID];
					// avoid wrong "Question was not answered" text
					if(!empty($user_answers[$cnt])) $_POST['answer-'.$question->ID][] = $user_answers[$cnt];
				
					if(md5($right) == $user_answers[$cnt]) {
						$img='<img src="'.WATUPRO_URL.'/correct.png" hspace="5">';
						$points += $question->correct_gap_points;
					}
					else {
						$img='<img src="'.WATUPRO_URL.'/wrong.png" hspace="5">';
						$points += $question->incorrect_gap_points;
						$all_correct = false;
					}
					
					$html .= "<td>".stripslashes( self :: unmd5($user_answers[$cnt], $question->q_answers) ).' '.$img."</td></tr>";
				}				
				
				$html .= "</table>";
				
				// if we treat question as a whole, points are calculated in different way
				if(!empty($question->calculate_whole)) {
					$max_points = $question->correct_gap_points;
					$points = $all_correct ? $question->correct_gap_points : $question->incorrect_gap_points;
				}	
			break;
		}		
		
		return array($points, $html, $max_points);
	} // end process question
	
	// small helper currently used by matrix questions. Compares the encoded answer
	// to md5 of all "right" parts of the answers to retrieve the decoded answer
	// curently used by matrix questions 
	static function unmd5($encoded, $answers) {
		foreach($answers as $answer) {
			list($left, $right) = explode('{{{split}}}', $answer->answer);			
			if(md5($right) == $encoded) return $right; 
		}
		
		return ''; // just in case
	}
	
	// displays option to reuse questions from another quiz
	static function reuse_questions($exam, &$intelligence_display) {
		global $wpdb;
		$reused_exams = explode(",", $exam->reuse_questions_from);
		
		if(!empty($_POST['ok'])) {
			// when the checkbox is unchecked, vanish the dropdown selection
			if(empty($_POST['reuse_questions'])) $_POST['reuse_questions_from'] = 0;			
			
			$reuse_questions_from = @implode(",", @$_POST['reuse_questions_from']);
			$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_EXAMS." SET reuse_questions_from=%s 
				WHERE ID=%d", $reuse_questions_from, $exam->ID));
				
			$reused_exams = @$_POST['reuse_questions_from'];
		}
		
		// select other existing exams
		$exams = $wpdb->get_results($wpdb->prepare("SELECT tE.* 
			FROM ".WATUPRO_EXAMS." tE JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.exam_id = tE.ID 
			WHERE tE.ID!=%d AND tE.reuse_questions_from=0 GROUP BY tE.ID ORDER BY tE.name", $exam->ID));
			
		if(!empty($reused_exams) and !empty($reused_exams[0])) $intelligence_display = "style='display:none;'";	
		
		if(@file_exists(get_stylesheet_directory().'/watupro/i/reuse_questions.php')) require get_stylesheet_directory().'/watupro/i/reuse_questions.php';
		else require WATUPRO_PATH."/i/views/reuse_questions.php";
	}
	
	// filter question processed text for some reason
	// for example in advanced settings we may have disabled right/wrong checkmarks
	static function filter_text($current_text, $qct, $question_content, $is_correct) {
		$advanced_settings = self :: $advanced_settings;
		$_question = new WTPQuestion();
		
		// remove checkmarks if so is selected
		if(!empty($advanced_settings['no_checkmarks']) or (!empty($advanced_settings['no_checkmarks_unresolved']) and !$is_correct) ) {
			$current_text = $_question->display_unresolved($current_text);
		}	
		
		return $current_text;
	} // end filter_text
	
	// saves the answers of a match / matrix question
	static function save_matrix($question_id, $old_answers) {
		global $wpdb;
				
		// first edit old answers (and delete these that are emptied)
		foreach($old_answers as $answer) {
			if(empty($_POST['left_match_'.$answer->ID])) {
				$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPRO_ANSWERS." WHERE ID=%d", $answer->ID));
				continue;
			}
			
			// if not empty, save
			$ans_text = $_POST['left_match_'.$answer->ID].'{{{split}}}'.$_POST['right_match_'.$answer->ID];
			
			$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_ANSWERS." SET answer=%s WHERE ID=%d", $ans_text, $answer->ID));
		}
		
		// now add new matches
		if(!empty($_POST['new_matches_left'])) {
			foreach($_POST['new_matches_left'] as $cnt=>$left) {
				if(empty($left) or empty($_POST['new_matches_right'][$cnt])) continue;
				
				$ans_text = $left.'{{{split}}}'.$_POST['new_matches_right'][$cnt];
				
				$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_ANSWERS." SET 
					question_id=%d, answer=%s, sort_order=%d",
					$question_id, $ans_text, $cnt + 1));
			}
		}		
	} // end save_matrix
	
	// calculate personality results on sorting questions
	static function sort_question_personality($question, $user_answers, &$user_grade_ids) {
		global $wpdb;
		
		// grades already selected?
		if(empty( self :: $personality_grades)) {
			 $grades = $wpdb->get_results($wpdb->prepare("SELECT ID, gtitle FROM ".WATUPRO_GRADES." 
				WHERE exam_id=%d ORDER BY ID", $question->exam_id));
			self :: $personality_grades = $grades;
		}	
		$grades = self :: $personality_grades;		
		
		$s_values = explode("\n", stripslashes($question->sorting_answers));				
		$top_points = sizeof($s_values);	
		
		// reconfigure user answer because in this question type it comes as a single value separated by |
		$user_answer = $user_answers[0];
		$user_answers = explode("|", urldecode($user_answer));
		array_pop($user_answers); // the last one doesn't play because the string ends with |
		
		// now foreach $s_values you have to check on which position the user has sorted it
		// to figure out points and add it as many times to the $user_grade_ids array	
		foreach($s_values as $svcnt=>$s_value) {
			foreach($user_answers as $cnt=>$answer) {
				if(strcmp(trim($s_value), trim($answer)) == 0) {					
					$times_to_add = $top_points - $cnt;
					
				   if($times_to_add and !empty($grades[$svcnt]->ID)) {
				   	// add this grade ID $times_to_add times to $user_grade_ids
				   	for($i = 0; $i < $times_to_add; $i++) $user_grade_ids[] = $grades[$svcnt]->ID;
					}
				}
			} // end foreach answer
		}	// end foreach value			
		
	} // end calculating personality on sorting questions
}