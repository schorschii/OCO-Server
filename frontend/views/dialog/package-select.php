<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');
?>

<div class='dialogStretch'>
	<input type='hidden' name='subject_id' value='<?php echo htmlspecialchars($_GET['subject_id']??'',ENT_QUOTES); ?>'></input>

	<div class='gallery packageSelection'>
		<div class='fillHeight'>
			<?php if(!empty($_GET['single'])) $SINGLE_SELECTION=1; require(__DIR__.'/../partial/package-selection.php'); ?>
		</div>
	</div>

	<div class='controls right'>
		<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
		<button class='primary' name='assign'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
	</div>
</div>
