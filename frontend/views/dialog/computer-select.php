<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<input type='hidden' name='subject_id' value='<?php echo htmlspecialchars($_GET['subject_id']??'',ENT_QUOTES); ?>'></input>
<div class='gallery computerSelection'>
	<div>
		<?php require('../partial/computer-selection.php'); ?>
	</div>
</div>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='assign'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
