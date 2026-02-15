<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<input type='hidden' name='id' value='<?php echo htmlspecialchars($_GET['id']??'',ENT_QUOTES); ?>'></input>
<div class='gallery'>
	<div>
		<select name='group' class='resizeVertical' size='10' multiple='true'>
			<?php Html::buildGroupOptions($cl, new Models\ComputerGroup()); ?>
		</select>
	</div>
</div>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='add'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
