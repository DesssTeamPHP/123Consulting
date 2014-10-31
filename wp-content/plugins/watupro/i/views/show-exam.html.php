<?php  if(!empty($advanced_settings['answered_paginator_color']) or !empty($advanced_settings['unanswered_paginator_color'])):?>
<style type="text/css"><?php if(!empty($advanced_settings['answered_paginator_color'])):?>
		ul.watupro-paginator li.answered {
			background-color: <?php echo $advanced_settings['answered_paginator_color']?> !important;
		}
	<?php endif;
	if(!empty($advanced_settings['unanswered_paginator_color'])):?>
		ul.watupro-paginator li.unanswered {
			background-color: <?php echo $advanced_settings['unanswered_paginator_color']?> !important;
		}
	<?php endif;?></style>
<?php endif;
include(WATUPRO_PATH."/views/show_exam.php");