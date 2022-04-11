<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditComputerId'></input>
<div class='gallery'>
	<div>
		<select id='sltNewComputerGroup' class='resizeVertical' size='10'>
			<?php echoComputerGroupOptions($db); ?>
		</select>
	</div>
</div>

<div class='controls right'>
	<button onclick="hideDialog()"><img src="img/close.dyn.svg">&nbsp;<?php echo LANG['close']; ?></button>
	<button class='primary' onclick='addComputerToGroup(txtEditComputerId.value, sltNewComputerGroup.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>
